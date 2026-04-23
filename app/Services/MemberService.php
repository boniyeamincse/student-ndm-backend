<?php

namespace App\Services;

use App\Enum\MemberStatus;
use App\Enum\UserStatus;
use App\Http\Requests\MemberListRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Requests\UpdateMemberStatusRequest;
use App\Models\District;
use App\Models\Division;
use App\Models\Member;
use App\Models\MemberStatusHistory;
use App\Models\Union;
use App\Models\Upazila;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MemberService
{
    // ─── Listing ─────────────────────────────────────────────────────────────

    public function list(MemberListRequest $request): LengthAwarePaginator
    {
        $query = Member::query()->with([
            'division:id,name_en,name_bn',
            'district:id,name_en,name_bn',
            'upazila:id,name_en,name_bn',
            'union:id,name_en,name_bn',
            'user:id,status',
            'committeeAssignments' => fn ($q) => $q
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->orderByDesc('id')
                ->with([
                    'committee:id,name',
                    'position:id,name',
                ]),
        ]);

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

        if ($request->has('promoted')) {
            $query->where('is_promoted', $request->boolean('promoted'));
        }

        if ($request->boolean('leadership_only')) {
            $query->whereHas('committeeAssignments');
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        if ($institution = $request->input('educational_institution')) {
            $query->where('educational_institution', 'like', "%{$institution}%");
        }

        if ($divisionId = $request->input('division_id')) {
            $query->where('division_id', $divisionId);
        }

        if ($division = $request->input('division')) {
            $query->whereHas('division', function ($q) use ($division) {
                $q->where('name_en', 'like', "%{$division}%")
                    ->orWhere('name_bn', 'like', "%{$division}%");
            });
        }

        if ($districtId = $request->input('district_id')) {
            $query->where('district_id', $districtId);
        }

        if ($district = $request->input('district')) {
            $query->whereHas('district', function ($q) use ($district) {
                $q->where('name_en', 'like', "%{$district}%")
                    ->orWhere('name_bn', 'like', "%{$district}%");
            });
        }

        if ($upazilaId = $request->input('upazila_id')) {
            $query->where('upazila_id', $upazilaId);
        }

        if ($upazila = $request->input('upazila')) {
            $query->whereHas('upazila', function ($q) use ($upazila) {
                $q->where('name_en', 'like', "%{$upazila}%")
                    ->orWhere('name_bn', 'like', "%{$upazila}%");
            });
        }

        if ($unionId = $request->input('union_id')) {
            $query->where('union_id', $unionId);
        }

        if ($unionName = $request->input('union_name')) {
            $query->whereHas('union', function ($q) use ($unionName) {
                $q->where('name_en', 'like', "%{$unionName}%")
                    ->orWhere('name_bn', 'like', "%{$unionName}%");
            });
        }

        if ($joinedFrom = $request->input('joined_from')) {
            $query->whereDate('joined_at', '>=', $joinedFrom);
        }

        if ($joinedTo = $request->input('joined_to')) {
            $query->whereDate('joined_at', '<=', $joinedTo);
        }

        if ($recentPeriodDays = $request->input('recent_period_days')) {
            $query->where('joined_at', '>=', now()->subDays((int) $recentPeriodDays));
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
            'division:id,name_en,name_bn',
            'district:id,name_en,name_bn',
            'upazila:id,name_en,name_bn',
            'union:id,name_en,name_bn',
            'committeeAssignments.committee',
            'committeeAssignments.position',
        ])->findOrFail($id);
    }

    // ─── Create ──────────────────────────────────────────────────────────────

    public function create(array $data, int $adminId): Member
    {
        $data = array_filter($data, fn ($value) => ! is_null($value) && $value !== '');

        // Extract names if not IDs
        $divisionName = $data['division_name'] ?? null;
        $districtName = $data['district_name'] ?? null;
        $upazilaName = $data['upazila_name'] ?? null;
        $unionName = $data['union_name'] ?? null;

        $this->applyGeoNamesToIds([
            'division_name' => $divisionName,
            'district_name' => $districtName,
            'upazila_name' => $upazilaName,
            'union_name' => $unionName,
        ], $data);

        // Handle photo upload
        if (isset($data['photo']) && is_object($data['photo'])) {
            $data['photo'] = $data['photo']->store('members/photos', 'public');
        } else {
            unset($data['photo']);
        }

        // Generate unique member number
        $data['member_no'] = $this->generateMemberNo();
        $data['status'] = MemberStatus::Active->value;
        $data['joined_at'] = $data['joined_at'] ?? now();
        $data['created_by'] = $adminId;
        $data['updated_by'] = $adminId;

        // Remove names since we converted them to IDs
        unset($data['division_name'], $data['district_name'], $data['upazila_name'], $data['union_name']);

        return Member::create($data);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Member $member, UpdateMemberRequest $request, int $adminId): Member
    {
        $data = $request->safe()->except([
            'photo',
            'division_name',
            'district_name',
            'upazila_name',
            'union_name',
        ]);

        $this->applyGeoNamesToIds($request, $data);

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

        return $member->fresh([
            'statusHistories.changedByUser',
            'user',
            'division',
            'district',
            'upazila',
            'union',
        ]);
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

        $leadership = Member::whereHas('committeeAssignments')->count();

        $summary = compact('total', 'byStatus', 'byGender', 'leadership');

        if ($withDivision) {
            $summary['byDivision'] = Member::query()
                ->selectRaw('division_id, COUNT(*) as count')
                ->whereNotNull('division_id')
                ->groupBy('division_id')
                ->orderByDesc('count')
                ->pluck('count', 'division_id')
                ->toArray();
        }

        return $summary;
    }

    private function applyGeoNamesToIds(UpdateMemberRequest|array $requestOrData, array &$data): void
    {
        if ($requestOrData instanceof UpdateMemberRequest) {
            $request = $requestOrData;
            if (empty($data['division_id']) && $request->filled('division_name')) {
                $name = trim((string) $request->input('division_name'));
                $data['division_id'] = Division::query()
                    ->where('name_en', $name)
                    ->orWhere('name_bn', $name)
                    ->value('id');
            }

            if (empty($data['district_id']) && $request->filled('district_name')) {
                $name = trim((string) $request->input('district_name'));
                $data['district_id'] = District::query()
                    ->where('name_en', $name)
                    ->orWhere('name_bn', $name)
                    ->value('id');
            }

            if (empty($data['upazila_id']) && $request->filled('upazila_name')) {
                $name = trim((string) $request->input('upazila_name'));
                $data['upazila_id'] = Upazila::query()
                    ->where('name_en', $name)
                    ->orWhere('name_bn', $name)
                    ->value('id');
            }

            if (empty($data['union_id']) && $request->filled('union_name')) {
                $name = trim((string) $request->input('union_name'));
                $data['union_id'] = Union::query()
                    ->where('name_en', $name)
                    ->orWhere('name_bn', $name)
                    ->value('id');
            }
        } else {
            // Handle array input
            if (empty($data['division_id']) && ! empty($requestOrData['division_name'])) {
                $name = trim((string) $requestOrData['division_name']);
                $data['division_id'] = Division::query()
                    ->where('name_en', $name)
                    ->orWhere('name_bn', $name)
                    ->value('id');
            }

            if (empty($data['district_id']) && ! empty($requestOrData['district_name'])) {
                $name = trim((string) $requestOrData['district_name']);
                $data['district_id'] = District::query()
                    ->where('name_en', $name)
                    ->orWhere('name_bn', $name)
                    ->value('id');
            }

            if (empty($data['upazila_id']) && ! empty($requestOrData['upazila_name'])) {
                $name = trim((string) $requestOrData['upazila_name']);
                $data['upazila_id'] = Upazila::query()
                    ->where('name_en', $name)
                    ->orWhere('name_bn', $name)
                    ->value('id');
            }

            if (empty($data['union_id']) && ! empty($requestOrData['union_name'])) {
                $name = trim((string) $requestOrData['union_name']);
                $data['union_id'] = Union::query()
                    ->where('name_en', $name)
                    ->orWhere('name_bn', $name)
                    ->value('id');
            }
        }
    }

    private function generateMemberNo(): string
    {
        $year = now()->year;
        $sequence = Member::whereYear('created_at', $year)->count() + 1;
        
        return sprintf('MEM-%d-%06d', $year, $sequence);
    }
}
