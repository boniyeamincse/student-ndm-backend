<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicNoticeListRequest;
use App\Http\Resources\PublicNoticeDetailResource;
use App\Http\Resources\PublicNoticeListResource;
use App\Services\NoticeService;
use Illuminate\Http\JsonResponse;

class PublicNoticeController extends Controller
{
    public function __construct(private readonly NoticeService $service)
    {
    }

    public function index(PublicNoticeListRequest $request): JsonResponse
    {
        $paginator = $this->service->listPublic($request->validated());

        return $this->success(
            PublicNoticeListResource::collection($paginator),
            'Public notices retrieved successfully.',
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
        $notice = $this->service->detailPublicBySlug($slug);

        return $this->success(new PublicNoticeDetailResource($notice), 'Public notice detail retrieved successfully.');
    }

    public function pinned(PublicNoticeListRequest $request): JsonResponse
    {
        $paginator = $this->service->listPublic($request->validated(), true);

        return $this->success(
            PublicNoticeListResource::collection($paginator),
            'Pinned public notices retrieved successfully.',
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
