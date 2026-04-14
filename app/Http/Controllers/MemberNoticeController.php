<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberNoticeListRequest;
use App\Http\Resources\MemberNoticeListResource;
use App\Http\Resources\PublicNoticeDetailResource;
use App\Services\NoticeService;
use Illuminate\Http\JsonResponse;

class MemberNoticeController extends Controller
{
    public function __construct(private readonly NoticeService $service)
    {
    }

    public function index(MemberNoticeListRequest $request): JsonResponse
    {
        $paginator = $this->service->listMemberVisible($request->user(), $request->validated());

        return $this->success(
            MemberNoticeListResource::collection($paginator),
            'Member notices retrieved successfully.',
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
        $notice = $this->service->detailMemberBySlug(auth()->user(), $slug);

        return $this->success(new PublicNoticeDetailResource($notice), 'Member notice detail retrieved successfully.');
    }
}
