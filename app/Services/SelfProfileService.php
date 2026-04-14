<?php

namespace App\Services;

use App\Models\CommitteeMemberAssignment;
use App\Models\Member;
use App\Models\MemberReportingRelation;
use App\Models\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SelfProfileService
{
    public function __construct(private readonly AccountSettingService $accountSettingService)
    {
    }

    public function getMyProfile(User $user): array
    {
        $user->loadMissing('roles', 'member');
        $member = $user->member;
        $settings = $this->accountSettingService->getOrCreate($user);

        $activeAssignmentsCount = 0;
        $leadershipAssignmentsCount = 0;
        $subordinatesCount = 0;

        if ($member) {
            $activeAssignmentsCount = CommitteeMemberAssignment::query()
                ->where('member_id', $member->id)
                ->where('is_active', true)
                ->count();

            $leadershipAssignmentsCount = CommitteeMemberAssignment::query()
                ->where('member_id', $member->id)
                ->where('is_active', true)
                ->where('is_leadership', true)
                ->count();

            $myLeadershipAssignmentIds = CommitteeMemberAssignment::query()
                ->where('member_id', $member->id)
                ->where('is_active', true)
                ->where('is_leadership', true)
                ->pluck('id');

            if ($myLeadershipAssignmentIds->isNotEmpty()) {
                $subordinatesCount = MemberReportingRelation::query()
                    ->whereIn('superior_assignment_id', $myLeadershipAssignmentIds)
                    ->where('is_active', true)
                    ->count();
            }
        }

        $pendingRequestCount = ProfileUpdateRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        return [
            'user' => $user,
            'member' => $member,
            'settings' => $settings,
            'active_assignments_count' => $activeAssignmentsCount,
            'leadership_assignments_count' => $leadershipAssignmentsCount,
            'subordinates_count' => $subordinatesCount,
            'pending_profile_requests_count' => $pendingRequestCount,
        ];
    }

    public function updateMyProfile(User $user, array $data): array
    {
        $restricted = ['member_no', 'status', 'committee_id', 'position_id', 'hierarchy'];
        foreach ($restricted as $field) {
            if (array_key_exists($field, $data)) {
                throw ValidationException::withMessages([
                    $field => ['This field cannot be updated directly. Please submit a profile update request.'],
                ]);
            }
        }

        if (array_key_exists('email', $data) && ! empty($data['email']) && $data['email'] !== $user->email) {
            throw ValidationException::withMessages([
                'email' => ['Direct email change is restricted. Please submit a profile update request.'],
            ]);
        }

        return DB::transaction(function () use ($user, $data) {
            $member = $user->member;

            $userPayload = [];
            if (array_key_exists('name', $data)) {
                $userPayload['name'] = trim((string) $data['name']);
            }
            if (array_key_exists('phone', $data)) {
                $userPayload['phone'] = $data['phone'] ? trim((string) $data['phone']) : null;
            }

            if (! empty($userPayload)) {
                $user->update($userPayload);
            }

            if ($member) {
                $memberPayload = [];

                if (array_key_exists('full_name', $data)) {
                    $memberPayload['full_name'] = trim((string) $data['full_name']);
                } elseif (array_key_exists('name', $data)) {
                    $memberPayload['full_name'] = trim((string) $data['name']);
                }

                if (array_key_exists('phone', $data)) {
                    $memberPayload['mobile'] = $data['phone'] ? trim((string) $data['phone']) : null;
                }

                foreach (['address_line', 'village_area', 'post_office', 'emergency_contact_name', 'emergency_contact_phone', 'bio', 'educational_institution', 'department', 'academic_year', 'occupation'] as $f) {
                    if (array_key_exists($f, $data)) {
                        $memberPayload[$f] = $data[$f];
                    }
                }

                if (! empty($memberPayload)) {
                    $memberPayload['updated_by'] = $user->id;
                    $member->update($memberPayload);
                }
            }

            Log::info('Self profile updated', [
                'user_id' => $user->id,
                'member_id' => $member?->id,
                'fields' => array_keys($data),
            ]);

            return $this->getMyProfile($user->fresh(['member', 'roles']));
        });
    }

    public function updatePhoto(User $user, UploadedFile $photo): array
    {
        $member = $user->member;

        // Profile photo source-of-truth policy:
        // - If a linked member exists, store on members.photo for organization identity consistency.
        // - Otherwise store on users.profile_photo for account-only users.
        if ($member) {
            if ($member->photo && Storage::disk('public')->exists($member->photo)) {
                Storage::disk('public')->delete($member->photo);
            }

            $path = $photo->store('profiles/photos', 'public');
            $member->update(['photo' => $path, 'updated_by' => $user->id]);

            return [
                'photo_url' => asset('storage/'.$path),
                'photo_source' => 'member',
            ];
        }

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $photo->store('profiles/photos', 'public');
        $user->update(['profile_photo' => $path]);

        return [
            'photo_url' => asset('storage/'.$path),
            'photo_source' => 'user',
        ];
    }

    public function memberOverview(User $user): ?Member
    {
        $member = $user->member;
        if (! $member) {
            return null;
        }

        $activeAssignmentsCount = CommitteeMemberAssignment::query()
            ->where('member_id', $member->id)
            ->where('is_active', true)
            ->count();

        $leadershipAssignmentsCount = CommitteeMemberAssignment::query()
            ->where('member_id', $member->id)
            ->where('is_active', true)
            ->where('is_leadership', true)
            ->count();

        $latestAssignment = CommitteeMemberAssignment::query()
            ->with(['committee', 'position'])
            ->where('member_id', $member->id)
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->first();

        $leaderRelation = null;
        if ($latestAssignment) {
            $leaderRelation = MemberReportingRelation::query()
                ->with(['superiorAssignment.member', 'superiorAssignment.position'])
                ->where('subordinate_assignment_id', $latestAssignment->id)
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->first();
        }

        $leader = $leaderRelation ? [
            'member_id' => $leaderRelation->superiorAssignment?->member?->id,
            'member_no' => $leaderRelation->superiorAssignment?->member?->member_no,
            'full_name' => $leaderRelation->superiorAssignment?->member?->full_name,
            'position_name' => $leaderRelation->superiorAssignment?->position?->name,
        ] : null;

        $member->active_assignments_count = $activeAssignmentsCount;
        $member->leadership_assignments_count = $leadershipAssignmentsCount;
        $member->setRelation('latestAssignment', $latestAssignment);
        $member->leader = $leader;

        return $member;
    }

    public function committeeAssignments(User $user, array $filters): LengthAwarePaginator
    {
        $member = $user->member;
        $perPage = (int) ($filters['per_page'] ?? 20);
        if (! $member) {
            return $this->emptyPaginator($perPage);
        }

        $query = CommitteeMemberAssignment::query()
            ->with(['committee.committeeType', 'position'])
            ->where('member_id', $member->id);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (($filters['active_only'] ?? true) === true) {
            $query->where('is_active', true);
        }

        if (($filters['leadership_only'] ?? false) === true) {
            $query->where('is_leadership', true);
        }

        $sortBy = $filters['sort_by'] ?? 'latest';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        if ($sortBy === 'hierarchy') {
            $query
                ->leftJoin('positions', 'positions.id', '=', 'committee_member_assignments.position_id')
                ->select('committee_member_assignments.*')
                ->orderByRaw('positions.hierarchy_rank IS NULL ASC')
                ->orderBy('positions.hierarchy_rank', $sortDir);
        } elseif ($sortBy === 'start_date') {
            $query->orderBy('start_date', $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    public function leader(User $user, ?int $assignmentId = null): ?MemberReportingRelation
    {
        $member = $user->member;
        if (! $member) {
            return null;
        }

        $assignment = $assignmentId
            ? CommitteeMemberAssignment::query()->where('member_id', $member->id)->find($assignmentId)
            : CommitteeMemberAssignment::query()->where('member_id', $member->id)->where('is_active', true)->orderByDesc('is_primary')->first();

        if (! $assignment) {
            return null;
        }

        return MemberReportingRelation::query()
            ->with(['committee', 'superiorAssignment.member', 'superiorAssignment.position'])
            ->where('subordinate_assignment_id', $assignment->id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->first();
    }

    public function subordinates(User $user, array $filters): LengthAwarePaginator
    {
        $member = $user->member;
        $perPage = (int) ($filters['per_page'] ?? 20);
        if (! $member) {
            return $this->emptyPaginator($perPage);
        }

        $assignmentIdsQuery = CommitteeMemberAssignment::query()
            ->where('member_id', $member->id)
            ->where('is_active', true)
            ->where('is_leadership', true);

        if (! empty($filters['assignment_id'])) {
            $assignmentIdsQuery->where('id', (int) $filters['assignment_id']);
        }

        $assignmentIds = $assignmentIdsQuery->pluck('id');
        if ($assignmentIds->isEmpty()) {
            return $this->emptyPaginator($perPage);
        }

        $query = MemberReportingRelation::query()
            ->with(['committee', 'subordinateAssignment.member', 'subordinateAssignment.position'])
            ->whereIn('superior_assignment_id', $assignmentIds);

        if (($filters['active_only'] ?? true) === true) {
            $query->where('is_active', true);
        }

        if (! empty($filters['relation_type'])) {
            $query->where('relation_type', $filters['relation_type']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('subordinateAssignment.member', function (Builder $q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('member_no', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        if ($sortBy === 'member_name') {
            $query
                ->join('committee_member_assignments as sub_a', 'sub_a.id', '=', 'member_reporting_relations.subordinate_assignment_id')
                ->join('members as sub_m', 'sub_m.id', '=', 'sub_a.member_id')
                ->select('member_reporting_relations.*')
                ->orderBy('sub_m.full_name', $sortDir);
        } else {
            $query->orderBy('created_at', $sortDir);
        }

        return $query->paginate($perPage);
    }

    public function summary(User $user): array
    {
        $profile = $this->getMyProfile($user);
        $member = $profile['member'];

        $mainAssignment = null;
        if ($member) {
            $mainAssignment = CommitteeMemberAssignment::query()
                ->with(['committee', 'position'])
                ->where('member_id', $member->id)
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->first();
        }

        $leader = $this->leader($user, $mainAssignment?->id);

        return [
            'name' => $profile['user']->name,
            'photo_url' => $member?->photo ? asset('storage/'.$member->photo) : ($profile['user']->profile_photo ? asset('storage/'.$profile['user']->profile_photo) : null),
            'member_no' => $member?->member_no,
            'primary_role' => $profile['user']->getRoleNames()->first(),
            'main_committee' => $mainAssignment?->committee?->name,
            'primary_position' => $mainAssignment?->position?->name,
            'leader_name' => $leader?->superiorAssignment?->member?->full_name,
            'subordinate_count' => $profile['subordinates_count'] ?? 0,
            'pending_profile_requests_count' => $profile['pending_profile_requests_count'] ?? 0,
        ];
    }

    private function emptyPaginator(int $perPage = 20): LengthAwarePaginator
    {
        return new Paginator([], 0, $perPage, 1);
    }
}
