<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelProfileUpdateRequestRequest;
use App\Http\Requests\MyProfileUpdateRequestListRequest;
use App\Http\Requests\StoreProfileUpdateRequestRequest;
use App\Http\Resources\ProfileUpdateRequestDetailResource;
use App\Http\Resources\ProfileUpdateRequestListResource;
use App\Services\ProfileUpdateRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MeProfileUpdateRequestController extends Controller
{
    public function __construct(private readonly ProfileUpdateRequestService $service)
    {
    }

    public function index(MyProfileUpdateRequestListRequest $request): JsonResponse
    {
        if (! auth()->user()->can('self.profile.request.view')) {
            return $this->error('You are not authorized to view profile update requests.', 403);
        }

        $paginator = $this->service->listMine($request->user(), $request->validated());

        return $this->success(
            ProfileUpdateRequestListResource::collection($paginator),
            'Profile update requests retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function store(StoreProfileUpdateRequestRequest $request): JsonResponse
    {
        if (! auth()->user()->can('self.profile.request.create')) {
            return $this->error('You are not authorized to create profile update requests.', 403);
        }

        try {
            $item = $this->service->create($request->user(), $request->validated());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new ProfileUpdateRequestDetailResource($item), 'Profile update request created successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        if (! auth()->user()->can('self.profile.request.view')) {
            return $this->error('You are not authorized to view profile update request details.', 403);
        }

        $item = $this->service->detailMine(auth()->user(), $id);

        return $this->success(new ProfileUpdateRequestDetailResource($item), 'Profile update request detail retrieved successfully.');
    }

    public function cancel(CancelProfileUpdateRequestRequest $request, int $id): JsonResponse
    {
        if (! auth()->user()->can('self.profile.request.create')) {
            return $this->error('You are not authorized to cancel profile update requests.', 403);
        }

        try {
            $item = $this->service->cancel($request->user(), $id, $request->validated('note'));
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new ProfileUpdateRequestDetailResource($item), 'Profile update request cancelled successfully.');
    }
}
