<?php

namespace App\Services;

use App\Models\CommitteeType;
use App\Models\Position;
use App\Models\PositionStatusHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PositionService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Position::query()
            ->with(['committeeTypes:id,name,slug'])
            ->withCount('committeeTypes');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        foreach (['category', 'scope'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        foreach (['is_active', 'is_leadership'] as $field) {
            if (array_key_exists($field, $filters) && $filters[$field] !== null) {
                $query->where($field, (bool) $filters[$field]);
            }
        }

        if (! empty($filters['committee_type_id'])) {
            $committeeTypeId = (int) $filters['committee_type_id'];
            $query->where(function ($q) use ($committeeTypeId) {
                // Include global positions and committee-specific positions mapped to this type
                $q->where('scope', 'global')
                  ->orWhereHas('committeeTypes', fn ($mapQ) => $mapQ->where('committee_types.id', $committeeTypeId));
            });
        }

        $sortBy = $filters['sort_by'] ?? 'hierarchy_rank';
        $sortDir = $filters['sort_dir'] ?? 'asc';

        if ($sortBy === 'display_order') {
            // Keep NULL display_order values after numeric display_order rows
            $query->orderByRaw('display_order IS NULL ASC')->orderBy('display_order', $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        if (! isset($filters['sort_by'])) {
            $query->orderByRaw('display_order IS NULL ASC')->orderBy('display_order', 'asc');
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function create(array $data, int $actorId): Position
    {
        return DB::transaction(function () use ($data, $actorId) {
            $committeeTypeIds = $this->normalizeCommitteeTypeIds($data['committee_type_ids'] ?? null);

            $this->validateScopeAndMappings($data['scope'], $committeeTypeIds);
            $this->validateCommitteeTypesActive($committeeTypeIds);

            $slug = $this->ensureUniqueSlug($data['slug'] ?? $data['name']);
            $this->assertNameAndCodeUnique($data);

            $position = Position::create([
                ...$data,
                'slug' => $slug,
                'created_by' => $actorId,
            ]);

            $this->syncCommitteeTypeMappings($position, $data['scope'], $committeeTypeIds);

            PositionStatusHistory::create([
                'position_id' => $position->id,
                'old_active_state' => null,
                'new_active_state' => (bool) $position->is_active,
                'changed_by' => $actorId,
                'note' => 'Position created.',
                'created_at' => now(),
            ]);

            Log::info('Position created', [
                'position_id' => $position->id,
                'name' => $position->name,
                'scope' => $position->scope?->value,
                'actor_id' => $actorId,
            ]);

            return $position->fresh(['committeeTypes', 'creator', 'updater', 'statusHistories.changedByUser']);
        });
    }

    public function detail(int $id): Position
    {
        return Position::with([
            'committeeTypes:id,name,slug,hierarchy_order,is_active',
            'creator:id,name,email',
            'updater:id,name,email',
            'statusHistories.changedByUser:id,name,email',
        ])->findOrFail($id);
    }

    public function update(Position $position, array $data, int $actorId): Position
    {
        return DB::transaction(function () use ($position, $data, $actorId) {
            $committeeTypeIds = $this->normalizeCommitteeTypeIds($data['committee_type_ids'] ?? null);

            $this->validateScopeAndMappings($data['scope'], $committeeTypeIds);
            $this->validateCommitteeTypesActive($committeeTypeIds);

            $slugCandidate = $data['slug'] ?? $data['name'];
            if ($slugCandidate) {
                $data['slug'] = $this->ensureUniqueSlug($slugCandidate, $position->id);
            }

            $this->assertNameAndCodeUnique($data, $position->id);

            if (array_key_exists('is_active', $data) && (bool) $position->is_active !== (bool) $data['is_active']) {
                $this->recordStatusHistory(
                    $position,
                    (bool) $position->is_active,
                    (bool) $data['is_active'],
                    $actorId,
                    'Active state changed during position update.'
                );
            }

            $data['updated_by'] = $actorId;
            $position->update($data);

            // Policy: switching to global clears committee type mappings.
            $this->syncCommitteeTypeMappings($position, $data['scope'], $committeeTypeIds);

            Log::info('Position updated', [
                'position_id' => $position->id,
                'scope' => $position->scope?->value,
                'actor_id' => $actorId,
            ]);

            return $position->fresh(['committeeTypes', 'creator', 'updater', 'statusHistories.changedByUser']);
        });
    }

    public function updateStatus(Position $position, bool $isActive, ?string $note, int $actorId): Position
    {
        if ((bool) $position->is_active === $isActive) {
            throw ValidationException::withMessages([
                'is_active' => ['Position already has this active state.'],
            ]);
        }

        return DB::transaction(function () use ($position, $isActive, $note, $actorId) {
            // Future placeholder: block deactivation if active assignments exist.
            $position->update([
                'is_active' => $isActive,
                'updated_by' => $actorId,
            ]);

            $this->recordStatusHistory(
                $position,
                ! $isActive,
                $isActive,
                $actorId,
                $note
            );

            Log::info('Position active state changed', [
                'position_id' => $position->id,
                'is_active' => $isActive,
                'actor_id' => $actorId,
            ]);

            return $position->fresh(['committeeTypes', 'creator', 'updater', 'statusHistories.changedByUser']);
        });
    }

    public function delete(Position $position, int $actorId): void
    {
        // Future placeholder: block delete if committee_member_assignments exist.
        $position->delete();

        Log::info('Position deleted', [
            'position_id' => $position->id,
            'actor_id' => $actorId,
        ]);
    }

    public function restore(Position $position, int $actorId): Position
    {
        $restoredSlug = $this->ensureUniqueSlug($position->slug, $position->id);

        $position->restore();

        if ($restoredSlug !== $position->slug) {
            $position->update(['slug' => $restoredSlug, 'updated_by' => $actorId]);
        }

        Log::info('Position restored', [
            'position_id' => $position->id,
            'actor_id' => $actorId,
        ]);

        return $position->fresh(['committeeTypes', 'creator', 'updater', 'statusHistories.changedByUser']);
    }

    public function summary(bool $includeCommitteeTypeSummary = false): array
    {
        $countsByCategory = Position::query()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $countsByScope = Position::query()
            ->selectRaw('scope, COUNT(*) as count')
            ->groupBy('scope')
            ->pluck('count', 'scope')
            ->toArray();

        $result = [
            'total_positions' => Position::count(),
            'active_positions' => Position::where('is_active', true)->count(),
            'inactive_positions' => Position::where('is_active', false)->count(),
            'leadership_positions' => Position::where('is_leadership', true)->count(),
            'non_leadership_positions' => Position::where('is_leadership', false)->count(),
            'counts_by_category' => $countsByCategory,
            'counts_by_scope' => $countsByScope,
        ];

        if ($includeCommitteeTypeSummary) {
            $result['counts_by_committee_type'] = DB::table('committee_type_position')
                ->join('committee_types', 'committee_types.id', '=', 'committee_type_position.committee_type_id')
                ->selectRaw('committee_types.name as committee_type, COUNT(DISTINCT committee_type_position.position_id) as count')
                ->groupBy('committee_types.name')
                ->pluck('count', 'committee_type')
                ->toArray();
        }

        return $result;
    }

    private function normalizeCommitteeTypeIds(null|array $committeeTypeIds): array
    {
        if ($committeeTypeIds === null) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $committeeTypeIds)));
    }

    private function validateScopeAndMappings(string $scope, array $committeeTypeIds): void
    {
        if ($scope === 'committee_specific' && count($committeeTypeIds) === 0) {
            throw ValidationException::withMessages([
                'committee_type_ids' => ['At least one committee type is required for committee_specific scope.'],
            ]);
        }
    }

    private function validateCommitteeTypesActive(array $committeeTypeIds): void
    {
        if (count($committeeTypeIds) === 0) {
            return;
        }

        $activeCount = CommitteeType::query()
            ->whereIn('id', $committeeTypeIds)
            ->where('is_active', true)
            ->count();

        if ($activeCount !== count($committeeTypeIds)) {
            throw ValidationException::withMessages([
                'committee_type_ids' => ['All committee type ids must exist and be active.'],
            ]);
        }
    }

    private function syncCommitteeTypeMappings(Position $position, string $scope, array $committeeTypeIds): void
    {
        if ($scope === 'global') {
            $position->committeeTypes()->sync([]);

            return;
        }

        $position->committeeTypes()->sync($committeeTypeIds);
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug);
        $candidate = $base;
        $counter = 1;

        while (
            Position::query()
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

    private function assertNameAndCodeUnique(array $data, ?int $ignoreId = null): void
    {
        $nameExists = Position::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereRaw('LOWER(name) = ?', [Str::lower($data['name'])])
            ->withTrashed()
            ->exists();

        if ($nameExists) {
            throw ValidationException::withMessages([
                'name' => ['A position with this name already exists.'],
            ]);
        }

        if (! empty($data['code'])) {
            $codeExists = Position::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->whereRaw('LOWER(code) = ?', [Str::lower($data['code'])])
                ->withTrashed()
                ->exists();

            if ($codeExists) {
                throw ValidationException::withMessages([
                    'code' => ['A position with this code already exists.'],
                ]);
            }
        }
    }

    private function recordStatusHistory(Position $position, ?bool $oldState, bool $newState, int $actorId, ?string $note): void
    {
        PositionStatusHistory::create([
            'position_id' => $position->id,
            'old_active_state' => $oldState,
            'new_active_state' => $newState,
            'changed_by' => $actorId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
