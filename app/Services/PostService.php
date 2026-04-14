<?php

namespace App\Services;

use App\Enum\CommitteeStatus;
use App\Enum\PostStatus;
use App\Enum\PostVisibility;
use App\Models\Committee;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostStatusHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Post::query()->with($this->listRelations());

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('post_no', 'like', "%{$search}%");
            });
        }

        foreach (['content_type', 'post_category_id', 'committee_id', 'author_id', 'editor_id', 'status', 'visibility'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        foreach (['is_featured', 'allow_on_homepage'] as $field) {
            if (array_key_exists($field, $filters) && $filters[$field] !== null) {
                $query->where($field, (bool) $filters[$field]);
            }
        }

        if (! empty($filters['published_from'])) {
            $query->whereDate('published_at', '>=', $filters['published_from']);
        }
        if (! empty($filters['published_to'])) {
            $query->whereDate('published_at', '<=', $filters['published_to']);
        }
        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }
        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function create(array $data, int $actorId): Post
    {
        return DB::transaction(function () use ($data, $actorId) {
            $actor = User::findOrFail($actorId);
            $status = PostStatus::from($data['status'] ?? PostStatus::Draft->value);
            $visibility = PostVisibility::from($data['visibility']);

            $this->validateCategory($data['post_category_id'] ?? null, $status);
            $this->validateCommittee($data['committee_id'] ?? null);
            $this->assertPublishPermission($actor, $status);
            $this->assertFeatureRules((bool) ($data['is_featured'] ?? false), $status, $visibility, (bool) ($data['allow_on_homepage'] ?? false));

            $imagePath = $this->storeFeaturedImage($data['featured_image'] ?? null);
            $slug = $this->generateUniqueSlug($data['slug'] ?? $data['title']);
            $publishedAt = $status === PostStatus::Published ? ($data['published_at'] ?? now()) : ($data['published_at'] ?? null);

            $post = Post::create([
                'post_no' => $this->generatePostNo(),
                'title' => trim($data['title']),
                'slug' => $slug,
                'excerpt' => $data['excerpt'] ?? null,
                'content' => $data['content'],
                'content_type' => $data['content_type'],
                'post_category_id' => $data['post_category_id'] ?? null,
                'committee_id' => $data['committee_id'] ?? null,
                'author_id' => $data['author_id'] ?? $actorId,
                'editor_id' => $data['editor_id'] ?? null,
                'featured_image' => $imagePath,
                'featured_image_alt' => $data['featured_image_alt'] ?? null,
                'tags' => $data['tags'] ?? null,
                'status' => $status,
                'visibility' => $visibility,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'allow_on_homepage' => (bool) ($data['allow_on_homepage'] ?? false),
                'published_at' => $publishedAt,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'last_edited_at' => now(),
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
                'created_by' => $actorId,
            ]);

            $this->recordStatusHistory($post, null, $status, $actorId, 'Initial status set on create.');

            Log::info('Post created', ['post_id' => $post->id, 'actor_id' => $actorId]);

            return $post->fresh($this->detailRelations());
        });
    }

    public function detail(int $id): Post
    {
        return Post::with($this->detailRelations())->findOrFail($id);
    }

    public function update(Post $post, array $data, int $actorId): Post
    {
        return DB::transaction(function () use ($post, $data, $actorId) {
            $visibility = array_key_exists('visibility', $data)
                ? PostVisibility::from($data['visibility'])
                : $post->visibility;

            $status = $post->status;
            $this->validateCategory($data['post_category_id'] ?? $post->post_category_id, $status);
            $this->validateCommittee($data['committee_id'] ?? $post->committee_id);

            $isFeatured = array_key_exists('is_featured', $data) ? (bool) $data['is_featured'] : (bool) $post->is_featured;
            $allowOnHomepage = array_key_exists('allow_on_homepage', $data) ? (bool) $data['allow_on_homepage'] : (bool) $post->allow_on_homepage;
            $this->assertFeatureRules($isFeatured, $status, $visibility, $allowOnHomepage);

            if (array_key_exists('title', $data) || array_key_exists('slug', $data)) {
                $baseSlug = $data['slug'] ?? ($data['title'] ?? $post->title);
                $data['slug'] = $this->generateUniqueSlug($baseSlug, $post->id);
            }

            if (! empty($data['featured_image']) && $data['featured_image'] instanceof UploadedFile) {
                $data['featured_image'] = $this->replaceFeaturedImage($post->featured_image, $data['featured_image']);
            } else {
                unset($data['featured_image']);
            }

            $post->update([
                ...$data,
                'updated_by' => $actorId,
                'last_edited_at' => now(),
            ]);

            Log::info('Post updated', ['post_id' => $post->id, 'actor_id' => $actorId]);

            return $post->fresh($this->detailRelations());
        });
    }

    public function changeStatus(Post $post, PostStatus $newStatus, ?string $note, int $actorId): Post
    {
        return DB::transaction(function () use ($post, $newStatus, $note, $actorId) {
            $actor = User::findOrFail($actorId);
            $oldStatus = $post->status;

            if ($oldStatus === $newStatus) {
                throw ValidationException::withMessages([
                    'status' => ['Post already has this status.'],
                ]);
            }

            $this->assertStatusTransitionAllowed($oldStatus, $newStatus);
            $this->assertPublishPermission($actor, $newStatus);
            $this->validateCategory($post->post_category_id, $newStatus);

            $payload = [
                'status' => $newStatus,
                'updated_by' => $actorId,
                'last_edited_at' => now(),
            ];

            if ($newStatus === PostStatus::Published && ! $post->published_at) {
                $payload['published_at'] = now();
            }

            if ($newStatus !== PostStatus::Published) {
                $payload['is_featured'] = false;
                $payload['allow_on_homepage'] = false;
            }

            $post->update($payload);

            $this->recordStatusHistory($post, $oldStatus, $newStatus, $actorId, $note);

            Log::info('Post status changed', [
                'post_id' => $post->id,
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'actor_id' => $actorId,
            ]);

            return $post->fresh($this->detailRelations());
        });
    }

    public function updateFeature(Post $post, bool $isFeatured, ?bool $allowOnHomepage, int $actorId): Post
    {
        $allowHomepage = $allowOnHomepage ?? $post->allow_on_homepage;
        $this->assertFeatureRules($isFeatured, $post->status, $post->visibility, $allowHomepage);

        $post->update([
            'is_featured' => $isFeatured,
            'allow_on_homepage' => $allowHomepage,
            'updated_by' => $actorId,
            'last_edited_at' => now(),
        ]);

        Log::info('Post feature toggled', ['post_id' => $post->id, 'actor_id' => $actorId, 'is_featured' => $isFeatured]);

        return $post->fresh($this->detailRelations());
    }

    public function summary(bool $includeCategorySummary = false): array
    {
        $query = Post::query();

        $summary = [
            'total_posts' => (clone $query)->count(),
            'total_drafts' => (clone $query)->where('status', PostStatus::Draft)->count(),
            'total_pending_review' => (clone $query)->where('status', PostStatus::PendingReview)->count(),
            'total_published' => (clone $query)->where('status', PostStatus::Published)->count(),
            'total_unpublished' => (clone $query)->where('status', PostStatus::Unpublished)->count(),
            'total_archived' => (clone $query)->where('status', PostStatus::Archived)->count(),
            'total_featured' => (clone $query)->where('is_featured', true)->count(),
            'published_this_month' => (clone $query)
                ->where('status', PostStatus::Published)
                ->whereBetween('published_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'recent_post_count' => (clone $query)->where('created_at', '>=', now()->subDays(7))->count(),
            'by_content_type' => Post::query()->select('content_type', DB::raw('COUNT(*) as total'))
                ->groupBy('content_type')
                ->pluck('total', 'content_type')
                ->toArray(),
            'by_visibility' => Post::query()->select('visibility', DB::raw('COUNT(*) as total'))
                ->groupBy('visibility')
                ->pluck('total', 'visibility')
                ->toArray(),
            'by_category' => [],
        ];

        if ($includeCategorySummary) {
            $summary['by_category'] = Post::query()
                ->leftJoin('post_categories', 'post_categories.id', '=', 'posts.post_category_id')
                ->select('posts.post_category_id', 'post_categories.name', DB::raw('COUNT(posts.id) as total'))
                ->groupBy('posts.post_category_id', 'post_categories.name')
                ->get()
                ->map(fn ($item) => [
                    'post_category_id' => $item->post_category_id,
                    'post_category_name' => $item->name,
                    'total' => (int) $item->total,
                ])
                ->values()
                ->toArray();
        }

        return $summary;
    }

    public function delete(Post $post, int $actorId): void
    {
        if ($post->trashed()) {
            throw ValidationException::withMessages([
                'post' => ['Post is already deleted.'],
            ]);
        }

        $post->update(['updated_by' => $actorId]);
        $post->delete();

        Log::info('Post deleted', ['post_id' => $post->id, 'actor_id' => $actorId]);
    }

    public function restore(Post $post, int $actorId): Post
    {
        if (! $post->trashed()) {
            throw ValidationException::withMessages([
                'post' => ['Post is not deleted.'],
            ]);
        }

        $post->slug = $this->generateUniqueSlug($post->slug, $post->id);
        $post->restore();
        $post->update(['updated_by' => $actorId]);

        Log::info('Post restored', ['post_id' => $post->id, 'actor_id' => $actorId]);

        return $post->fresh($this->detailRelations());
    }

    public function listPublic(array $filters): LengthAwarePaginator
    {
        $query = Post::query()
            ->with(['category:id,name,slug', 'author:id,name', 'committee:id,name,slug'])
            ->where('status', PostStatus::Published)
            ->where('visibility', PostVisibility::Public)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['content_type'])) {
            $query->where('content_type', $filters['content_type']);
        }

        if (! empty($filters['post_category_id'])) {
            $query->where('post_category_id', (int) $filters['post_category_id']);
        }

        if (! empty($filters['category_slug'])) {
            $query->whereHas('category', fn ($cq) => $cq->where('slug', $filters['category_slug']));
        }

        if (! empty($filters['committee_id'])) {
            $query->where('committee_id', (int) $filters['committee_id']);
        }

        if ($filters['featured_only'] ?? false) {
            $query->where('is_featured', true);
        }

        $sortBy = $filters['sort_by'] ?? 'published_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        if (! empty($filters['limit'])) {
            $perPage = min((int) $filters['limit'], 20);
            return $query->paginate($perPage);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function detailPublicBySlug(string $slug): Post
    {
        return DB::transaction(function () use ($slug) {
            $post = Post::query()
                ->with(['category:id,name,slug', 'author:id,name', 'committee:id,name,slug'])
                ->where('slug', $slug)
                ->where('status', PostStatus::Published)
                ->where('visibility', PostVisibility::Public)
                ->where(function ($q) {
                    $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->firstOrFail();

            $post->increment('view_count');
            $post->refresh();

            return $post;
        });
    }

    public function featuredPublic(array $filters): LengthAwarePaginator
    {
        $query = Post::query()
            ->with(['category:id,name,slug', 'author:id,name', 'committee:id,name,slug'])
            ->where('status', PostStatus::Published)
            ->where('visibility', PostVisibility::Public)
            ->where('is_featured', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('allow_on_homepage')
            ->orderByDesc('published_at');

        return $query->paginate((int) ($filters['limit'] ?? 10));
    }

    public function publish(Post $post, int $actorId, ?string $note = null): Post
    {
        return $this->changeStatus($post, PostStatus::Published, $note, $actorId);
    }

    public function unpublish(Post $post, int $actorId, ?string $note = null): Post
    {
        return $this->changeStatus($post, PostStatus::Unpublished, $note, $actorId);
    }

    public function archive(Post $post, int $actorId, ?string $note = null): Post
    {
        return $this->changeStatus($post, PostStatus::Archived, $note, $actorId);
    }

    private function validateCategory(?int $categoryId, PostStatus $status): void
    {
        if (! $categoryId) {
            return;
        }

        $category = PostCategory::findOrFail($categoryId);

        if (! $category->is_active && $status === PostStatus::Published) {
            throw ValidationException::withMessages([
                'post_category_id' => ['Cannot publish post under an inactive category.'],
            ]);
        }
    }

    private function validateCommittee(?int $committeeId): void
    {
        if (! $committeeId) {
            return;
        }

        $committee = Committee::findOrFail($committeeId);

        if ($committee->status !== CommitteeStatus::Active && ! $committee->is_current) {
            throw ValidationException::withMessages([
                'committee_id' => ['Committee must be active or current for post targeting.'],
            ]);
        }
    }

    private function assertFeatureRules(bool $isFeatured, PostStatus $status, PostVisibility $visibility, bool $allowOnHomepage): void
    {
        if ($isFeatured && $status !== PostStatus::Published) {
            throw ValidationException::withMessages([
                'is_featured' => ['Only published posts can be featured.'],
            ]);
        }

        if ($allowOnHomepage && $visibility !== PostVisibility::Public) {
            throw ValidationException::withMessages([
                'allow_on_homepage' => ['Homepage posts must have public visibility.'],
            ]);
        }
    }

    private function assertPublishPermission(User $actor, PostStatus $status): void
    {
        if ($status === PostStatus::Published && ! $actor->can('post.publish')) {
            throw ValidationException::withMessages([
                'status' => ['You are not authorized to publish posts.'],
            ]);
        }
    }

    private function assertStatusTransitionAllowed(PostStatus $from, PostStatus $to): void
    {
        $allowed = match ($from) {
            PostStatus::Draft => [PostStatus::PendingReview, PostStatus::Published, PostStatus::Archived],
            PostStatus::PendingReview => [PostStatus::Published, PostStatus::Draft, PostStatus::Archived],
            PostStatus::Published => [PostStatus::Unpublished, PostStatus::Archived],
            PostStatus::Unpublished => [PostStatus::Published, PostStatus::Archived, PostStatus::Draft],
            PostStatus::Archived => [PostStatus::Draft, PostStatus::Unpublished],
        };

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ["Invalid status transition from {$from->value} to {$to->value}."],
            ]);
        }
    }

    private function generatePostNo(): string
    {
        $year = now()->year;
        $last = Post::withTrashed()
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('post_no');

        $next = 1;
        if ($last && preg_match('/PST-\d{4}-(\d{6})/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return sprintf('PST-%d-%06d', $year, $next);
    }

    private function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'post';
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
        $query = Post::withTrashed()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    private function storeFeaturedImage(mixed $image): ?string
    {
        if (! $image instanceof UploadedFile) {
            return null;
        }

        return $image->store('posts/featured-images', 'public');
    }

    private function replaceFeaturedImage(?string $oldPath, UploadedFile $newImage): string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $newImage->store('posts/featured-images', 'public');
    }

    private function recordStatusHistory(
        Post $post,
        ?PostStatus $oldStatus,
        PostStatus $newStatus,
        ?int $actorId,
        ?string $note = null
    ): void {
        PostStatusHistory::create([
            'post_id' => $post->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $actorId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    private function listRelations(): array
    {
        return [
            'category:id,name,slug',
            'committee:id,name,slug',
            'author:id,name,email',
        ];
    }

    private function detailRelations(): array
    {
        return [
            ...$this->listRelations(),
            'editor:id,name,email',
            'creator:id,name,email',
            'updater:id,name,email',
            'statusHistories.changedByUser:id,name,email',
        ];
    }
}
