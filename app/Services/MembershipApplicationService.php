<?php

namespace App\Services;

use App\Enum\ApplicationStatus;
use App\Enum\MemberStatus;
use App\Enum\UserStatus;
use App\Models\ApplicationStatusHistory;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Notifications\ApplicationApprovedNotification;
use App\Notifications\ApplicationReceivedNotification;
use App\Notifications\ApplicationRejectedNotification;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MembershipApplicationService
{
    // ─── Public: Submit Application ──────────────────────────────────────────

    /**
     * Handle public membership application submission.
     */
    public function submit(array $data, Request $request): MembershipApplication
    {
        // Block duplicate pending/under_review application by same email or mobile
        $this->blockDuplicateApplication($data);

        $photoPath = null;
        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            $photoPath = $data['photo']->store('membership-applications/photos', 'public');
        }

        $application = MembershipApplication::create([
            ...$data,
            'photo'        => $photoPath,
            'uuid'         => (string) Str::uuid(),
            'application_no' => $this->generateApplicationNo(),
            'status'       => ApplicationStatus::Pending,
            'source'       => 'web',
            'submitted_ip' => $request->ip(),
        ]);

        // Record initial status history
        $this->recordHistory($application, null, ApplicationStatus::Pending->value, null, 'Application submitted by applicant.');

        // Send acknowledgment — queued if queue driver configured, sync otherwise.
        // Mail failure does NOT interrupt the submission.
        $this->sendNotification($application, 'received');

        return $application;
    }

    // ─── Admin: Review ────────────────────────────────────────────────────────

    public function markUnderReview(MembershipApplication $application, User $admin, ?string $remarks): MembershipApplication
    {
        if ($application->status->isFinalized()) {
            throw ValidationException::withMessages([
                'status' => ['This application is already finalized and cannot be set to under review.'],
            ]);
        }

        if ($application->status === ApplicationStatus::UnderReview) {
            throw ValidationException::withMessages([
                'status' => ['Application is already under review.'],
            ]);
        }

        $old = $application->status->value;

        $application->update([
            'status'      => ApplicationStatus::UnderReview,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'remarks'     => $remarks,
        ]);

        $this->recordHistory($application, $old, ApplicationStatus::UnderReview->value, $admin->id, $remarks);

        return $application->refresh();
    }

    // ─── Admin: Approve ───────────────────────────────────────────────────────

    /**
     * Approve an application.
     * Creates a Member and associated User account inside a DB transaction.
     *
     * @throws ValidationException
     */
    public function approve(MembershipApplication $application, User $admin, ?string $remarks): array
    {
        if ($application->status === ApplicationStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => ['This application has already been approved.'],
            ]);
        }

        if ($application->status === ApplicationStatus::Rejected) {
            throw ValidationException::withMessages([
                'status' => ['Rejected applications cannot be approved directly. Please contact a superadmin.'],
            ]);
        }

        $old = $application->status->value;

        [$user, $member] = DB::transaction(function () use ($application, $admin, $remarks, $old) {
            // 1. Update application status
            $application->update([
                'status'      => ApplicationStatus::Approved,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'remarks'     => $remarks,
            ]);

            // 2. Record history
            $this->recordHistory($application, $old, ApplicationStatus::Approved->value, $admin->id, $remarks);

            // 3. Create or activate user account
            $user = $this->resolveUserForApplicant($application);

            // 4. Create member profile
            $member = $this->createMember($application, $user, $admin);

            // 5. Assign default member role
            $user->assignRole('member');

            return [$user, $member];
        });

        // 6. Send password setup link (outside transaction — mail failure should not roll back DB)
        $this->dispatchPasswordSetupLink($user);

        // 7. Send approval notification
        $this->sendApprovalNotification($application, $member);

        return [$user, $member];
    }

    // ─── Admin: Reject ────────────────────────────────────────────────────────

    public function reject(MembershipApplication $application, User $admin, string $reason): MembershipApplication
    {
        if ($application->status === ApplicationStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => ['Approved applications cannot be rejected. Contact superadmin for reversal.'],
            ]);
        }

        if ($application->status === ApplicationStatus::Rejected) {
            throw ValidationException::withMessages([
                'status' => ['This application is already rejected.'],
            ]);
        }

        $old = $application->status->value;

        $application->update([
            'status'           => ApplicationStatus::Rejected,
            'rejected_by'      => $admin->id,
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        $this->recordHistory($application, $old, ApplicationStatus::Rejected->value, $admin->id, $reason);

        // Send rejection notification
        $this->sendNotification($application, 'rejected');

        return $application->refresh();
    }

    // ─── Admin: Hold ──────────────────────────────────────────────────────────

    public function hold(MembershipApplication $application, User $admin, string $remarks): MembershipApplication
    {
        if ($application->status === ApplicationStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => ['Approved applications cannot be placed on hold.'],
            ]);
        }

        if ($application->status === ApplicationStatus::OnHold) {
            throw ValidationException::withMessages([
                'status' => ['Application is already on hold.'],
            ]);
        }

        $old = $application->status->value;

        $application->update([
            'status'  => ApplicationStatus::OnHold,
            'remarks' => $remarks,
        ]);

        $this->recordHistory($application, $old, ApplicationStatus::OnHold->value, $admin->id, $remarks);

        return $application->refresh();
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Block duplicate pending/under-review applications by same email or mobile.
     *
     * @throws ValidationException
     */
    private function blockDuplicateApplication(array $data): void
    {
        $activeStatuses = [
            ApplicationStatus::Pending->value,
            ApplicationStatus::UnderReview->value,
            ApplicationStatus::OnHold->value,
        ];

        $query = MembershipApplication::whereIn('status', $activeStatuses);

        if (! empty($data['email'])) {
            $byEmail = (clone $query)->where('email', $data['email'])->exists();
            if ($byEmail) {
                throw ValidationException::withMessages([
                    'email' => ['An active membership application already exists for this email.'],
                ]);
            }
        }

        if (! empty($data['mobile'])) {
            $byMobile = (clone $query)->where('mobile', $data['mobile'])->exists();
            if ($byMobile) {
                throw ValidationException::withMessages([
                    'mobile' => ['An active membership application already exists for this mobile number.'],
                ]);
            }
        }
    }

    /**
     * Resolve or create the user account for the approved applicant.
     *
     * Strategy:
     * - If a user exists with matching email or mobile AND is the same person,
     *   activate/update that user.
     * - If a user exists with the same email but is a different identity,
     *   throw a controlled error for manual admin resolution.
     * - If no user exists, create one with a random unusable password.
     *   The applicant receives a password reset link to set their own password.
     *
     * @throws ValidationException
     */
    private function resolveUserForApplicant(MembershipApplication $application): User
    {
        $email  = $application->email;
        $mobile = $application->mobile;

        // Try to find by email first (more reliable identifier)
        $existingByEmail  = $email  ? User::where('email', $email)->first()  : null;
        $existingByMobile = $mobile ? User::where('phone', $mobile)->first() : null;

        // Conflict: two different users found for same applicant — needs manual resolution
        if (
            $existingByEmail && $existingByMobile &&
            $existingByEmail->id !== $existingByMobile->id
        ) {
            throw ValidationException::withMessages([
                'user_conflict' => [
                    'Conflicting user accounts found for this applicant\'s email and mobile. '.
                    'Please resolve manually before approving.',
                ],
            ]);
        }

        $existing = $existingByEmail ?? $existingByMobile;

        if ($existing) {
            // Activate and update the existing user to reflect approval
            $existing->update([
                'name'      => $application->full_name,
                'status'    => UserStatus::Active,
                'role_type' => 'member',
                // Conditionally set phone if not already set
                'phone' => $existing->phone ?? $mobile,
            ]);

            return $existing->refresh();
        }

        // No existing user — create a new one with an unusable random password.
        // Password reset link is dispatched after approval (outside transaction).
        return User::create([
            'name'      => $application->full_name,
            'email'     => $email,
            'phone'     => $mobile,
            'password'  => Hash::make(Str::random(64)), // random, not usable until reset
            'status'    => UserStatus::Active,
            'role_type' => 'member',
        ]);
    }

    /**
     * Create the Member profile from the application data.
     */
    private function createMember(MembershipApplication $application, User $user, User $admin): Member
    {
        return Member::create([
            'uuid'                       => (string) Str::uuid(),
            'user_id'                    => $user->id,
            'membership_application_id'  => $application->id,
            'member_no'                  => $this->generateMemberNo(),
            'full_name'                  => $application->full_name,
            'email'                      => $application->email,
            'mobile'                     => $application->mobile,
            'photo'                      => $application->photo,
            'gender'                     => $application->gender,
            'date_of_birth'              => $application->date_of_birth,
            'blood_group'                => $application->blood_group,
            'father_name'                => $application->father_name,
            'mother_name'                => $application->mother_name,
            'educational_institution'    => $application->educational_institution,
            'department'                 => $application->department,
            'academic_year'              => $application->academic_year,
            'occupation'                 => $application->occupation,
            'address_line'               => $application->address_line,
            'village_area'               => $application->village_area,
            'post_office'                => $application->post_office,
            'union_name'                 => $application->union_name,
            'upazila_name'               => $application->upazila_name,
            'district_name'              => $application->district_name,
            'division_name'              => $application->division_name,
            'emergency_contact_name'     => $application->emergency_contact_name,
            'emergency_contact_phone'    => $application->emergency_contact_phone,
            'status'                     => MemberStatus::Active,
            'joined_at'                  => now(),
            'approved_at'                => now(),
            'created_by'                 => $admin->id,
        ]);
    }

    /**
     * Dispatch a password reset/setup link to the newly created user.
     * Wrapped in try/catch — mail failure must not roll back the approval.
     */
    private function dispatchPasswordSetupLink(User $user): void
    {
        if (! $user->email) {
            // Phone-only applicants cannot receive link via email — skip silently
            return;
        }

        try {
            Password::broker()->sendResetLink(['email' => $user->email]);
        } catch (\Throwable $e) {
            Log::error('Password setup link failed for user '.$user->id.': '.$e->getMessage());
        }
    }

    /**
     * Insert a record into application_status_histories.
     */
    private function recordHistory(
        MembershipApplication $application,
        ?string $old,
        string $new,
        ?int $changedById,
        ?string $note,
    ): void {
        ApplicationStatusHistory::create([
            'membership_application_id' => $application->id,
            'old_status'                => $old,
            'new_status'                => $new,
            'changed_by'                => $changedById,
            'note'                      => $note,
            'created_at'                => now(),
        ]);
    }

    /**
     * Generate unique application number: SMN-{YEAR}-{PADDED_SEQ}
     */
    private function generateApplicationNo(): string
    {
        $year = now()->year;
        $last = MembershipApplication::withTrashed()
            ->where('application_no', 'like', "SMN-{$year}-%")
            ->count();

        return sprintf('SMN-%d-%06d', $year, $last + 1);
    }

    /**
     * Generate unique member number: MEM-{YEAR}-{PADDED_SEQ}
     */
    private function generateMemberNo(): string
    {
        $year = now()->year;
        $last = Member::withTrashed()
            ->where('member_no', 'like', "MEM-{$year}-%")
            ->count();

        return sprintf('MEM-%d-%06d', $year, $last + 1);
    }

    // ─── Notification helpers ─────────────────────────────────────────────────

    private function sendNotification(MembershipApplication $application, string $type): void
    {
        // Determine the contact target (email or phone — email only supported for now)
        if (! $application->email) {
            return; // Phone-only applicants — no email notification
        }

        try {
            // Wrap application in an anonymous Notifiable for one-shot email dispatch
            \Illuminate\Support\Facades\Notification::route('mail', $application->email)
                ->notify(
                    match ($type) {
                        'received' => new ApplicationReceivedNotification($application),
                        'rejected' => new ApplicationRejectedNotification($application),
                        default    => throw new \InvalidArgumentException("Unknown notification type: {$type}"),
                    }
                );
        } catch (\Throwable $e) {
            Log::error("Membership notification [{$type}] failed for application {$application->application_no}: ".$e->getMessage());
        }
    }

    private function sendApprovalNotification(MembershipApplication $application, Member $member): void
    {
        if (! $application->email) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Notification::route('mail', $application->email)
                ->notify(new ApplicationApprovedNotification($application, $member));
        } catch (\Throwable $e) {
            Log::error("Approval notification failed for application {$application->application_no}: ".$e->getMessage());
        }
    }
}
