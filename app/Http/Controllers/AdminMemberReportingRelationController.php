<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommitteeHierarchyTreeRequest;
use App\Http\Requests\LeaderListRequest;
use App\Http\Requests\MemberReportingRelationListRequest;
use App\Http\Requests\StoreMemberReportingRelationRequest;
use App\Http\Requests\SubordinatesListRequest;
use App\Http\Requests\UpdateMemberReportingRelationRequest;
use App\Http\Requests\UpdateMemberReportingRelationStatusRequest;
use App\Http\Resources\CommitteeHierarchyTreeResource;
use App\Http\Resources\LeaderResource;
use App\Http\Resources\MemberReportingRelationDetailResource;
use App\Http\Resources\MemberReportingRelationListResource;
use App\Http\Resources\MemberReportingRelationsSummaryResource;
use App\Http\Resources\SubordinateResource;
use App\Models\MemberReportingRelation;
use App\Services\MemberReportingRelationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminMemberReportingRelationController extends Controller
{
    public function __construct(private readonly MemberReportingRelationService $service)
    {
    }

    public function index(MemberReportingRelationListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', MemberReportingRelation::class);

        $paginator = $this->service->list($request->validated());

        return $this->success(
            MemberReportingRelationListResource::collection($paginator),
            'Member reporting relations retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function store(StoreMemberReportingRelationRequest $request): JsonResponse
    {
        $this->authorize('create', MemberReportingRelation::class);

        try {
            $relation = $this->service->create($request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new MemberReportingRelationDetailResource($relation), 'Member reporting relation created successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $model = MemberReportingRelation::findOrFail($id);
        $this->authorize('view', $model);

        $relation = $this->service->detail($id);

        return $this->success(new MemberReportingRelationDetailResource($relation), 'Member reporting relation detail retrieved successfully.');
    }

    public function update(UpdateMemberReportingRelationRequest $request, int $id): JsonResponse
    {
        $model = MemberReportingRelation::findOrFail($id);
        $this->authorize('update', $model);

        try {
            $relation = $this->service->update($model, $request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new MemberReportingRelationDetailResource($relation), 'Member reporting relation updated successfully.');
    }

    public function updateStatus(UpdateMemberReportingRelationStatusRequest $request, int $id): JsonResponse
    {
        $model = MemberReportingRelation::findOrFail($id);
        $this->authorize('updateStatus', $model);

        try {
            $relation = $this->service->updateStatus($model, $request->boolean('is_active'), $request->input('note'), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new MemberReportingRelationDetailResource($relation), 'Member reporting relation status updated successfully.');
    }

    public function summary(Request $request): JsonResponse
    {
        if (! $request->user()->can('hierarchy.summary.view')) {
            return $this->error('You are not authorized to view hierarchy summary.', 403);
        }

        $summary = $this->service->summary($request->boolean('include_committee_summary'));

        return $this->success(new MemberReportingRelationsSummaryResource($summary), 'Member reporting relation summary retrieved successfully.');
    }

    public function leader(LeaderListRequest $request, int $assignmentId): JsonResponse
    {
        if (! $request->user()->can('hierarchy.view.detail')) {
            return $this->error('You are not authorized to view leader details.', 403);
        }

        $leader = $this->service->leader($assignmentId, $request->validated());

        if (! $leader) {
            return $this->success(null, 'No active leader found for this assignment.');
        }

        return $this->success(new LeaderResource($leader), 'Leader retrieved successfully.');
    }

    public function subordinates(SubordinatesListRequest $request, int $assignmentId): JsonResponse
    {
        if (! $request->user()->can('hierarchy.view')) {
            return $this->error('You are not authorized to view subordinates.', 403);
        }

        $paginator = $this->service->subordinates($assignmentId, $request->validated());

        return $this->success(
            SubordinateResource::collection($paginator),
            'Subordinates retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function hierarchyTree(CommitteeHierarchyTreeRequest $request, int $committeeId): JsonResponse
    {
        if (! $request->user()->can('hierarchy.tree.view')) {
            return $this->error('You are not authorized to view hierarchy tree.', 403);
        }

        $tree = $this->service->committeeHierarchyTree($committeeId, $request->validated());

        return $this->success(CommitteeHierarchyTreeResource::collection(collect($tree)), 'Committee hierarchy tree retrieved successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $model = MemberReportingRelation::findOrFail($id);
        $this->authorize('delete', $model);

        try {
            $this->service->delete($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(null, 'Member reporting relation deleted successfully.');
    }

    public function restore(int $id): JsonResponse
    {
        $model = MemberReportingRelation::withTrashed()->findOrFail($id);
        $this->authorize('restore', $model);

        try {
            $relation = $this->service->restore($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new MemberReportingRelationDetailResource($relation), 'Member reporting relation restored successfully.');
    }
}
