<?php

namespace App\Http\Controllers;

use App\Enum\PostContentType;
use App\Http\Requests\PublicPostListRequest;
use App\Http\Resources\PostCategoryResource;
use App\Http\Resources\PublicPostDetailResource;
use App\Http\Resources\PublicPostListResource;
use App\Models\PostCategory;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;

class PublicPostController extends Controller
{
    public function __construct(private readonly PostService $service)
    {
    }

    public function index(PublicPostListRequest $request): JsonResponse
    {
        $paginator = $this->service->listPublic($request->validated());

        return $this->success(
            PublicPostListResource::collection($paginator),
            'Public posts retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function show(string $slug): JsonResponse
    {
        $post = $this->service->detailPublicBySlug($slug);

        return $this->success(new PublicPostDetailResource($post), 'Public post detail retrieved successfully.');
    }

    public function categories(): JsonResponse
    {
        $categories = PostCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->success(PostCategoryResource::collection($categories), 'Public post categories retrieved successfully.');
    }

    public function featured(PublicPostListRequest $request): JsonResponse
    {
        $paginator = $this->service->featuredPublic($request->validated());

        return $this->success(
            PublicPostListResource::collection($paginator),
            'Featured posts retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function news(PublicPostListRequest $request): JsonResponse
    {
        $filters = [...$request->validated(), 'content_type' => PostContentType::News->value];
        $paginator = $this->service->listPublic($filters);

        return $this->success(
            PublicPostListResource::collection($paginator),
            'Public news posts retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function blogs(PublicPostListRequest $request): JsonResponse
    {
        $filters = [...$request->validated(), 'content_type' => PostContentType::Blog->value];
        $paginator = $this->service->listPublic($filters);

        return $this->success(
            PublicPostListResource::collection($paginator),
            'Public blog posts retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }
}
