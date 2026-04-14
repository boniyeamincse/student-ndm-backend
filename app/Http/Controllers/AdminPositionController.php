<?php

namespace App\Http\Controllers;

use App\Http\Requests\PositionListRequest;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Http\Requests\UpdatePositionStatusRequest;
use App\Http\Resources\PositionDetailResource;
use App\Http\Resources\PositionListResource;
use App\Http\Resources\PositionsSummaryResource;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminPositionController extends Controller
{
    public function __construct(private readonly PositionService $service)
    {
    }

    public function index(PositionListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Position::class);

        $paginator = $this->service->list($request->validated());

        return $this->success(
            PositionListResource::collection($paginator),
            'Positions retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $this->authorize('create', Position::class);

        if ($request->filled('committee_type_ids') && ! $request->user()->can('position.committee-type.map')) {
            return $this->error('You are not authorized to map positions to committee types.', 403);
        }

        try {
            $position = $this->service->create($request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PositionDetailResource($position), 'Position created successfully.', 201);
    }

    public function show(int $position): JsonResponse
    {
        $model = Position::findOrFail($position);
        $this->authorize('view', $model);

        $detail = $this->service->detail($position);

        return $this->success(new PositionDetailResource($detail), 'Position detail retrieved.');
    }

    public function update(UpdatePositionRequest $request, int $position): JsonResponse
    {
        $model = Position::findOrFail($position);
        $this->authorize('update', $model);

        if ($request->filled('committee_type_ids') && ! $request->user()->can('position.committee-type.map')) {
            return $this->error('You are not authorized to map positions to committee types.', 403);
        }

        try {
            $updated = $this->service->update($model, $request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PositionDetailResource($updated), 'Position updated successfully.');
    }

    public function updateStatus(UpdatePositionStatusRequest $request, int $position): JsonResponse
    {
        $model = Position::findOrFail($position);
        $this->authorize('updateStatus', $model);

        try {
            $updated = $this->service->updateStatus(
                $model,
                $request->boolean('is_active'),
                $request->input('note'),
                $request->user()->id
            );
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new PositionDetailResource($updated), 'Position status updated successfully.');
    }

    public function summary(Request $request): JsonResponse
    {
        if (! $request->user()->can('position.summary.view')) {
            return $this->error('You are not authorized to view positions summary.', 403);
        }

        $includeCommitteeTypeSummary = $request->boolean('include_committee_type_summary');
        $summary = $this->service->summary($includeCommitteeTypeSummary);

        return $this->success(new PositionsSummaryResource($summary), 'Positions summary retrieved successfully.');
    }

    public function destroy(int $position): JsonResponse
    {
        $model = Position::findOrFail($position);
        $this->authorize('delete', $model);

        try {
            $this->service->delete($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(null, 'Position deleted successfully.');
    }

    public function restore(int $position): JsonResponse
    {
        $model = Position::withTrashed()->findOrFail($position);
        $this->authorize('restore', $model);

        $restored = $this->service->restore($model, auth()->id());

        return $this->success(new PositionDetailResource($restored), 'Position restored successfully.');
    }
}
