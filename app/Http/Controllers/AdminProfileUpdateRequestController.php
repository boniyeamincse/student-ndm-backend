<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminProfileUpdateRequestListRequest;
use App\Http\Requests\ApproveProfileUpdateRequestRequest;
use App\Http\Requests\RejectProfileUpdateRequestRequest;
use App\Http\Resources\ProfileUpdateRequestDetailResource;
use App\Http\Resources\ProfileUpdateRequestListResource;
use App\Services\ProfileUpdateRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AdminProfileUpdateRequestController extends Controller
{
    public function __construct(private readonly ProfileUpdateRequestService $service)
    {
    }

    public function index(AdminProfileUpdateRequestListRequest $request): JsonResponse
    {
        if (! auth()->user()->can('profile.request.view')) {
            return $this->error('You are not authorized to view profile update requests.', 403);
        }

        $paginator = $this->service->adminList($request->validated());

        return $this->success(
            ProfileUpdateRequestListResource::collection($paginator),
            'Admin profile update requests retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function show(int $id): JsonResponse
    {
        if (! auth()->user()->can('profile.request.view')) {
            return $this->error('You are not authorized to view profile update request details.', 403);
        }

        $item = $this->service->adminDetail($id);

        return $this->success(new ProfileUpdateRequestDetailResource($item), 'Admin profile update request detail retrieved successfully.');
    }

    public function approve(ApproveProfileUpdateRequestRequest $request, int $id): JsonResponse
    {
        if (! auth()->user()->can('profile.request.approve')) {
            return $this->error('You are not authorized to approve profile update requests.', 403);
        }

        try {
            $item = $this->service->approve($id, auth()->id(), $request->validated('note'));
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new ProfileUpdateRequestDetailResource($item), 'Profile update request approved successfully.');
    }

    public function reject(RejectProfileUpdateRequestRequest $request, int $id): JsonResponse
    {
        if (! auth()->user()->can('profile.request.reject')) {
            return $this->error('You are not authorized to reject profile update requests.', 403);
        }

        try {
            $item = $this->service->reject($id, auth()->id(), $request->validated('rejection_reason'), $request->validated('note'));
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new ProfileUpdateRequestDetailResource($item), 'Profile update request rejected successfully.');
    }
}
