<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommitteeListRequest;
use App\Http\Requests\StoreCommitteeRequest;
use App\Http\Requests\UpdateCommitteeRequest;
use App\Http\Requests\UpdateCommitteeStatusRequest;
use App\Http\Resources\CommitteeDetailResource;
use App\Http\Resources\CommitteeListResource;
use App\Http\Resources\CommitteeTreeResource;
use App\Http\Resources\CommitteesSummaryResource;
use App\Models\Committee;
use App\Services\CommitteeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminCommitteeController extends Controller
{
    public function __construct(private readonly CommitteeService $service)
    {
    }

    public function index(CommitteeListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Committee::class);

        $paginator = $this->service->list($request->validated());

        return $this->success(
            CommitteeListResource::collection($paginator),
            'Committees retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function store(StoreCommitteeRequest $request): JsonResponse
    {
        $this->authorize('create', Committee::class);

        try {
            $committee = $this->service->create($request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new CommitteeDetailResource($committee), 'Committee created successfully.', 201);
    }

    public function show(int $committee): JsonResponse
    {
        $model = Committee::findOrFail($committee);
        $this->authorize('view', $model);

        $detail = $this->service->detail($committee);

        return $this->success(new CommitteeDetailResource($detail), 'Committee detail retrieved.');
    }

    public function update(UpdateCommitteeRequest $request, int $committee): JsonResponse
    {
        $model = Committee::findOrFail($committee);
        $this->authorize('update', $model);

        try {
            $updated = $this->service->update($model, $request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new CommitteeDetailResource($updated), 'Committee updated successfully.');
    }

    public function updateStatus(UpdateCommitteeStatusRequest $request, int $committee): JsonResponse
    {
        $model = Committee::findOrFail($committee);
        $this->authorize('updateStatus', $model);

        try {
            $updated = $this->service->updateStatus(
                $model,
                $request->validated('status'),
                $request->validated('note'),
                $request->user()->id
            );
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new CommitteeDetailResource($updated), 'Committee status updated successfully.');
    }

    public function summary(Request $request): JsonResponse
    {
        if (! $request->user()->can('committee.summary.view')) {
            return $this->error('You are not authorized to view committee summary.', 403);
        }

        $includeDivision = $request->boolean('include_division_summary');
        $summary = $this->service->summary($includeDivision);

        return $this->success(new CommitteesSummaryResource($summary), 'Committees summary retrieved successfully.');
    }

    public function tree(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Committee::class);

        $filters = [
            'root_committee_id' => $request->query('root_committee_id'),
            'committee_type_id' => $request->query('committee_type_id'),
            'status' => $request->query('status'),
            'is_current' => $request->query('is_current'),
        ];

        try {
            $tree = $this->service->tree($filters);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(CommitteeTreeResource::collection($tree), 'Committees tree retrieved successfully.');
    }

    public function destroy(int $committee): JsonResponse
    {
        $model = Committee::findOrFail($committee);
        $this->authorize('delete', $model);

        try {
            $this->service->delete($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(null, 'Committee deleted successfully.');
    }

    public function restore(int $committee): JsonResponse
    {
        $model = Committee::withTrashed()->findOrFail($committee);
        $this->authorize('restore', $model);

        $restored = $this->service->restore($model, auth()->id());

        return $this->success(new CommitteeDetailResource($restored), 'Committee restored successfully.');
    }
}
