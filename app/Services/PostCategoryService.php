<?php

namespace App\Services;

use App\Models\PostCategory;
use App\Enum\PostStatus;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostCategoryService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = PostCategory::query()->with(['creator:id,name,email', 'updater:id,name,email']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        if ($sortBy === 'name') {
            $query->orderBy('name', $sortDir);
        } else {
            $query->orderBy('created_at', $sortDir);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function create(array $data, int $actorId): PostCategory
    {
        return DB::transaction(function () use ($data, $actorId) {
            $slug = $this->generateUniqueSlug($data['slug'] ?? $data['name']);

            $category = PostCategory::create([
                'name' => trim($data['name']),
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'color' => $data['color'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'created_by' => $actorId,
            ]);

            return $category->fresh(['creator:id,name,email', 'updater:id,name,email']);
        });
    }

    public function detail(int $id): PostCategory
    {
        return PostCategory::with(['creator:id,name,email', 'updater:id,name,email'])->findOrFail($id);
    }

    public function update(PostCategory $category, array $data, int $actorId): PostCategory
    {
        return DB::transaction(function () use ($category, $data, $actorId) {
            if (array_key_exists('name', $data) || array_key_exists('slug', $data)) {
                $baseSlug = $data['slug'] ?? ($data['name'] ?? $category->name);
                $data['slug'] = $this->generateUniqueSlug($baseSlug, $category->id);
            }

            $category->update([
                ...$data,
                'updated_by' => $actorId,
            ]);

            return $category->fresh(['creator:id,name,email', 'updater:id,name,email']);
        });
    }

    public function updateStatus(PostCategory $category, bool $isActive, ?string $note, int $actorId): PostCategory
    {
        if ((bool) $category->is_active === $isActive) {
            throw ValidationException::withMessages([
                'is_active' => ['Category already has this active state.'],
            ]);
        }

        $category->update([
            'is_active' => $isActive,
            'updated_by' => $actorId,
        ]);

        return $category->fresh(['creator:id,name,email', 'updater:id,name,email']);
    }

    public function delete(PostCategory $category, int $actorId): void
    {
        if ($category->trashed()) {
            throw ValidationException::withMessages([
                'category' => ['Category is already deleted.'],
            ]);
        }

        $hasPublishedPosts = Post::query()
            ->where('post_category_id', $category->id)
            ->where('status', PostStatus::Published)
            ->exists();

        if ($hasPublishedPosts) {
            throw ValidationException::withMessages([
                'category' => ['Cannot delete category because published posts are using it.'],
            ]);
        }

        $category->update(['updated_by' => $actorId]);
        $category->delete();
    }

    public function restore(PostCategory $category, int $actorId): PostCategory
    {
        if (! $category->trashed()) {
            throw ValidationException::withMessages([
                'category' => ['Category is not deleted.'],
            ]);
        }

        $category->slug = $this->generateUniqueSlug($category->slug, $category->id);
        $category->restore();
        $category->update(['updated_by' => $actorId]);

        return $category->fresh(['creator:id,name,email', 'updater:id,name,email']);
    }

    private function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'category';
        $slug = $base;
        $i = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = PostCategory::withTrashed()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
