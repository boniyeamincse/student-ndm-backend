<?php

namespace App\Services;

use App\Enum\CommitteeStatus;
use App\Models\Committee;
use App\Models\CommitteeStatusHistory;
use App\Models\CommitteeType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommitteeService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Committee::query()->with(['committeeType:id,name,slug,hierarchy_order', 'parentCommittee:id,name,committee_no']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('committee_no', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        foreach (['committee_type_id', 'status', 'parent_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (array_key_exists('is_current', $filters) && $filters['is_current'] !== null) {
            $query->where('is_current', (bool) $filters['is_current']);
        }

        foreach (['division_name', 'district_name', 'upazila_name', 'union_name'] as $locationField) {
            if (! empty($filters[$locationField])) {
                $query->where($locationField, 'like', '%'.$filters[$locationField].'%');
            }
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        if ($sortBy === 'hierarchy_order') {
            $query->join('committee_types', 'committee_types.id', '=', 'committees.committee_type_id')
                ->select('committees.*')
                ->orderBy('committee_types.hierarchy_order', $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function create(array $data, int $actorId): Committee
    {
        return DB::transaction(function () use ($data, $actorId) {
            $type = CommitteeType::findOrFail($data['committee_type_id']);
            $parent = ! empty($data['parent_id']) ? Committee::findOrFail($data['parent_id']) : null;

            $this->validateParentChain($type, $parent);
            $this->validateLocationByType($type, $data);
            $this->validateDuplicateScope($data);

            $committeeNo = $this->generateCommitteeNo();
            $slug = $this->ensureUniqueSlug($data['slug'] ?? $data['name']);

            $committee = Committee::create([
                ...$data,
                'committee_no' => $committeeNo,
                'slug' => $slug,
                'created_by' => $actorId,
                'is_current' => $data['is_current'] ?? CommitteeStatus::from($data['status'])->isCurrentDefault(),
            ]);

            CommitteeStatusHistory::create([
                'committee_id' => $committee->id,
                'old_status' => null,
                'new_status' => $committee->status->value,
                'changed_by' => $actorId,
                'note' => 'Committee created.',
                'created_at' => now(),
            ]);

            Log::info('Committee created', [
                'committee_id' => $committee->id,
                'committee_no' => $committee->committee_no,
                'actor_id' => $actorId,
            ]);

            return $committee->fresh(['committeeType', 'parentCommittee']);
        });
    }

    public function detail(int $id): Committee
    {
        return Committee::with([
            'committeeType',
            'parentCommittee:id,name,committee_no,slug',
            'childCommittees:id,parent_id,name,committee_no,status,is_current',
            'formedByUser:id,name,email',
            'approvedByUser:id,name,email',
            'statusHistories.changedByUser:id,name,email',
        ])->findOrFail($id);
    }

    public function update(Committee $committee, array $data, int $actorId): Committee
    {
        return DB::transaction(function () use ($committee, $data, $actorId) {
            $type = CommitteeType::findOrFail($data['committee_type_id']);
            $parent = ! empty($data['parent_id']) ? Committee::findOrFail($data['parent_id']) : null;

            if ($parent && $parent->id === $committee->id) {
                throw ValidationException::withMessages([
                    'parent_id' => ['A committee cannot be its own parent.'],
                ]);
            }

            $this->validateParentChain($type, $parent);
            $this->ensureNoCycle($committee, $parent);
            $this->validateLocationByType($type, $data);
            $this->validateDuplicateScope($data, $committee->id);

            if (isset($data['slug']) || isset($data['name'])) {
                $data['slug'] = $this->ensureUniqueSlug($data['slug'] ?? $data['name'], $committee->id);
            }

            if (isset($data['status']) && $committee->status->value !== $data['status']) {
                $this->recordStatusHistory(
                    $committee,
                    $committee->status->value,
                    $data['status'],
                    $actorId,
                    'Status changed during committee update.'
                );
            }

            $data['updated_by'] = $actorId;
            $committee->update($data);

            Log::info('Committee updated', [
                'committee_id' => $committee->id,
                'actor_id' => $actorId,
                'parent_id' => $committee->parent_id,
            ]);

            return $committee->fresh(['committeeType', 'parentCommittee', 'childCommittees', 'statusHistories.changedByUser']);
        });
    }

    public function updateStatus(Committee $committee, string $newStatus, ?string $note, int $actorId): Committee
    {
        $targetStatus = CommitteeStatus::from($newStatus);

        if ($committee->status === $targetStatus) {
            throw ValidationException::withMessages([
                'status' => ['Committee already has this status.'],
            ]);
        }

        return DB::transaction(function () use ($committee, $targetStatus, $note, $actorId) {
            $oldStatus = $committee->status->value;

            $isCurrent = in_array($targetStatus, [CommitteeStatus::Dissolved, CommitteeStatus::Archived], true)
                ? false
                : $committee->is_current;

            $committee->update([
                'status' => $targetStatus,
                'notes' => $note ? trim(($committee->notes ? $committee->notes."\n" : '').$note) : $committee->notes,
                'is_current' => $isCurrent,
                'updated_by' => $actorId,
            ]);

            $this->recordStatusHistory($committee, $oldStatus, $targetStatus->value, $actorId, $note);

            Log::info('Committee status updated', [
                'committee_id' => $committee->id,
                'old_status' => $oldStatus,
                'new_status' => $targetStatus->value,
                'actor_id' => $actorId,
            ]);

            return $committee->fresh(['committeeType', 'parentCommittee', 'statusHistories.changedByUser']);
        });
    }

    public function delete(Committee $committee, int $actorId): void
    {
        if ($committee->childCommittees()->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages([
                'committee' => ['Cannot delete a committee that has child committees.'],
            ]);
        }

        // Future placeholder: block deletion if committeeMembers relation has rows.
        $committee->delete();

        Log::info('Committee deleted', [
            'committee_id' => $committee->id,
            'actor_id' => $actorId,
        ]);
    }

    public function restore(Committee $committee, int $actorId): Committee
    {
        $restoredSlug = $this->ensureUniqueSlug($committee->slug, $committee->id);

        $committee->restore();

        if ($restoredSlug !== $committee->slug) {
            $committee->update(['slug' => $restoredSlug, 'updated_by' => $actorId]);
        }

        Log::info('Committee restored', [
            'committee_id' => $committee->id,
            'actor_id' => $actorId,
        ]);

        return $committee->fresh(['committeeType', 'parentCommittee']);
    }

    public function summary(bool $includeDivision = false): array
    {
        $statusCounts = Committee::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $byType = Committee::query()
            ->join('committee_types', 'committee_types.id', '=', 'committees.committee_type_id')
            ->selectRaw('committee_types.name as committee_type, COUNT(*) as count')
            ->groupBy('committee_types.name')
            ->pluck('count', 'committee_type')
            ->toArray();

        $result = [
            'total_committees' => Committee::count(),
            'active_committees' => (int) ($statusCounts['active'] ?? 0),
            'inactive_committees' => (int) ($statusCounts['inactive'] ?? 0),
            'dissolved_committees' => (int) ($statusCounts['dissolved'] ?? 0),
            'archived_committees' => (int) ($statusCounts['archived'] ?? 0),
            'current_committees' => Committee::query()->where('is_current', true)->count(),
            'counts_by_committee_type' => $byType,
        ];

        if ($includeDivision) {
            $result['counts_by_division'] = Committee::query()
                ->whereNotNull('division_name')
                ->selectRaw('division_name, COUNT(*) as count')
                ->groupBy('division_name')
                ->orderByDesc('count')
                ->pluck('count', 'division_name')
                ->toArray();
        }

        return $result;
    }

    public function tree(array $filters): array
    {
        $query = Committee::query()->with(['committeeType:id,name,slug,hierarchy_order']);
        $rootCommitteeId = (int) ($filters['root_committee_id'] ?? 0);

        if (! empty($filters['committee_type_id'])) {
            $query->where('committee_type_id', $filters['committee_type_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('is_current', $filters) && $filters['is_current'] !== null) {
            $query->where('is_current', (bool) $filters['is_current']);
        }

        if ($rootCommitteeId > 0) {
            (clone $query)->where('id', $rootCommitteeId)->firstOrFail();
        }

        $committees = $query->orderBy('name')->get();

        return $this->buildTreeFromCollection($committees, $rootCommitteeId);
    }

    private function buildTreeFromCollection($committees, int $rootCommitteeId = 0): array
    {
        $grouped = $committees->groupBy(fn (Committee $c) => (int) ($c->parent_id ?? 0));

        $build = function (int $parentId) use (&$build, $grouped) {
            return ($grouped[$parentId] ?? collect())->map(function (Committee $committee) use (&$build) {
                return [
                    'id' => $committee->id,
                    'name' => $committee->name,
                    'committee_no' => $committee->committee_no,
                    'committee_type' => [
                        'id' => $committee->committeeType?->id,
                        'name' => $committee->committeeType?->name,
                        'slug' => $committee->committeeType?->slug,
                    ],
                    'status' => $committee->status?->value,
                    'children' => $build($committee->id),
                ];
            })->values()->all();
        };

        if ($rootCommitteeId > 0) {
            $root = $committees->firstWhere('id', $rootCommitteeId);
            if (! $root) {
                return [];
            }

            return [[
                'id' => $root->id,
                'name' => $root->name,
                'committee_no' => $root->committee_no,
                'committee_type' => [
                    'id' => $root->committeeType?->id,
                    'name' => $root->committeeType?->name,
                    'slug' => $root->committeeType?->slug,
                ],
                'status' => $root->status?->value,
                'children' => $build($root->id),
            ]];
        }

        return $build(0);
    }

    private function generateCommitteeNo(): string
    {
        $year = now()->year;
        $lastId = Committee::query()->max('id') ?? 0;
        $next = $lastId + 1;

        return 'COM-'.$year.'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug);
        $candidate = $base;
        $counter = 1;

        while (
            Committee::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->withTrashed()
                ->exists()
        ) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function validateParentChain(CommitteeType $type, ?Committee $parent): void
    {
        if ($type->hierarchy_order === 1 && $parent) {
            throw ValidationException::withMessages([
                'parent_id' => ['Central committee cannot have a parent.'],
            ]);
        }

        if ($type->hierarchy_order > 1 && ! $parent) {
            throw ValidationException::withMessages([
                'parent_id' => ['Parent committee is required for non-central levels.'],
            ]);
        }

        if ($parent) {
            $parentType = $parent->committeeType;
            if (! $parentType || $type->hierarchy_order !== ($parentType->hierarchy_order + 1)) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Invalid hierarchy mapping for parent and child committee types.'],
                ]);
            }
        }
    }

    private function validateLocationByType(CommitteeType $type, array $data): void
    {
        $requiredByOrder = [
            1 => [],
            2 => ['division_name'],
            3 => ['division_name', 'district_name'],
            4 => ['division_name', 'district_name', 'upazila_name'],
            5 => ['division_name', 'district_name', 'upazila_name', 'union_name'],
        ];

        $requiredFields = $requiredByOrder[$type->hierarchy_order] ?? [];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw ValidationException::withMessages([
                    $field => ['This location field is required for the selected committee type.'],
                ]);
            }
        }
    }

    private function validateDuplicateScope(array $data, ?int $ignoreCommitteeId = null): void
    {
        $query = Committee::query()
            ->where('committee_type_id', $data['committee_type_id'])
            ->whereRaw('LOWER(name) = ?', [Str::lower($data['name'])])
            ->where('division_name', $data['division_name'] ?? null)
            ->where('district_name', $data['district_name'] ?? null)
            ->where('upazila_name', $data['upazila_name'] ?? null)
            ->where('union_name', $data['union_name'] ?? null)
            ->whereNull('deleted_at');

        if ($ignoreCommitteeId) {
            $query->where('id', '!=', $ignoreCommitteeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => ['A committee with the same name/type/location already exists.'],
            ]);
        }
    }

    private function ensureNoCycle(Committee $committee, ?Committee $newParent): void
    {
        if (! $newParent) {
            return;
        }

        $cursor = $newParent;
        while ($cursor) {
            if ($cursor->id === $committee->id) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Invalid parent selection: cyclic hierarchy detected.'],
                ]);
            }
            $cursor = $cursor->parentCommittee;
        }
    }

    private function recordStatusHistory(Committee $committee, ?string $oldStatus, string $newStatus, int $actorId, ?string $note): void
    {
        CommitteeStatusHistory::create([
            'committee_id' => $committee->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $actorId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
