<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommitteeTypeListRequest;
use App\Http\Requests\StoreCommitteeTypeRequest;
use App\Http\Requests\UpdateCommitteeTypeRequest;
use App\Http\Resources\CommitteeTypeResource;
use App\Models\CommitteeType;
use App\Services\CommitteeTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AdminCommitteeTypeController extends Controller
{
    public function __construct(private readonly CommitteeTypeService $service)
    {
    }

    public function index(CommitteeTypeListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', CommitteeType::class);

        $paginator = $this->service->list($request->validated());

        return $this->success(
            CommitteeTypeResource::collection($paginator),
            'Committee types retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function store(StoreCommitteeTypeRequest $request): JsonResponse
    {
        $this->authorize('create', CommitteeType::class);

        $type = $this->service->create($request->validated(), $request->user()->id);

        return $this->success(new CommitteeTypeResource($type), 'Committee type created successfully.', 201);
    }

    public function show(int $committeeType): JsonResponse
    {
        $type = CommitteeType::findOrFail($committeeType);
        $this->authorize('view', $type);

        return $this->success(new CommitteeTypeResource($type), 'Committee type detail retrieved.');
    }

    public function update(UpdateCommitteeTypeRequest $request, int $committeeType): JsonResponse
    {
        $type = CommitteeType::findOrFail($committeeType);
        $this->authorize('update', $type);

        $updated = $this->service->update($type, $request->validated(), $request->user()->id);

        return $this->success(new CommitteeTypeResource($updated), 'Committee type updated successfully.');
    }

    public function destroy(int $committeeType): JsonResponse
    {
        $type = CommitteeType::findOrFail($committeeType);
        $this->authorize('delete', $type);

        try {
            $this->service->delete($type, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(null, 'Committee type deleted successfully.');
    }

    public function restore(int $committeeType): JsonResponse
    {
        $type = CommitteeType::withTrashed()->findOrFail($committeeType);
        $this->authorize('restore', $type);

        $restored = $this->service->restore($type, auth()->id());

        return $this->success(new CommitteeTypeResource($restored), 'Committee type restored successfully.');
    }
}
