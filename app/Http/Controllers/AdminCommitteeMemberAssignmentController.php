<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommitteeMemberAssignmentListRequest;
use App\Http\Requests\CommitteeMembersListRequest;
use App\Http\Requests\CommitteeOfficeBearersListRequest;
use App\Http\Requests\MemberCommitteeAssignmentsListRequest;
use App\Http\Requests\StoreCommitteeMemberAssignmentRequest;
use App\Http\Requests\TransferCommitteeMemberAssignmentRequest;
use App\Http\Requests\UpdateCommitteeMemberAssignmentRequest;
use App\Http\Requests\UpdateCommitteeMemberAssignmentStatusRequest;
use App\Http\Resources\CommitteeMemberAssignmentDetailResource;
use App\Http\Resources\CommitteeMemberAssignmentListResource;
use App\Http\Resources\CommitteeMemberAssignmentsSummaryResource;
use App\Http\Resources\CommitteeMemberResource;
use App\Http\Resources\CommitteeOfficeBearerResource;
use App\Http\Resources\MemberCommitteeAssignmentResource;
use App\Models\CommitteeMemberAssignment;
use App\Services\CommitteeMemberAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminCommitteeMemberAssignmentController extends Controller
{
    public function __construct(private readonly CommitteeMemberAssignmentService $service)
    {
    }

    public function index(CommitteeMemberAssignmentListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', CommitteeMemberAssignment::class);

        $paginator = $this->service->list($request->validated());

        return $this->success(
            CommitteeMemberAssignmentListResource::collection($paginator),
            'Committee member assignments retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function store(StoreCommitteeMemberAssignmentRequest $request): JsonResponse
    {
        $this->authorize('create', CommitteeMemberAssignment::class);

        try {
            $assignment = $this->service->create($request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new CommitteeMemberAssignmentDetailResource($assignment), 'Committee member assignment created successfully.', 201);
    }

    public function show(int $assignment): JsonResponse
    {
        $model = CommitteeMemberAssignment::findOrFail($assignment);
        $this->authorize('view', $model);

        $detail = $this->service->detail($assignment);

        return $this->success(new CommitteeMemberAssignmentDetailResource($detail), 'Committee member assignment detail retrieved.');
    }

    public function update(UpdateCommitteeMemberAssignmentRequest $request, int $assignment): JsonResponse
    {
        $model = CommitteeMemberAssignment::findOrFail($assignment);
        $this->authorize('update', $model);

        try {
            $updated = $this->service->update($model, $request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new CommitteeMemberAssignmentDetailResource($updated), 'Committee member assignment updated successfully.');
    }

    public function updateStatus(UpdateCommitteeMemberAssignmentStatusRequest $request, int $assignment): JsonResponse
    {
        $model = CommitteeMemberAssignment::findOrFail($assignment);
        $this->authorize('updateStatus', $model);

        try {
            $updated = $this->service->updateStatus(
                $model,
                $request->validated('status'),
                $request->has('is_active') ? $request->boolean('is_active') : null,
                $request->input('note'),
                $request->user()->id
            );
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new CommitteeMemberAssignmentDetailResource($updated), 'Committee member assignment status updated successfully.');
    }

    public function transfer(TransferCommitteeMemberAssignmentRequest $request, int $assignment): JsonResponse
    {
        $model = CommitteeMemberAssignment::findOrFail($assignment);
        $this->authorize('transfer', $model);

        try {
            $newAssignment = $this->service->transfer($model, $request->validated(), $request->user()->id);
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new CommitteeMemberAssignmentDetailResource($newAssignment), 'Committee member assignment transferred successfully.');
    }

    public function summary(Request $request): JsonResponse
    {
        if (! $request->user()->can('committee.member.assignment.summary.view')) {
            return $this->error('You are not authorized to view assignment summary.', 403);
        }

        $summary = $this->service->summary(
            $request->boolean('include_position_summary'),
            $request->boolean('include_committee_summary')
        );

        return $this->success(new CommitteeMemberAssignmentsSummaryResource($summary), 'Committee member assignment summary retrieved successfully.');
    }

    public function committeeMembers(CommitteeMembersListRequest $request, int $committeeId): JsonResponse
    {
        if (! $request->user()->can('committee.member.assignment.view')) {
            return $this->error('You are not authorized to view committee members.', 403);
        }

        $paginator = $this->service->committeeMembers($committeeId, $request->validated());

        return $this->success(
            CommitteeMemberResource::collection($paginator),
            'Committee members retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function committeeOfficeBearers(CommitteeOfficeBearersListRequest $request, int $committeeId): JsonResponse
    {
        if (! $request->user()->can('committee.office-bearer.view')) {
            return $this->error('You are not authorized to view office bearers.', 403);
        }

        $paginator = $this->service->committeeOfficeBearers($committeeId, $request->validated());

        return $this->success(
            CommitteeOfficeBearerResource::collection($paginator),
            'Committee office bearers retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function memberAssignments(MemberCommitteeAssignmentsListRequest $request, int $memberId): JsonResponse
    {
        if (! $request->user()->can('committee.member.assignment.view')) {
            return $this->error('You are not authorized to view member assignments.', 403);
        }

        $paginator = $this->service->memberAssignments($memberId, $request->validated());

        return $this->success(
            MemberCommitteeAssignmentResource::collection($paginator),
            'Member committee assignments retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function destroy(int $assignment): JsonResponse
    {
        $model = CommitteeMemberAssignment::findOrFail($assignment);
        $this->authorize('delete', $model);

        try {
            $this->service->delete($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(null, 'Committee member assignment deleted successfully.');
    }

    public function restore(int $assignment): JsonResponse
    {
        $model = CommitteeMemberAssignment::withTrashed()->findOrFail($assignment);
        $this->authorize('restore', $model);

        try {
            $restored = $this->service->restore($model, auth()->id());
        } catch (ValidationException $e) {
            return $this->error('Validation failed.', 422, $e->errors());
        }

        return $this->success(new CommitteeMemberAssignmentDetailResource($restored), 'Committee member assignment restored successfully.');
    }
}
