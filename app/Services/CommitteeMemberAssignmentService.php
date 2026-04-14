<?php

namespace App\Services;

use App\Enum\AssignmentHistoryAction;
use App\Enum\AssignmentStatus;
use App\Enum\AssignmentType;
use App\Enum\CommitteeStatus;
use App\Enum\MemberStatus;
use App\Enum\PositionScope;
use App\Models\Committee;
use App\Models\CommitteeMemberAssignment;
use App\Models\CommitteeMemberAssignmentHistory;
use App\Models\CommitteeMemberPositionHistory;
use App\Models\Member;
use App\Models\Position;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CommitteeMemberAssignmentService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = CommitteeMemberAssignment::query()
            ->with([
                'member:id,member_no,full_name,email,mobile',
                'committee:id,committee_no,name,committee_type_id,division_name,district_name,upazila_name,union_name',
                'committee.committeeType:id,name,hierarchy_order',
                'position:id,name,hierarchy_rank,display_order,is_leadership',
            ]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('assignment_no', 'like', "%{$search}%")
                  ->orWhereHas('member', fn ($mq) => $mq->where('full_name', 'like', "%{$search}%")
                      ->orWhere('member_no', 'like', "%{$search}%"))
                  ->orWhereHas('committee', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('position', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        foreach (['member_id', 'committee_id', 'position_id', 'assignment_type', 'status'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        foreach (['is_active', 'is_primary', 'is_leadership'] as $field) {
            if (array_key_exists($field, $filters) && $filters[$field] !== null) {
                $query->where($field, (bool) $filters[$field]);
            }
        }

        if (! empty($filters['committee_type_id'])) {
            $committeeTypeId = (int) $filters['committee_type_id'];
            $query->whereHas('committee', fn ($cq) => $cq->where('committee_type_id', $committeeTypeId));
        }

        foreach (['division_name', 'district_name', 'upazila_name', 'union_name'] as $locationField) {
            if (! empty($filters[$locationField])) {
                $query->whereHas('committee', fn ($cq) => $cq->where($locationField, 'like', '%'.$filters[$locationField].'%'));
            }
        }

        if (! empty($filters['start_date_from'])) {
            $query->whereDate('start_date', '>=', $filters['start_date_from']);
        }
        if (! empty($filters['start_date_to'])) {
            $query->whereDate('start_date', '<=', $filters['start_date_to']);
        }
        if (! empty($filters['end_date_from'])) {
            $query->whereDate('end_date', '>=', $filters['end_date_from']);
        }
        if (! empty($filters['end_date_to'])) {
            $query->whereDate('end_date', '<=', $filters['end_date_to']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        if ($sortBy === 'member_name') {
            $query->join('members', 'members.id', '=', 'committee_member_assignments.member_id')
                ->select('committee_member_assignments.*')
                ->orderBy('members.full_name', $sortDir);
        } elseif ($sortBy === 'committee_name') {
            $query->join('committees', 'committees.id', '=', 'committee_member_assignments.committee_id')
                ->select('committee_member_assignments.*')
                ->orderBy('committees.name', $sortDir);
        } elseif ($sortBy === 'position_rank') {
            $query->leftJoin('positions', 'positions.id', '=', 'committee_member_assignments.position_id')
                ->select('committee_member_assignments.*')
                ->orderByRaw('positions.hierarchy_rank IS NULL ASC')
                ->orderBy('positions.hierarchy_rank', $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function create(array $data, int $actorId): CommitteeMemberAssignment
    {
        return DB::transaction(function () use ($data, $actorId) {
            $member = Member::findOrFail($data['member_id']);
            $committee = Committee::with('committeeType')->findOrFail($data['committee_id']);
            $position = ! empty($data['position_id']) ? Position::with('committeeTypes')->findOrFail($data['position_id']) : null;

            $this->validateCoreCreateRules($member, $committee, $position, $data);

            $status = AssignmentStatus::from($data['status']);
            $data['is_active'] = $this->resolveActiveFlag($status, $data['is_active'] ?? null);

            $this->assertNoActiveDuplicate($member->id, $committee->id, $position?->id, null, true);

            if (! empty($data['is_primary']) && $data['is_active']) {
                $this->assertPrimaryUniquenessAtCommitteeTypeLevel($member->id, $committee->committee_type_id);
            }

            $assignment = CommitteeMemberAssignment::create([
                ...$data,
                'assignment_no' => $this->generateAssignmentNo(),
                'created_by' => $actorId,
            ]);

            $this->recordHistory(
                $assignment,
                AssignmentHistoryAction::Created,
                null,
                $assignment->only([
                    'member_id', 'committee_id', 'position_id', 'assignment_type', 'is_primary',
                    'is_leadership', 'is_active', 'status', 'start_date', 'end_date',
                ]),
                $actorId,
                'Assignment created.'
            );

            if ($position) {
                $this->recordPositionHistory($assignment, null, $position->id, $actorId, 'Initial position assigned.');
            }

            Log::info('Committee member assignment created', [
                'assignment_id' => $assignment->id,
                'assignment_no' => $assignment->assignment_no,
                'actor_id' => $actorId,
            ]);

            return $assignment->fresh($this->detailRelations());
        });
    }

    public function detail(int $id): CommitteeMemberAssignment
    {
        return CommitteeMemberAssignment::with($this->detailRelations())->findOrFail($id);
    }

    public function update(CommitteeMemberAssignment $assignment, array $data, int $actorId): CommitteeMemberAssignment
    {
        return DB::transaction(function () use ($assignment, $data, $actorId) {
            $old = $assignment->replicate();
            $committee = $assignment->committee()->with('committeeType')->firstOrFail();
            $member = $assignment->member()->firstOrFail();
            $newPosition = array_key_exists('position_id', $data)
                ? ($data['position_id'] ? Position::with('committeeTypes')->findOrFail($data['position_id']) : null)
                : $assignment->position;

            $this->validateCoreCreateRules($member, $committee, $newPosition, [
                ...$assignment->toArray(),
                ...$data,
            ], false);

            $newStatus = AssignmentStatus::from($data['status']);
            $data['is_active'] = $this->resolveActiveFlag($newStatus, $data['is_active'] ?? null);
            $data['updated_by'] = $actorId;

            if (! empty($data['is_primary']) && $data['is_active']) {
                $this->assertPrimaryUniquenessAtCommitteeTypeLevel(
                    $assignment->member_id,
                    $committee->committee_type_id,
                    $assignment->id
                );
            }

            $this->assertNoActiveDuplicate(
                $assignment->member_id,
                $assignment->committee_id,
                $newPosition?->id,
                $assignment->id,
                (bool) $data['is_active']
            );

            $assignment->update($data);

            if (($old->position_id ?? null) !== ($assignment->position_id ?? null)) {
                $this->recordPositionHistory(
                    $assignment,
                    $old->position_id,
                    $assignment->position_id,
                    $actorId,
                    'Position changed via assignment update.'
                );
                $this->recordHistory(
                    $assignment,
                    AssignmentHistoryAction::PositionChanged,
                    ['position_id' => $old->position_id],
                    ['position_id' => $assignment->position_id],
                    $actorId,
                    'Position changed.'
                );
            }

            if ((bool) $old->is_primary !== (bool) $assignment->is_primary) {
                $this->recordHistory(
                    $assignment,
                    AssignmentHistoryAction::PrimaryChanged,
                    ['is_primary' => (bool) $old->is_primary],
                    ['is_primary' => (bool) $assignment->is_primary],
                    $actorId,
                    'Primary flag changed.'
                );
            }

            if ((bool) $old->is_active !== (bool) $assignment->is_active) {
                $this->recordHistory(
                    $assignment,
                    $assignment->is_active ? AssignmentHistoryAction::Activated : AssignmentHistoryAction::Deactivated,
                    ['is_active' => (bool) $old->is_active],
                    ['is_active' => (bool) $assignment->is_active],
                    $actorId,
                    'Active state changed in update.'
                );
            }

            if (($old->status?->value ?? null) !== ($assignment->status?->value ?? null)) {
                $this->recordHistory(
                    $assignment,
                    AssignmentHistoryAction::StatusChanged,
                    ['status' => $old->status?->value],
                    ['status' => $assignment->status?->value],
                    $actorId,
                    'Status changed in update.'
                );
            }

            $this->recordHistory(
                $assignment,
                AssignmentHistoryAction::Updated,
                [
                    'assignment_type' => $old->assignment_type?->value,
                    'note' => $old->note,
                ],
                [
                    'assignment_type' => $assignment->assignment_type?->value,
                    'note' => $assignment->note,
                ],
                $actorId,
                'Assignment updated.'
            );

            Log::info('Committee member assignment updated', [
                'assignment_id' => $assignment->id,
                'actor_id' => $actorId,
            ]);

            return $assignment->fresh($this->detailRelations());
        });
    }

    public function updateStatus(CommitteeMemberAssignment $assignment, string $status, ?bool $isActive, ?string $note, int $actorId): CommitteeMemberAssignment
    {
        return DB::transaction(function () use ($assignment, $status, $isActive, $note, $actorId) {
            $targetStatus = AssignmentStatus::from($status);
            $resolvedActive = $this->resolveActiveFlag($targetStatus, $isActive);

            if ($assignment->status === $targetStatus && (bool) $assignment->is_active === $resolvedActive) {
                throw ValidationException::withMessages([
                    'status' => ['Assignment already has this status/active state.'],
                ]);
            }

            if ($resolvedActive && $assignment->is_primary) {
                $committeeTypeId = $assignment->committee()->value('committee_type_id');
                $this->assertPrimaryUniquenessAtCommitteeTypeLevel($assignment->member_id, (int) $committeeTypeId, $assignment->id);
            }

            if ($resolvedActive) {
                $this->assertNoActiveDuplicate(
                    $assignment->member_id,
                    $assignment->committee_id,
                    $assignment->position_id,
                    $assignment->id,
                    true
                );
            }

            $old = ['status' => $assignment->status?->value, 'is_active' => (bool) $assignment->is_active];

            $assignment->update([
                'status' => $targetStatus,
                'is_active' => $resolvedActive,
                'note' => $note ? trim(($assignment->note ? $assignment->note."\n" : '').$note) : $assignment->note,
                'updated_by' => $actorId,
            ]);

            $this->recordHistory(
                $assignment,
                AssignmentHistoryAction::StatusChanged,
                $old,
                ['status' => $assignment->status?->value, 'is_active' => (bool) $assignment->is_active],
                $actorId,
                $note
            );

            Log::info('Committee member assignment status updated', [
                'assignment_id' => $assignment->id,
                'actor_id' => $actorId,
                'status' => $assignment->status?->value,
                'is_active' => $assignment->is_active,
            ]);

            return $assignment->fresh($this->detailRelations());
        });
    }

    public function transfer(CommitteeMemberAssignment $assignment, array $data, int $actorId): CommitteeMemberAssignment
    {
        return DB::transaction(function () use ($assignment, $data, $actorId) {
            $targetCommittee = Committee::with('committeeType')->findOrFail($data['target_committee_id']);
            $targetPosition = ! empty($data['target_position_id'])
                ? Position::with('committeeTypes')->findOrFail($data['target_position_id'])
                : ($assignment->position_id ? Position::with('committeeTypes')->findOrFail($assignment->position_id) : null);

            if ($targetCommittee->id === $assignment->committee_id && ($targetPosition?->id ?? null) === ($assignment->position_id ?? null)) {
                throw ValidationException::withMessages([
                    'target_committee_id' => ['Transfer target cannot be identical to current assignment context.'],
                ]);
            }

            $transferMode = $data['transfer_mode'];
            $effectiveDate = $data['effective_date'] ?? now()->toDateString();
            $note = $data['note'] ?? null;
            $isPrimary = array_key_exists('is_primary', $data) ? (bool) $data['is_primary'] : (bool) $assignment->is_primary;

            $payload = [
                'member_id' => $assignment->member_id,
                'committee_id' => $targetCommittee->id,
                'position_id' => $targetPosition?->id,
                'assignment_type' => $assignment->assignment_type->value,
                'is_primary' => $isPrimary,
                'is_leadership' => $assignment->is_leadership,
                'appointed_by' => $assignment->appointed_by,
                'approved_by' => $assignment->approved_by,
                'assigned_at' => $effectiveDate,
                'approved_at' => $assignment->approved_at,
                'start_date' => $effectiveDate,
                'end_date' => null,
                'status' => AssignmentStatus::Active->value,
                'is_active' => true,
                'note' => $note,
            ];

            $member = Member::findOrFail($assignment->member_id);
            $this->validateCoreCreateRules($member, $targetCommittee, $targetPosition, $payload);
            $this->assertNoActiveDuplicate($member->id, $targetCommittee->id, $targetPosition?->id, null, true);

            if ($isPrimary) {
                $this->assertPrimaryUniquenessAtCommitteeTypeLevel($member->id, $targetCommittee->committee_type_id, null, $assignment->id);
            }

            $newAssignment = CommitteeMemberAssignment::create([
                ...$payload,
                'assignment_no' => $this->generateAssignmentNo(),
                'created_by' => $actorId,
            ]);

            if ($targetPosition) {
                $this->recordPositionHistory($newAssignment, null, $targetPosition->id, $actorId, 'Position assigned on transfer.');
            }

            if ($transferMode === 'move' && ! ($data['keep_old_assignment_active'] ?? false)) {
                $assignment->update([
                    'status' => AssignmentStatus::Completed,
                    'is_active' => false,
                    'end_date' => $effectiveDate,
                    'updated_by' => $actorId,
                ]);
            }

            $this->recordHistory(
                $assignment,
                AssignmentHistoryAction::Transferred,
                [
                    'committee_id' => $assignment->committee_id,
                    'position_id' => $assignment->position_id,
                    'status' => $assignment->status?->value,
                    'is_active' => (bool) $assignment->is_active,
                ],
                [
                    'new_assignment_id' => $newAssignment->id,
                    'target_committee_id' => $targetCommittee->id,
                    'target_position_id' => $targetPosition?->id,
                    'transfer_mode' => $transferMode,
                ],
                $actorId,
                $note
            );

            $this->recordHistory(
                $newAssignment,
                AssignmentHistoryAction::Created,
                null,
                [
                    'from_assignment_id' => $assignment->id,
                    'transfer_mode' => $transferMode,
                ],
                $actorId,
                'Created via transfer.'
            );

            Log::info('Committee member assignment transferred', [
                'from_assignment_id' => $assignment->id,
                'to_assignment_id' => $newAssignment->id,
                'transfer_mode' => $transferMode,
                'actor_id' => $actorId,
            ]);

            return $newAssignment->fresh($this->detailRelations());
        });
    }

    public function committeeMembers(int $committeeId, array $filters): LengthAwarePaginator
    {
        $query = CommitteeMemberAssignment::query()
            ->where('committee_id', $committeeId)
            ->with([
                'member:id,member_no,full_name,email,mobile,photo',
                'position:id,name,hierarchy_rank,display_order,is_leadership',
            ]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('member', fn ($mq) => $mq->where('full_name', 'like', "%{$search}%")
                ->orWhere('member_no', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        foreach (['assignment_type', 'status', 'position_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        foreach (['is_active', 'is_leadership'] as $field) {
            if (array_key_exists($field, $filters) && $filters[$field] !== null) {
                $query->where($field, (bool) $filters[$field]);
            }
        }

        $sortBy = $filters['sort_by'] ?? 'position_rank';
        $sortDir = $filters['sort_dir'] ?? 'asc';

        if ($sortBy === 'position_rank') {
            $query->leftJoin('positions', 'positions.id', '=', 'committee_member_assignments.position_id')
                ->leftJoin('members', 'members.id', '=', 'committee_member_assignments.member_id')
                ->select('committee_member_assignments.*')
                ->orderByDesc('committee_member_assignments.is_leadership')
                ->orderByRaw('positions.hierarchy_rank IS NULL ASC')
                ->orderBy('positions.hierarchy_rank', 'asc')
                ->orderBy('members.full_name', 'asc');
        } elseif ($sortBy === 'member_name') {
            $query->join('members', 'members.id', '=', 'committee_member_assignments.member_id')
                ->select('committee_member_assignments.*')
                ->orderBy('members.full_name', $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function committeeOfficeBearers(int $committeeId, array $filters): LengthAwarePaginator
    {
        $query = CommitteeMemberAssignment::query()
            ->where('committee_id', $committeeId)
            ->where(function ($q) {
                $q->where('committee_member_assignments.assignment_type', AssignmentType::OfficeBearer)
                  ->orWhere('committee_member_assignments.is_leadership', true)
                  ->orWhereHas('position', fn ($pq) => $pq->where('is_leadership', true));
            })
            ->with([
                'member:id,member_no,full_name,photo',
                'position:id,name,short_name,hierarchy_rank,display_order,is_leadership',
            ]);

        if (! ($filters['include_inactive'] ?? false)) {
            $query->where('committee_member_assignments.is_active', true);
        }

        $query->leftJoin('positions', 'positions.id', '=', 'committee_member_assignments.position_id')
            ->leftJoin('members', 'members.id', '=', 'committee_member_assignments.member_id')
            ->select('committee_member_assignments.*')
            ->orderByRaw('positions.hierarchy_rank IS NULL ASC')
            ->orderBy('positions.hierarchy_rank', 'asc')
            ->orderByRaw('positions.display_order IS NULL ASC')
            ->orderBy('positions.display_order', 'asc')
            ->orderBy('members.full_name', 'asc');

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function memberAssignments(int $memberId, array $filters): LengthAwarePaginator
    {
        $query = CommitteeMemberAssignment::query()
            ->where('member_id', $memberId)
            ->with([
                'committee:id,committee_no,name,committee_type_id',
                'committee.committeeType:id,name,hierarchy_order',
                'position:id,name,hierarchy_rank',
            ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['committee_type_id'])) {
            $committeeTypeId = (int) $filters['committee_type_id'];
            $query->whereHas('committee', fn ($cq) => $cq->where('committee_type_id', $committeeTypeId));
        }

        if (! empty($filters['leadership_only'])) {
            $query->where(function ($q) {
                $q->where('is_leadership', true)
                  ->orWhereHas('position', fn ($pq) => $pq->where('is_leadership', true));
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        if ($sortBy === 'position_rank') {
            $query->leftJoin('positions', 'positions.id', '=', 'committee_member_assignments.position_id')
                ->select('committee_member_assignments.*')
                ->orderByRaw('positions.hierarchy_rank IS NULL ASC')
                ->orderBy('positions.hierarchy_rank', $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function summary(bool $includePositionSummary = false, bool $includeCommitteeSummary = false): array
    {
        $assignmentsByStatus = CommitteeMemberAssignment::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $assignmentsByCommitteeType = DB::table('committee_member_assignments')
            ->join('committees', 'committees.id', '=', 'committee_member_assignments.committee_id')
            ->join('committee_types', 'committee_types.id', '=', 'committees.committee_type_id')
            ->selectRaw('committee_types.name as committee_type, COUNT(*) as count')
            ->groupBy('committee_types.name')
            ->pluck('count', 'committee_type')
            ->toArray();

        $summary = [
            'total_assignments' => CommitteeMemberAssignment::count(),
            'active_assignments' => (int) ($assignmentsByStatus['active'] ?? 0),
            'inactive_assignments' => (int) ($assignmentsByStatus['inactive'] ?? 0),
            'completed_assignments' => (int) ($assignmentsByStatus['completed'] ?? 0),
            'removed_assignments' => (int) ($assignmentsByStatus['removed'] ?? 0),
            'total_office_bearers' => CommitteeMemberAssignment::where('assignment_type', AssignmentType::OfficeBearer)->count(),
            'total_general_members' => CommitteeMemberAssignment::where('assignment_type', AssignmentType::GeneralMember)->count(),
            'total_leadership_assignments' => CommitteeMemberAssignment::where('is_leadership', true)->count(),
            'assignments_by_committee_type' => $assignmentsByCommitteeType,
            'assignments_by_status' => $assignmentsByStatus,
        ];

        if ($includePositionSummary) {
            $summary['assignments_by_position'] = DB::table('committee_member_assignments')
                ->leftJoin('positions', 'positions.id', '=', 'committee_member_assignments.position_id')
                ->selectRaw('COALESCE(positions.name, "Unassigned") as position_name, COUNT(*) as count')
                ->groupBy('position_name')
                ->pluck('count', 'position_name')
                ->toArray();
        }

        if ($includeCommitteeSummary) {
            $summary['assignments_by_committee'] = DB::table('committee_member_assignments')
                ->join('committees', 'committees.id', '=', 'committee_member_assignments.committee_id')
                ->selectRaw('committees.name as committee_name, COUNT(*) as count')
                ->groupBy('committees.name')
                ->pluck('count', 'committee_name')
                ->toArray();
        }

        return $summary;
    }

    public function delete(CommitteeMemberAssignment $assignment, int $actorId): void
    {
        // Future placeholder: block delete when reporting hierarchy depends on this assignment.
        $assignment->delete();

        $this->recordHistory(
            $assignment,
            AssignmentHistoryAction::Deleted,
            ['deleted_at' => null],
            ['deleted_at' => now()->toDateTimeString()],
            $actorId,
            'Assignment soft-deleted.'
        );

        Log::info('Committee member assignment deleted', [
            'assignment_id' => $assignment->id,
            'actor_id' => $actorId,
        ]);
    }

    public function restore(CommitteeMemberAssignment $assignment, int $actorId): CommitteeMemberAssignment
    {
        return DB::transaction(function () use ($assignment, $actorId) {
            $committeeTypeId = $assignment->committee()->value('committee_type_id');

            if ($assignment->is_active) {
                $this->assertNoActiveDuplicate($assignment->member_id, $assignment->committee_id, $assignment->position_id, $assignment->id, true);

                if ($assignment->is_primary) {
                    $this->assertPrimaryUniquenessAtCommitteeTypeLevel($assignment->member_id, (int) $committeeTypeId, $assignment->id);
                }
            }

            $assignment->restore();

            $this->recordHistory(
                $assignment,
                AssignmentHistoryAction::Restored,
                ['deleted_at' => now()->toDateTimeString()],
                ['deleted_at' => null],
                $actorId,
                'Assignment restored.'
            );

            Log::info('Committee member assignment restored', [
                'assignment_id' => $assignment->id,
                'actor_id' => $actorId,
            ]);

            return $assignment->fresh($this->detailRelations());
        });
    }

    private function validateCoreCreateRules(Member $member, Committee $committee, ?Position $position, array $data, bool $strictCommitteeCurrent = true): void
    {
        if (! in_array($member->status, [MemberStatus::Active], true)) {
            throw ValidationException::withMessages([
                'member_id' => ['Only active members can be assigned.'],
            ]);
        }

        if (! in_array($committee->status, [CommitteeStatus::Active], true) || ($strictCommitteeCurrent && ! $committee->is_current)) {
            throw ValidationException::withMessages([
                'committee_id' => ['Assignment is allowed only for active and current committees.'],
            ]);
        }

        if ($position) {
            if (! $position->is_active) {
                throw ValidationException::withMessages([
                    'position_id' => ['Selected position is inactive.'],
                ]);
            }

            if ($position->scope === PositionScope::CommitteeSpecific) {
                $allowed = $position->committeeTypes()->where('committee_types.id', $committee->committee_type_id)->exists();
                if (! $allowed) {
                    throw ValidationException::withMessages([
                        'position_id' => ['This position is not allowed for the selected committee type.'],
                    ]);
                }
            }
        }

        $assignmentType = AssignmentType::from($data['assignment_type']);

        if ($assignmentType === AssignmentType::OfficeBearer && empty($data['position_id'])) {
            throw ValidationException::withMessages([
                'position_id' => ['Office bearer assignment requires a position.'],
            ]);
        }

        if (! empty($data['is_leadership']) && ! $position && $assignmentType === AssignmentType::GeneralMember) {
            throw ValidationException::withMessages([
                'is_leadership' => ['General member without a leadership position cannot be marked as leadership.'],
            ]);
        }
    }

    private function resolveActiveFlag(AssignmentStatus $status, ?bool $incomingIsActive): bool
    {
        if ($incomingIsActive === null) {
            return $status->shouldBeActiveFlag();
        }

        if ($status !== AssignmentStatus::Active) {
            return false;
        }

        return (bool) $incomingIsActive;
    }

    private function assertNoActiveDuplicate(int $memberId, int $committeeId, ?int $positionId, ?int $ignoreId, bool $isActive): void
    {
        if (! $isActive) {
            return;
        }

        $query = CommitteeMemberAssignment::query()
            ->where('member_id', $memberId)
            ->where('committee_id', $committeeId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId));

        if ($positionId === null) {
            $query->whereNull('position_id')->where('assignment_type', AssignmentType::GeneralMember);
        } else {
            $query->where('position_id', $positionId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'assignment' => ['Duplicate active assignment conflict detected for this member/committee/position scope.'],
            ]);
        }
    }

    private function assertPrimaryUniquenessAtCommitteeTypeLevel(int $memberId, int $committeeTypeId, ?int $ignoreId = null, ?int $ignoreOldAssignmentId = null): void
    {
        $query = CommitteeMemberAssignment::query()
            ->join('committees', 'committees.id', '=', 'committee_member_assignments.committee_id')
            ->where('committee_member_assignments.member_id', $memberId)
            ->where('committee_member_assignments.is_primary', true)
            ->where('committee_member_assignments.is_active', true)
            ->whereNull('committee_member_assignments.deleted_at')
            ->where('committees.committee_type_id', $committeeTypeId)
            ->when($ignoreId, fn ($q) => $q->where('committee_member_assignments.id', '!=', $ignoreId))
            ->when($ignoreOldAssignmentId, fn ($q) => $q->where('committee_member_assignments.id', '!=', $ignoreOldAssignmentId));

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'is_primary' => ['Only one active primary assignment is allowed per member within the same committee type level.'],
            ]);
        }
    }

    private function generateAssignmentNo(): string
    {
        $year = now()->year;
        $next = ((int) CommitteeMemberAssignment::query()->max('id')) + 1;

        return 'ASN-'.$year.'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function recordHistory(
        CommitteeMemberAssignment $assignment,
        AssignmentHistoryAction $action,
        ?array $oldValues,
        ?array $newValues,
        int $actorId,
        ?string $note
    ): void {
        CommitteeMemberAssignmentHistory::create([
            'committee_member_assignment_id' => $assignment->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_by' => $actorId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    private function recordPositionHistory(
        CommitteeMemberAssignment $assignment,
        ?int $oldPositionId,
        ?int $newPositionId,
        int $actorId,
        ?string $note
    ): void {
        CommitteeMemberPositionHistory::create([
            'committee_member_assignment_id' => $assignment->id,
            'old_position_id' => $oldPositionId,
            'new_position_id' => $newPositionId,
            'changed_by' => $actorId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    private function detailRelations(): array
    {
        return [
            'member:id,member_no,full_name,email,mobile,status',
            'committee:id,committee_no,name,committee_type_id,status,is_current',
            'committee.committeeType:id,name,hierarchy_order',
            'position:id,name,short_name,hierarchy_rank,display_order,is_leadership,is_active',
            'creator:id,name,email',
            'updater:id,name,email',
            'appointedByUser:id,name,email',
            'approvedByUser:id,name,email',
            'histories.changedByUser:id,name,email',
            'positionHistories.oldPosition:id,name,hierarchy_rank',
            'positionHistories.newPosition:id,name,hierarchy_rank',
            'positionHistories.changedByUser:id,name,email',
        ];
    }
}
