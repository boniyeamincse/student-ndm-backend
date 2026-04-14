<?php

namespace App\Services;

use App\Models\CommitteeType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommitteeTypeService
{
    private const DEFAULT_TYPE_SLUGS = ['central', 'division', 'district', 'upazila', 'union'];

    public function list(array $filters): LengthAwarePaginator
    {
        $query = CommitteeType::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('hierarchy_order')->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function create(array $data, int $actorId): CommitteeType
    {
        $data['slug'] = $this->ensureUniqueSlug($data['slug']);

        $type = CommitteeType::create($data);

        Log::info('Committee type created', [
            'committee_type_id' => $type->id,
            'name' => $type->name,
            'actor_id' => $actorId,
        ]);

        return $type;
    }

    public function update(CommitteeType $type, array $data, int $actorId): CommitteeType
    {
        if (isset($data['slug'])) {
            $data['slug'] = $this->ensureUniqueSlug($data['slug'], $type->id);
        }

        $type->update($data);

        Log::info('Committee type updated', [
            'committee_type_id' => $type->id,
            'actor_id' => $actorId,
        ]);

        return $type->refresh();
    }

    public function delete(CommitteeType $type, int $actorId): void
    {
        if (in_array($type->slug, self::DEFAULT_TYPE_SLUGS, true)) {
            throw ValidationException::withMessages([
                'committee_type' => ['Default committee types cannot be deleted.'],
            ]);
        }

        if ($type->committees()->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages([
                'committee_type' => ['Cannot delete this type while active committees use it.'],
            ]);
        }

        $type->delete();

        Log::info('Committee type deleted', [
            'committee_type_id' => $type->id,
            'actor_id' => $actorId,
        ]);
    }

    public function restore(CommitteeType $type, int $actorId): CommitteeType
    {
        $type->restore();

        Log::info('Committee type restored', [
            'committee_type_id' => $type->id,
            'actor_id' => $actorId,
        ]);

        return $type->refresh();
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug);
        $candidate = $base;
        $counter = 1;

        while (
            CommitteeType::query()
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
}
