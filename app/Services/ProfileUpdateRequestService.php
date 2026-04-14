<?php

namespace App\Services;

use App\Enum\ProfileUpdateRequestStatus;
use App\Models\ProfileUpdateRequest;
use App\Models\ProfileUpdateRequestHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProfileUpdateRequestService
{
    public function __construct(private readonly SelfProfileService $selfProfileService)
    {
    }

    public function listMine(User $user, array $filters): LengthAwarePaginator
    {
        $query = ProfileUpdateRequest::query()
            ->with(['member'])
            ->where('user_id', $user->id);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['request_type'])) {
            $query->where('request_type', $filters['request_type']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function create(User $user, array $data): ProfileUpdateRequest
    {
        $member = $user->member;

        $existingPending = ProfileUpdateRequest::query()
            ->where('user_id', $user->id)
            ->where('request_type', $data['request_type'])
            ->where('status', ProfileUpdateRequestStatus::Pending)
            ->latest('id')
            ->first();

        if ($existingPending && $this->normalizeChanges($existingPending->requested_changes) === $this->normalizeChanges($data['requested_changes'])) {
            throw ValidationException::withMessages([
                'requested_changes' => ['An identical pending request already exists.'],
            ]);
        }

        $request = ProfileUpdateRequest::create([
            'request_no' => $this->generateRequestNo(),
            'user_id' => $user->id,
            'member_id' => $member?->id,
            'request_type' => $data['request_type'],
            'requested_changes' => $data['requested_changes'],
            'status' => ProfileUpdateRequestStatus::Pending,
            'submitted_note' => $data['submitted_note'] ?? null,
        ]);

        $this->addHistory($request, null, ProfileUpdateRequestStatus::Pending, $user->id, 'Request submitted by member.');

        Log::info('Profile update request created', [
            'request_id' => $request->id,
            'request_no' => $request->request_no,
            'user_id' => $user->id,
        ]);

        return $request->fresh(['user', 'member', 'histories.changedByUser']);
    }

    public function detailMine(User $user, int $id): ProfileUpdateRequest
    {
        return ProfileUpdateRequest::query()
            ->with(['user', 'member', 'reviewer', 'histories.changedByUser'])
            ->where('user_id', $user->id)
            ->findOrFail($id);
    }

    public function cancel(User $user, int $id, ?string $note = null): ProfileUpdateRequest
    {
        return DB::transaction(function () use ($user, $id, $note) {
            $request = ProfileUpdateRequest::query()->where('user_id', $user->id)->findOrFail($id);

            if ($request->status !== ProfileUpdateRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => ['Only pending requests can be cancelled.'],
                ]);
            }

            $old = $request->status;
            $request->update([
                'status' => ProfileUpdateRequestStatus::Cancelled,
            ]);

            $this->addHistory($request, $old, ProfileUpdateRequestStatus::Cancelled, $user->id, $note ?: 'Request cancelled by requester.');

            return $request->fresh(['user', 'member', 'reviewer', 'histories.changedByUser']);
        });
    }

    public function adminList(array $filters): LengthAwarePaginator
    {
        $query = ProfileUpdateRequest::query()->with(['user', 'member', 'reviewer']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('request_no', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('member', fn ($mq) => $mq->where('member_no', 'like', "%{$search}%"));
            });
        }

        foreach (['status', 'request_type'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function adminDetail(int $id): ProfileUpdateRequest
    {
        return ProfileUpdateRequest::query()
            ->with(['user', 'member', 'reviewer', 'histories.changedByUser'])
            ->findOrFail($id);
    }

    public function approve(int $id, int $reviewerId, ?string $note = null): ProfileUpdateRequest
    {
        return DB::transaction(function () use ($id, $reviewerId, $note) {
            $request = ProfileUpdateRequest::query()->with(['user', 'member'])->lockForUpdate()->findOrFail($id);

            if ($request->status !== ProfileUpdateRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => ['Only pending requests can be approved.'],
                ]);
            }

            $this->applyApprovedChanges($request, $reviewerId);

            $old = $request->status;
            $request->update([
                'status' => ProfileUpdateRequestStatus::Approved,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $this->addHistory($request, $old, ProfileUpdateRequestStatus::Approved, $reviewerId, $note ?: 'Request approved.');

            Log::info('Profile update request approved', [
                'request_id' => $request->id,
                'reviewer_id' => $reviewerId,
            ]);

            return $request->fresh(['user', 'member', 'reviewer', 'histories.changedByUser']);
        });
    }

    public function reject(int $id, int $reviewerId, string $rejectionReason, ?string $note = null): ProfileUpdateRequest
    {
        return DB::transaction(function () use ($id, $reviewerId, $rejectionReason, $note) {
            $request = ProfileUpdateRequest::query()->lockForUpdate()->findOrFail($id);

            if ($request->status !== ProfileUpdateRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => ['Only pending requests can be rejected.'],
                ]);
            }

            $old = $request->status;
            $request->update([
                'status' => ProfileUpdateRequestStatus::Rejected,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);

            $this->addHistory($request, $old, ProfileUpdateRequestStatus::Rejected, $reviewerId, $note ?: $rejectionReason);

            Log::info('Profile update request rejected', [
                'request_id' => $request->id,
                'reviewer_id' => $reviewerId,
            ]);

            return $request->fresh(['user', 'member', 'reviewer', 'histories.changedByUser']);
        });
    }

    private function applyApprovedChanges(ProfileUpdateRequest $request, int $reviewerId): void
    {
        $changes = Arr::only($request->requested_changes ?? [], [
            'name',
            'phone',
            'full_name',
            'address_line',
            'village_area',
            'post_office',
            'emergency_contact_name',
            'emergency_contact_phone',
            'bio',
            'educational_institution',
            'department',
            'academic_year',
            'occupation',
        ]);

        if (array_key_exists('email', $request->requested_changes ?? [])) {
            $requestedEmail = $request->requested_changes['email'];
            if ($requestedEmail && $request->user->email !== $requestedEmail) {
                $exists = User::query()->where('email', $requestedEmail)->where('id', '!=', $request->user_id)->exists();
                if ($exists) {
                    throw ValidationException::withMessages([
                        'email' => ['Requested email is already taken by another account.'],
                    ]);
                }

                $request->user->update(['email' => $requestedEmail]);
            }
        }

        if (! empty($changes)) {
            $this->selfProfileService->updateMyProfile($request->user, $changes);
        }

        if (array_key_exists('photo_path', $request->requested_changes ?? [])) {
            // Future-safe placeholder: secure attachment-based photo review flow.
        }

        if ($request->member) {
            $request->member->update(['updated_by' => $reviewerId]);
        }
    }

    private function generateRequestNo(): string
    {
        $year = now()->year;
        $last = ProfileUpdateRequest::withTrashed()
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('request_no');

        $next = 1;
        if ($last && preg_match('/PUR-\d{4}-(\d{6})/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return sprintf('PUR-%d-%06d', $year, $next);
    }

    private function addHistory(
        ProfileUpdateRequest $request,
        ?ProfileUpdateRequestStatus $old,
        ProfileUpdateRequestStatus $new,
        ?int $actorId,
        ?string $note
    ): void {
        ProfileUpdateRequestHistory::create([
            'profile_update_request_id' => $request->id,
            'old_status' => $old,
            'new_status' => $new,
            'changed_by' => $actorId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    private function normalizeChanges(array $changes): array
    {
        ksort($changes);

        return $changes;
    }
}
