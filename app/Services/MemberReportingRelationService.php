<?php

namespace App\Services;

use App\Enum\ReportingRelationHistoryAction;
use App\Enum\ReportingRelationType;
use App\Models\Committee;
use App\Models\CommitteeMemberAssignment;
use App\Models\MemberReportingRelation;
use App\Models\MemberReportingRelationHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MemberReportingRelationService
{
	public function list(array $filters): LengthAwarePaginator
	{
		$query = MemberReportingRelation::query()
			->with($this->listRelations());

		if (! empty($filters['search'])) {
			$search = $filters['search'];
			$query->where(function ($q) use ($search) {
				$q->where('relation_no', 'like', "%{$search}%")
					->orWhereHas('subordinateAssignment.member', fn ($mq) => $mq
						->where('full_name', 'like', "%{$search}%")
						->orWhere('member_no', 'like', "%{$search}%"))
					->orWhereHas('superiorAssignment.member', fn ($mq) => $mq
						->where('full_name', 'like', "%{$search}%")
						->orWhere('member_no', 'like', "%{$search}%"))
					->orWhereHas('committee', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
					->orWhereHas('subordinateAssignment.position', fn ($pq) => $pq->where('name', 'like', "%{$search}%"))
					->orWhereHas('superiorAssignment.position', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
			});
		}

		foreach (['committee_id', 'subordinate_assignment_id', 'superior_assignment_id'] as $key) {
			if (! empty($filters[$key])) {
				$query->where($key, (int) $filters[$key]);
			}
		}

		if (! empty($filters['committee_type_id'])) {
			$query->whereHas('committee', fn ($cq) => $cq->where('committee_type_id', (int) $filters['committee_type_id']));
		}

		if (! empty($filters['relation_type'])) {
			$query->where('relation_type', $filters['relation_type']);
		}

		foreach (['is_primary', 'is_active'] as $key) {
			if (array_key_exists($key, $filters) && $filters[$key] !== null) {
				$query->where($key, (bool) $filters[$key]);
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

		if ($sortBy === 'subordinate_name') {
			$query
				->join('committee_member_assignments as sub_a', 'sub_a.id', '=', 'member_reporting_relations.subordinate_assignment_id')
				->join('members as sub_m', 'sub_m.id', '=', 'sub_a.member_id')
				->select('member_reporting_relations.*')
				->orderBy('sub_m.full_name', $sortDir);
		} elseif ($sortBy === 'superior_name') {
			$query
				->join('committee_member_assignments as sup_a', 'sup_a.id', '=', 'member_reporting_relations.superior_assignment_id')
				->join('members as sup_m', 'sup_m.id', '=', 'sup_a.member_id')
				->select('member_reporting_relations.*')
				->orderBy('sup_m.full_name', $sortDir);
		} else {
			$query->orderBy($sortBy, $sortDir);
		}

		return $query->paginate((int) ($filters['per_page'] ?? 20));
	}

	public function create(array $data, int $actorId): MemberReportingRelation
	{
		return DB::transaction(function () use ($data, $actorId) {
			$subordinate = CommitteeMemberAssignment::with(['committee'])->findOrFail($data['subordinate_assignment_id']);
			$superior = CommitteeMemberAssignment::with(['committee'])->findOrFail($data['superior_assignment_id']);
			$committeeId = $this->resolveCommitteeId($data['committee_id'] ?? null, $subordinate, $superior);
			$relationType = ReportingRelationType::from($data['relation_type']);
			$isPrimary = (bool) ($data['is_primary'] ?? true);
			$isActive = (bool) ($data['is_active'] ?? true);

			$this->assertCoreRules(
				subordinate: $subordinate,
				superior: $superior,
				committeeId: $committeeId,
				relationType: $relationType,
				isPrimary: $isPrimary,
				isActive: $isActive,
			);

			$this->assertDuplicateRelation($subordinate->id, $superior->id, $relationType, $isActive);

			if ($isPrimary && $isActive) {
				$this->assertPrimaryUniqueness($subordinate->id, $relationType, null);
			}

			$relation = MemberReportingRelation::create([
				'relation_no' => $this->generateRelationNo(),
				'subordinate_assignment_id' => $subordinate->id,
				'superior_assignment_id' => $superior->id,
				'committee_id' => $committeeId,
				'relation_type' => $relationType,
				'is_primary' => $isPrimary,
				'is_active' => $isActive,
				'start_date' => $data['start_date'] ?? null,
				'end_date' => $data['end_date'] ?? null,
				'note' => $data['note'] ?? null,
				'created_by' => $actorId,
			]);

			$this->recordHistory(
				$relation,
				ReportingRelationHistoryAction::Created,
				null,
				$relation->only([
					'subordinate_assignment_id',
					'superior_assignment_id',
					'committee_id',
					'relation_type',
					'is_primary',
					'is_active',
					'start_date',
					'end_date',
				]),
				$actorId,
				'Reporting relation created.'
			);

			Log::info('Member reporting relation created', [
				'relation_id' => $relation->id,
				'relation_no' => $relation->relation_no,
				'actor_id' => $actorId,
			]);

			return $relation->fresh($this->detailRelations());
		});
	}

	public function detail(int $id): MemberReportingRelation
	{
		return MemberReportingRelation::with($this->detailRelations())->findOrFail($id);
	}

	public function update(MemberReportingRelation $relation, array $data, int $actorId): MemberReportingRelation
	{
		return DB::transaction(function () use ($relation, $data, $actorId) {
			$relation->loadMissing(['subordinateAssignment.committee', 'superiorAssignment.committee']);

			$old = [
				'superior_assignment_id' => $relation->superior_assignment_id,
				'relation_type' => $relation->relation_type?->value,
				'is_primary' => (bool) $relation->is_primary,
				'is_active' => (bool) $relation->is_active,
				'committee_id' => $relation->committee_id,
				'start_date' => optional($relation->start_date)->toDateString(),
				'end_date' => optional($relation->end_date)->toDateString(),
				'note' => $relation->note,
			];

			$subordinate = $relation->subordinateAssignment;
			$superior = array_key_exists('superior_assignment_id', $data)
				? CommitteeMemberAssignment::with('committee')->findOrFail($data['superior_assignment_id'])
				: $relation->superiorAssignment;

			$relationType = array_key_exists('relation_type', $data)
				? ReportingRelationType::from($data['relation_type'])
				: $relation->relation_type;

			$isPrimary = array_key_exists('is_primary', $data)
				? (bool) $data['is_primary']
				: (bool) $relation->is_primary;

			$isActive = array_key_exists('is_active', $data)
				? (bool) $data['is_active']
				: (bool) $relation->is_active;

			$committeeId = $this->resolveCommitteeId($data['committee_id'] ?? $relation->committee_id, $subordinate, $superior);

			$this->assertCoreRules(
				subordinate: $subordinate,
				superior: $superior,
				committeeId: $committeeId,
				relationType: $relationType,
				isPrimary: $isPrimary,
				isActive: $isActive,
				ignoreRelationId: $relation->id,
			);

			$this->assertDuplicateRelation($subordinate->id, $superior->id, $relationType, $isActive, $relation->id);

			if ($isPrimary && $isActive) {
				$this->assertPrimaryUniqueness($subordinate->id, $relationType, $relation->id);
			}

			$payload = Arr::only($data, ['note', 'start_date', 'end_date']);
			$payload['superior_assignment_id'] = $superior->id;
			$payload['relation_type'] = $relationType;
			$payload['is_primary'] = $isPrimary;
			$payload['is_active'] = $isActive;
			$payload['committee_id'] = $committeeId;
			$payload['updated_by'] = $actorId;

			$relation->update($payload);

			if ($old['superior_assignment_id'] !== $relation->superior_assignment_id) {
				$this->recordHistory(
					$relation,
					ReportingRelationHistoryAction::SuperiorChanged,
					['superior_assignment_id' => $old['superior_assignment_id']],
					['superior_assignment_id' => $relation->superior_assignment_id],
					$actorId,
					'Superior changed.'
				);
			}

			if ($old['relation_type'] !== $relation->relation_type?->value) {
				$this->recordHistory(
					$relation,
					ReportingRelationHistoryAction::RelationTypeChanged,
					['relation_type' => $old['relation_type']],
					['relation_type' => $relation->relation_type?->value],
					$actorId,
					'Relation type changed.'
				);
			}

			if ($old['is_primary'] !== (bool) $relation->is_primary) {
				$this->recordHistory(
					$relation,
					ReportingRelationHistoryAction::PrimaryChanged,
					['is_primary' => $old['is_primary']],
					['is_primary' => (bool) $relation->is_primary],
					$actorId,
					'Primary flag changed.'
				);
			}

			if ($old['is_active'] !== (bool) $relation->is_active) {
				$this->recordHistory(
					$relation,
					$relation->is_active ? ReportingRelationHistoryAction::Activated : ReportingRelationHistoryAction::Deactivated,
					['is_active' => $old['is_active']],
					['is_active' => (bool) $relation->is_active],
					$actorId,
					'Active status changed in update.'
				);
			}

			$this->recordHistory(
				$relation,
				ReportingRelationHistoryAction::Updated,
				$old,
				[
					'superior_assignment_id' => $relation->superior_assignment_id,
					'relation_type' => $relation->relation_type?->value,
					'is_primary' => (bool) $relation->is_primary,
					'is_active' => (bool) $relation->is_active,
					'committee_id' => $relation->committee_id,
					'start_date' => optional($relation->start_date)->toDateString(),
					'end_date' => optional($relation->end_date)->toDateString(),
					'note' => $relation->note,
				],
				$actorId,
				'Reporting relation updated.'
			);

			Log::info('Member reporting relation updated', [
				'relation_id' => $relation->id,
				'actor_id' => $actorId,
			]);

			return $relation->fresh($this->detailRelations());
		});
	}

	public function updateStatus(MemberReportingRelation $relation, bool $isActive, ?string $note, int $actorId): MemberReportingRelation
	{
		return DB::transaction(function () use ($relation, $isActive, $note, $actorId) {
			if ((bool) $relation->is_active === $isActive) {
				throw ValidationException::withMessages([
					'is_active' => ['Relation already has this active state.'],
				]);
			}

			if ($isActive) {
				$this->assertCoreRules(
					subordinate: $relation->subordinateAssignment,
					superior: $relation->superiorAssignment,
					committeeId: $relation->committee_id,
					relationType: $relation->relation_type,
					isPrimary: (bool) $relation->is_primary,
					isActive: true,
					ignoreRelationId: $relation->id,
				);

				if ($relation->is_primary) {
					$this->assertPrimaryUniqueness($relation->subordinate_assignment_id, $relation->relation_type, $relation->id);
				}
			}

			$old = ['is_active' => (bool) $relation->is_active];

			$relation->update([
				'is_active' => $isActive,
				'updated_by' => $actorId,
				'note' => $note ? trim(($relation->note ? $relation->note."\n" : '').$note) : $relation->note,
			]);

			$this->recordHistory(
				$relation,
				ReportingRelationHistoryAction::StatusChanged,
				$old,
				['is_active' => (bool) $relation->is_active],
				$actorId,
				$note
			);

			$this->recordHistory(
				$relation,
				$isActive ? ReportingRelationHistoryAction::Activated : ReportingRelationHistoryAction::Deactivated,
				$old,
				['is_active' => (bool) $relation->is_active],
				$actorId,
				$note
			);

			Log::info('Member reporting relation status updated', [
				'relation_id' => $relation->id,
				'is_active' => $isActive,
				'actor_id' => $actorId,
			]);

			return $relation->fresh($this->detailRelations());
		});
	}

	public function leader(int $assignmentId, array $filters): ?MemberReportingRelation
	{
		CommitteeMemberAssignment::findOrFail($assignmentId);

		$query = MemberReportingRelation::query()
			->with($this->detailRelations())
			->where('subordinate_assignment_id', $assignmentId)
			->where('is_active', true);

		if (! ($filters['include_non_primary'] ?? false)) {
			$query->where('is_primary', true);
		}

		if (! ($filters['include_all_relation_types'] ?? false)) {
			$query->where('relation_type', ReportingRelationType::DirectReport);
		}

		return $query
			->orderByDesc('is_primary')
			->orderByDesc('created_at')
			->first();
	}

	public function subordinates(int $assignmentId, array $filters): LengthAwarePaginator
	{
		CommitteeMemberAssignment::findOrFail($assignmentId);

		$query = MemberReportingRelation::query()
			->with($this->listRelations())
			->where('superior_assignment_id', $assignmentId);

		if (! empty($filters['relation_type'])) {
			$query->where('relation_type', $filters['relation_type']);
		}

		foreach (['is_primary', 'is_active'] as $key) {
			if (array_key_exists($key, $filters) && $filters[$key] !== null) {
				$query->where($key, (bool) $filters[$key]);
			}
		}

		if ($filters['leadership_only'] ?? false) {
			$query->whereHas('subordinateAssignment.position', fn ($pq) => $pq->where('is_leadership', true));
		}

		$sortBy = $filters['sort_by'] ?? 'rank';
		$sortDir = $filters['sort_dir'] ?? 'asc';

		if ($sortBy === 'member_name') {
			$query
				->join('committee_member_assignments as sub_a', 'sub_a.id', '=', 'member_reporting_relations.subordinate_assignment_id')
				->join('members as sub_m', 'sub_m.id', '=', 'sub_a.member_id')
				->select('member_reporting_relations.*')
				->orderBy('sub_m.full_name', $sortDir);
		} elseif ($sortBy === 'rank') {
			$query
				->join('committee_member_assignments as sub_a', 'sub_a.id', '=', 'member_reporting_relations.subordinate_assignment_id')
				->leftJoin('positions as sub_p', 'sub_p.id', '=', 'sub_a.position_id')
				->select('member_reporting_relations.*')
				->orderByDesc('sub_p.is_leadership')
				->orderByRaw('sub_p.hierarchy_rank IS NULL ASC')
				->orderBy('sub_p.hierarchy_rank', 'asc');
		} else {
			$query->orderBy('member_reporting_relations.created_at', 'desc');
		}

		return $query->paginate((int) ($filters['per_page'] ?? 20));
	}

	public function committeeHierarchyTree(int $committeeId, array $filters): array
	{
		Committee::findOrFail($committeeId);

		$activeOnly = (bool) ($filters['active_only'] ?? true);
		$relationType = ReportingRelationType::from($filters['relation_type'] ?? ReportingRelationType::DirectReport->value);
		$includeNonPrimary = (bool) ($filters['include_non_primary'] ?? false);
		$includeOrphans = (bool) ($filters['include_orphans'] ?? true);

		$assignmentsQuery = CommitteeMemberAssignment::query()
			->with([
				'member:id,member_no,full_name,photo',
				'position:id,name,hierarchy_rank,is_leadership',
			])
			->where('committee_id', $committeeId);

		if ($activeOnly) {
			$assignmentsQuery->where('is_active', true);
		}

		$assignments = $assignmentsQuery->get()->keyBy('id');

		if ($assignments->isEmpty()) {
			return [];
		}

		$relationsQuery = MemberReportingRelation::query()
			->where('committee_id', $committeeId)
			->where('relation_type', $relationType);

		if ($activeOnly) {
			$relationsQuery->where('is_active', true);
		}
		if (! $includeNonPrimary) {
			$relationsQuery->where('is_primary', true);
		}

		$relations = $relationsQuery->get();

		$parentMap = [];
		$childrenMap = [];
		foreach ($relations as $relation) {
			if (! $assignments->has($relation->subordinate_assignment_id) || ! $assignments->has($relation->superior_assignment_id)) {
				continue;
			}

			if (! isset($parentMap[$relation->subordinate_assignment_id])) {
				$parentMap[$relation->subordinate_assignment_id] = $relation->superior_assignment_id;
			}

			$childrenMap[$relation->superior_assignment_id] ??= [];
			$childrenMap[$relation->superior_assignment_id][] = [
				'assignment_id' => $relation->subordinate_assignment_id,
				'relation_type' => $relation->relation_type->value,
			];
		}

		$roots = $assignments
			->keys()
			->filter(fn ($id) => ! isset($parentMap[$id]))
			->values()
			->all();

		$built = [];
		$used = [];

		foreach ($roots as $rootId) {
			$node = $this->buildTreeNode($rootId, $assignments, $childrenMap, [], $used);
			if ($node !== null) {
				$built[] = $node;
			}
		}

		if ($includeOrphans) {
			foreach ($assignments as $assignmentId => $assignment) {
				if (! isset($used[$assignmentId])) {
					$node = $this->buildTreeNode((int) $assignmentId, $assignments, $childrenMap, [], $used);
					if ($node !== null) {
						$built[] = $node;
					}
				}
			}
		}

		usort($built, function (array $a, array $b) {
			$leaderCmp = ((int) ($b['is_leadership'] ?? 0)) <=> ((int) ($a['is_leadership'] ?? 0));
			if ($leaderCmp !== 0) {
				return $leaderCmp;
			}

			return ($a['position_rank'] ?? PHP_INT_MAX) <=> ($b['position_rank'] ?? PHP_INT_MAX);
		});

		return $built;
	}

	public function summary(bool $includeCommitteeSummary = false): array
	{
		$baseQuery = MemberReportingRelation::query();

		$summary = [
			'total_relations' => (clone $baseQuery)->count(),
			'active_relations' => (clone $baseQuery)->where('is_active', true)->count(),
			'inactive_relations' => (clone $baseQuery)->where('is_active', false)->count(),
			'primary_relations' => (clone $baseQuery)->where('is_primary', true)->count(),
			'by_relation_type' => (clone $baseQuery)
				->select('relation_type', DB::raw('COUNT(*) as total'))
				->groupBy('relation_type')
				->pluck('total', 'relation_type')
				->toArray(),
			'by_committee' => [],
			'by_committee_type' => [],
		];

		if ($includeCommitteeSummary) {
			$summary['by_committee'] = MemberReportingRelation::query()
				->select('committee_id', DB::raw('COUNT(*) as total'))
				->with('committee:id,name')
				->groupBy('committee_id')
				->get()
				->map(fn (MemberReportingRelation $relation) => [
					'committee_id' => $relation->committee_id,
					'committee_name' => $relation->committee?->name,
					'total' => (int) ($relation->total ?? 0),
				])
				->values()
				->toArray();

			$summary['by_committee_type'] = MemberReportingRelation::query()
				->join('committees', 'committees.id', '=', 'member_reporting_relations.committee_id')
				->join('committee_types', 'committee_types.id', '=', 'committees.committee_type_id')
				->select('committee_types.id', 'committee_types.name', DB::raw('COUNT(member_reporting_relations.id) as total'))
				->groupBy('committee_types.id', 'committee_types.name')
				->get()
				->map(fn ($item) => [
					'committee_type_id' => $item->id,
					'committee_type_name' => $item->name,
					'total' => (int) $item->total,
				])
				->values()
				->toArray();
		}

		return $summary;
	}

	public function delete(MemberReportingRelation $relation, int $actorId): void
	{
		DB::transaction(function () use ($relation, $actorId) {
			if ($relation->trashed()) {
				throw ValidationException::withMessages([
					'relation' => ['Relation is already deleted.'],
				]);
			}

			$relation->update(['updated_by' => $actorId]);
			$relation->delete();

			$this->recordHistory(
				$relation,
				ReportingRelationHistoryAction::Deleted,
				['deleted_at' => null],
				['deleted_at' => now()->toDateTimeString()],
				$actorId,
				'Relation soft deleted.'
			);
		});
	}

	public function restore(MemberReportingRelation $relation, int $actorId): MemberReportingRelation
	{
		return DB::transaction(function () use ($relation, $actorId) {
			if (! $relation->trashed()) {
				throw ValidationException::withMessages([
					'relation' => ['Relation is not deleted.'],
				]);
			}

			$relation->loadMissing(['subordinateAssignment.committee', 'superiorAssignment.committee']);

			$this->assertCoreRules(
				subordinate: $relation->subordinateAssignment,
				superior: $relation->superiorAssignment,
				committeeId: $relation->committee_id,
				relationType: $relation->relation_type,
				isPrimary: (bool) $relation->is_primary,
				isActive: (bool) $relation->is_active,
				ignoreRelationId: $relation->id,
			);

			if ($relation->is_primary && $relation->is_active) {
				$this->assertPrimaryUniqueness($relation->subordinate_assignment_id, $relation->relation_type, $relation->id);
			}

			$relation->restore();
			$relation->update(['updated_by' => $actorId]);

			$this->recordHistory(
				$relation,
				ReportingRelationHistoryAction::Restored,
				['deleted_at' => $relation->deleted_at?->toDateTimeString()],
				['deleted_at' => null],
				$actorId,
				'Relation restored.'
			);

			return $relation->fresh($this->detailRelations());
		});
	}

	private function generateRelationNo(): string
	{
		$year = now()->year;
		$last = MemberReportingRelation::withTrashed()
			->whereYear('created_at', $year)
			->lockForUpdate()
			->orderByDesc('id')
			->value('relation_no');

		$next = 1;
		if ($last && preg_match('/REL-\d{4}-(\d{6})/', $last, $match)) {
			$next = ((int) $match[1]) + 1;
		}

		return sprintf('REL-%d-%06d', $year, $next);
	}

	private function resolveCommitteeId(?int $committeeId, CommitteeMemberAssignment $subordinate, CommitteeMemberAssignment $superior): int
	{
		$resolved = $committeeId ?? $subordinate->committee_id;

		if ((int) $subordinate->committee_id !== (int) $resolved || (int) $superior->committee_id !== (int) $resolved) {
			throw ValidationException::withMessages([
				'committee_id' => ['Committee scope must match both subordinate and superior assignments.'],
			]);
		}

		return (int) $resolved;
	}

	private function assertCoreRules(
		CommitteeMemberAssignment $subordinate,
		CommitteeMemberAssignment $superior,
		int $committeeId,
		ReportingRelationType $relationType,
		bool $isPrimary,
		bool $isActive,
		?int $ignoreRelationId = null
	): void {
		if ((int) $subordinate->id === (int) $superior->id) {
			throw ValidationException::withMessages([
				'superior_assignment_id' => ['Subordinate and superior assignments cannot be the same.'],
			]);
		}

		if ((int) $subordinate->committee_id !== (int) $superior->committee_id) {
			throw ValidationException::withMessages([
				'superior_assignment_id' => ['Reporting relation currently requires same committee assignments.'],
			]);
		}

		if ((int) $subordinate->committee_id !== $committeeId || (int) $superior->committee_id !== $committeeId) {
			throw ValidationException::withMessages([
				'committee_id' => ['Committee does not match assignment committee context.'],
			]);
		}

		if ($isActive && (! $subordinate->is_active || ! $superior->is_active)) {
			throw ValidationException::withMessages([
				'is_active' => ['Active reporting relation requires active subordinate and superior assignments.'],
			]);
		}

		if ($isPrimary && $relationType !== ReportingRelationType::DirectReport) {
			throw ValidationException::withMessages([
				'is_primary' => ['Primary reporting line is only allowed for direct_report relation type.'],
			]);
		}

		$this->assertNoCycle($subordinate->id, $superior->id, $ignoreRelationId);
	}

	private function assertNoCycle(int $subordinateAssignmentId, int $proposedSuperiorAssignmentId, ?int $ignoreRelationId = null): void
	{
		$visited = [];
		$stack = [$proposedSuperiorAssignmentId];

		while (! empty($stack)) {
			$current = array_pop($stack);
			if (isset($visited[$current])) {
				continue;
			}
			$visited[$current] = true;

			if ((int) $current === (int) $subordinateAssignmentId) {
				throw ValidationException::withMessages([
					'superior_assignment_id' => ['Circular reporting relation is not allowed.'],
				]);
			}

			$query = MemberReportingRelation::query()
				->where('subordinate_assignment_id', $current)
				->where('is_active', true);

			if ($ignoreRelationId) {
				$query->where('id', '!=', $ignoreRelationId);
			}

			$nextSuperiors = $query->pluck('superior_assignment_id')->all();
			foreach ($nextSuperiors as $next) {
				if (! isset($visited[$next])) {
					$stack[] = $next;
				}
			}
		}
	}

	private function assertDuplicateRelation(
		int $subordinateAssignmentId,
		int $superiorAssignmentId,
		ReportingRelationType $relationType,
		bool $isActive,
		?int $ignoreRelationId = null
	): void {
		if (! $isActive) {
			return;
		}

		$query = MemberReportingRelation::query()
			->where('subordinate_assignment_id', $subordinateAssignmentId)
			->where('superior_assignment_id', $superiorAssignmentId)
			->where('relation_type', $relationType)
			->where('is_active', true);

		if ($ignoreRelationId) {
			$query->where('id', '!=', $ignoreRelationId);
		}

		if ($query->exists()) {
			throw ValidationException::withMessages([
				'superior_assignment_id' => ['Duplicate active reporting relation already exists.'],
			]);
		}
	}

	private function assertPrimaryUniqueness(int $subordinateAssignmentId, ReportingRelationType $relationType, ?int $ignoreRelationId): void
	{
		if ($relationType !== ReportingRelationType::DirectReport) {
			return;
		}

		$query = MemberReportingRelation::query()
			->where('subordinate_assignment_id', $subordinateAssignmentId)
			->where('relation_type', ReportingRelationType::DirectReport)
			->where('is_primary', true)
			->where('is_active', true);

		if ($ignoreRelationId) {
			$query->where('id', '!=', $ignoreRelationId);
		}

		if ($query->exists()) {
			throw ValidationException::withMessages([
				'is_primary' => ['Subordinate assignment already has an active primary direct_report relation.'],
			]);
		}
	}

	private function buildTreeNode(
		int $assignmentId,
		Collection $assignments,
		array $childrenMap,
		array $path,
		array &$used
	): ?array {
		if (in_array($assignmentId, $path, true)) {
			return null;
		}

		$assignment = $assignments->get($assignmentId);
		if (! $assignment) {
			return null;
		}

		$used[$assignmentId] = true;
		$newPath = [...$path, $assignmentId];
		$childNodes = [];

		foreach ($childrenMap[$assignmentId] ?? [] as $child) {
			$node = $this->buildTreeNode(
				$child['assignment_id'],
				$assignments,
				$childrenMap,
				$newPath,
				$used
			);
			if ($node !== null) {
				$node['relation_type'] = $child['relation_type'];
				$childNodes[] = $node;
			}
		}

		usort($childNodes, function (array $a, array $b) {
			$leaderCmp = ((int) ($b['is_leadership'] ?? 0)) <=> ((int) ($a['is_leadership'] ?? 0));
			if ($leaderCmp !== 0) {
				return $leaderCmp;
			}

			return ($a['position_rank'] ?? PHP_INT_MAX) <=> ($b['position_rank'] ?? PHP_INT_MAX);
		});

		return [
			'assignment_id' => $assignment->id,
			'member_id' => $assignment->member?->id,
			'member_name' => $assignment->member?->full_name,
			'member_no' => $assignment->member?->member_no,
			'photo_url' => $assignment->member?->photo ? asset('storage/'.$assignment->member->photo) : null,
			'position' => $assignment->position?->name,
			'position_rank' => $assignment->position?->hierarchy_rank,
			'is_leadership' => (bool) ($assignment->position?->is_leadership ?? false),
			'relation_type' => null,
			'children' => $childNodes,
		];
	}

	private function recordHistory(
		MemberReportingRelation $relation,
		ReportingRelationHistoryAction $action,
		?array $oldValues,
		?array $newValues,
		?int $actorId,
		?string $note = null
	): void {
		MemberReportingRelationHistory::create([
			'member_reporting_relation_id' => $relation->id,
			'action' => $action,
			'old_values' => $oldValues,
			'new_values' => $newValues,
			'changed_by' => $actorId,
			'note' => $note,
			'created_at' => now(),
		]);
	}

	private function listRelations(): array
	{
		return [
			'committee:id,committee_no,name,committee_type_id',
			'committee.committeeType:id,name',
			'subordinateAssignment:id,assignment_no,member_id,position_id,committee_id',
			'subordinateAssignment.member:id,member_no,full_name,photo',
			'subordinateAssignment.position:id,name,hierarchy_rank,is_leadership',
			'superiorAssignment:id,assignment_no,member_id,position_id,committee_id',
			'superiorAssignment.member:id,member_no,full_name,photo',
			'superiorAssignment.position:id,name,hierarchy_rank,is_leadership',
		];
	}

	private function detailRelations(): array
	{
		return [
			...$this->listRelations(),
			'subordinateAssignment.committee:id,name',
			'superiorAssignment.committee:id,name',
			'creator:id,name,email',
			'updater:id,name,email',
			'histories.changedByUser:id,name,email',
		];
	}
}
