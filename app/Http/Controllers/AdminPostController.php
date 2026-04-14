<?php

namespace App\Http\Controllers;

use App\Enum\PostStatus;
use App\Http\Requests\PostListRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostFeatureRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Requests\UpdatePostStatusRequest;
use App\Http\Resources\PostDetailResource;
use App\Http\Resources\PostListResource;
use App\Http\Resources\PostsSummaryResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminPostController extends Controller
{
    public function __construct(private readonly PostService $service)
    {
    }

    public function index(PostListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Post::class);

        $paginator = $this->service->list($request->validated());

        return $this->success(
            PostListResource::collection($paginator),
            'Posts retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        try {
            $post = $this->service->create($request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostDetailResource($post), 'Post created successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $model = Post::findOrFail($id);
        $this->authorize('view', $model);

        $post = $this->service->detail($id);

        return $this->success(new PostDetailResource($post), 'Post detail retrieved successfully.');
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $model = Post::findOrFail($id);
        $this->authorize('update', $model);

        try {
            $post = $this->service->update($model, $request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostDetailResource($post), 'Post updated successfully.');
    }

    public function updateStatus(UpdatePostStatusRequest $request, int $id): JsonResponse
    {
        $model = Post::findOrFail($id);
        $this->authorize('update', $model);

        try {
            $post = $this->service->changeStatus(
                $model,
                PostStatus::from($request->validated('status')),
                $request->input('note'),
                $request->user()->id
            );
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostDetailResource($post), 'Post status updated successfully.');
    }

    public function updateFeature(UpdatePostFeatureRequest $request, int $id): JsonResponse
    {
        $model = Post::findOrFail($id);
        $this->authorize('feature', $model);

        try {
            $post = $this->service->updateFeature(
                $model,
                $request->boolean('is_featured'),
                $request->has('allow_on_homepage') ? $request->boolean('allow_on_homepage') : null,
                $request->user()->id
            );
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostDetailResource($post), 'Post feature state updated successfully.');
    }

    public function summary(Request $request): JsonResponse
    {
        if (! $request->user()->can('post.summary.view')) {
            return $this->error('You are not authorized to view post summary.', 403);
        }

        $summary = $this->service->summary($request->boolean('include_category_summary'));

        return $this->success(new PostsSummaryResource($summary), 'Post summary retrieved successfully.');
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $model = Post::findOrFail($id);
        $this->authorize('publish', $model);

        try {
            $post = $this->service->publish($model, $request->user()->id, $request->input('note'));
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostDetailResource($post), 'Post published successfully.');
    }

    public function unpublish(Request $request, int $id): JsonResponse
    {
        $model = Post::findOrFail($id);
        $this->authorize('unpublish', $model);

        try {
            $post = $this->service->unpublish($model, $request->user()->id, $request->input('note'));
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostDetailResource($post), 'Post unpublished successfully.');
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $model = Post::findOrFail($id);
        $this->authorize('archive', $model);

        try {
            $post = $this->service->archive($model, $request->user()->id, $request->input('note'));
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostDetailResource($post), 'Post archived successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $model = Post::findOrFail($id);
        $this->authorize('delete', $model);

        try {
            $this->service->delete($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(null, 'Post deleted successfully.');
    }

    public function restore(int $id): JsonResponse
    {
        $model = Post::withTrashed()->findOrFail($id);
        $this->authorize('restore', $model);

        try {
            $post = $this->service->restore($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostDetailResource($post), 'Post restored successfully.');
    }
}
