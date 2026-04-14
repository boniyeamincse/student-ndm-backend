<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberListRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Requests\UpdateMemberStatusRequest;
use App\Http\Resources\MemberDetailResource;
use App\Http\Resources\MemberListResource;
use App\Models\Member;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMemberController extends Controller
{
    public function __construct(private readonly MemberService $service) {}

    /**
     * GET /api/v1/admin/members
     * List all members with filters and pagination.
     */
    public function index(MemberListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Member::class);

        $paginator = $this->service->list($request);

        return $this->success(
            MemberListResource::collection($paginator),
            'Members retrieved successfully.',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]
        );
    }

    /**
     * GET /api/v1/admin/members/{member}
     * Full member detail with status history.
     */
    public function show(int $member): JsonResponse
    {
        $memberModel = Member::findOrFail($member);
        $this->authorize('view', $memberModel);

        $memberModel = $this->service->detail($member);

        return $this->success(
            new MemberDetailResource($memberModel),
            'Member retrieved successfully.'
        );
    }

    /**
     * PUT /api/v1/admin/members/{member}
     * Update member profile fields.
     */
    public function update(UpdateMemberRequest $request, int $member): JsonResponse
    {
        $memberModel = Member::findOrFail($member);
        $this->authorize('update', $memberModel);

        $updated = $this->service->update($memberModel, $request, $request->user()->id);

        return $this->success(
            new MemberDetailResource($updated),
            'Member updated successfully.'
        );
    }

    /**
     * PATCH /api/v1/admin/members/{member}/status
     * Change member status and record history.
     */
    public function updateStatus(UpdateMemberStatusRequest $request, int $member): JsonResponse
    {
        $memberModel = Member::findOrFail($member);
        $this->authorize('update', $memberModel);

        try {
            $updated = $this->service->updateStatus($memberModel, $request, $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(
            new MemberDetailResource($updated),
            'Member status updated successfully.'
        );
    }

    /**
     * GET /api/v1/admin/members-summary
     * Aggregate stats: total, by-status, by-gender, optional by-division.
     */
    public function summary(Request $request): JsonResponse
    {
        if (! $request->user()->can('member.summary.view')) {
            return $this->error('You are not authorized to view member summary.', 403);
        }

        $withDivision = $request->boolean('with_division');
        $data         = $this->service->summary($withDivision);

        return $this->success($data, 'Member summary retrieved successfully.');
    }

    /**
     * DELETE /api/v1/admin/members/{member}
     * Soft-delete a member.
     */
    public function destroy(int $member): JsonResponse
    {
        $memberModel = Member::findOrFail($member);
        $this->authorize('delete', $memberModel);

        $memberModel->delete();

        return $this->success(null, 'Member deleted successfully.');
    }

    /**
     * PUT /api/v1/admin/members/{member}/restore
     * Restore a soft-deleted member.
     */
    public function restore(int $member): JsonResponse
    {
        $memberModel = Member::withTrashed()->findOrFail($member);
        $this->authorize('restore', $memberModel);

        $memberModel->restore();

        return $this->success(
            new MemberDetailResource($memberModel),
            'Member restored successfully.'
        );
    }
}
