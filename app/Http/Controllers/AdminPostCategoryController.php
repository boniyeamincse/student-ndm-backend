<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostCategoryListRequest;
use App\Http\Requests\StorePostCategoryRequest;
use App\Http\Requests\UpdatePostCategoryRequest;
use App\Http\Requests\UpdatePostCategoryStatusRequest;
use App\Http\Resources\PostCategoryResource;
use App\Models\PostCategory;
use App\Services\PostCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AdminPostCategoryController extends Controller
{
    public function __construct(private readonly PostCategoryService $service)
    {
    }

    public function index(PostCategoryListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PostCategory::class);

        $paginator = $this->service->list($request->validated());

        return $this->success(
            PostCategoryResource::collection($paginator),
            'Post categories retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function store(StorePostCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', PostCategory::class);

        try {
            $category = $this->service->create($request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostCategoryResource($category), 'Post category created successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $model = PostCategory::findOrFail($id);
        $this->authorize('view', $model);

        $category = $this->service->detail($id);

        return $this->success(new PostCategoryResource($category), 'Post category detail retrieved successfully.');
    }

    public function update(UpdatePostCategoryRequest $request, int $id): JsonResponse
    {
        $model = PostCategory::findOrFail($id);
        $this->authorize('update', $model);

        try {
            $category = $this->service->update($model, $request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostCategoryResource($category), 'Post category updated successfully.');
    }

    public function updateStatus(UpdatePostCategoryStatusRequest $request, int $id): JsonResponse
    {
        $model = PostCategory::findOrFail($id);
        $this->authorize('update', $model);

        try {
            $category = $this->service->updateStatus($model, $request->boolean('is_active'), $request->input('note'), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostCategoryResource($category), 'Post category status updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $model = PostCategory::findOrFail($id);
        $this->authorize('delete', $model);

        try {
            $this->service->delete($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(null, 'Post category deleted successfully.');
    }

    public function restore(int $id): JsonResponse
    {
        $model = PostCategory::withTrashed()->findOrFail($id);
        $this->authorize('restore', $model);

        try {
            $category = $this->service->restore($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PostCategoryResource($category), 'Post category restored successfully.');
    }
}
