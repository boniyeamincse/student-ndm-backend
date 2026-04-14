<?php

namespace App\Services;

use App\Enum\MemberStatus;
use App\Enum\UserStatus;
use App\Http\Requests\MemberListRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Requests\UpdateMemberStatusRequest;
use App\Models\Member;
use App\Models\MemberStatusHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MemberService
{
    // ─── Listing ─────────────────────────────────────────────────────────────

    public function list(MemberListRequest $request): LengthAwarePaginator
    {
        $query = Member::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('member_no', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        if ($division = $request->input('division')) {
            $query->where('division_name', 'like', "%{$division}%");
        }

        if ($district = $request->input('district')) {
            $query->where('district_name', 'like', "%{$district}%");
        }

        if ($upazila = $request->input('upazila')) {
            $query->where('upazila_name', 'like', "%{$upazila}%");
        }

        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = (int) $request->input('per_page', 20);

        return $query->paginate($perPage);
    }

    // ─── Detail ───────────────────────────────────────────────────────────────

    public function detail(int|string $id): Member
    {
        return Member::with([
            'statusHistories.changedByUser',
            'user',
            'application',
        ])->findOrFail($id);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Member $member, UpdateMemberRequest $request, int $adminId): Member
    {
        $data = $request->safe()->except(['photo']);

        if ($request->hasFile('photo')) {
            // Remove old photo if it exists and is stored locally
            if ($member->photo) {
                Storage::disk('public')->delete($member->photo);
            }
            $data['photo'] = $request->file('photo')->store('members/photos', 'public');
        }

        $data['updated_by'] = $adminId;

        $member->update($data);

        // Sync email/name to the linked user account if they changed
        if ($member->user && (isset($data['full_name']) || isset($data['email']))) {
            $userUpdates = [];
            if (isset($data['full_name'])) {
                $userUpdates['name'] = $data['full_name'];
            }
            if (isset($data['email']) && $data['email'] !== $member->user->email) {
                $userUpdates['email'] = $data['email'];
            }
            if ($userUpdates) {
                $member->user->update($userUpdates);
            }
        }

        return $member->fresh(['statusHistories.changedByUser', 'user']);
    }

    // ─── Status update ────────────────────────────────────────────────────────

    public function updateStatus(Member $member, UpdateMemberStatusRequest $request, int $adminId): Member
    {
        $newStatus = MemberStatus::from($request->input('status'));
        $oldStatus = $member->status;

        if ($oldStatus === $newStatus) {
            throw new \InvalidArgumentException(
                "Member is already in [{$newStatus->label()}] status."
            );
        }

        DB::transaction(function () use ($member, $newStatus, $oldStatus, $request, $adminId) {
            $now  = now();
            $note = $request->input('note');

            $member->update([
                'status'                 => $newStatus,
                'status_note'            => $note,
                'last_status_changed_at' => $now,
                'updated_by'             => $adminId,
            ]);

            MemberStatusHistory::create([
                'member_id'  => $member->id,
                'old_status' => $oldStatus?->value,
                'new_status' => $newStatus->value,
                'changed_by' => $adminId,
                'note'       => $note,
                'created_at' => $now,
            ]);

            // Optionally revoke access / deactivate user account
            if ($request->boolean('revoke_access') && $member->user) {
                $member->user->update(['status' => UserStatus::Inactive]);
                $member->user->tokens()->delete();
            }
        });

        return $member->fresh(['statusHistories.changedByUser', 'user']);
    }

    // ─── Summary ──────────────────────────────────────────────────────────────

    public function summary(bool $withDivision = false): array
    {
        $total = Member::count();

        $byStatus = Member::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $byGender = Member::query()
            ->selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();

        $summary = compact('total', 'byStatus', 'byGender');

        if ($withDivision) {
            $summary['byDivision'] = Member::query()
                ->selectRaw('division_name, COUNT(*) as count')
                ->groupBy('division_name')
                ->orderByDesc('count')
                ->pluck('count', 'division_name')
                ->toArray();
        }

        return $summary;
    }
}
