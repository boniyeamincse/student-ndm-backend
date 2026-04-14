<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ApproveMembershipApplicationRequest;
use App\Http\Requests\Api\V1\HoldMembershipApplicationRequest;
use App\Http\Requests\Api\V1\RejectMembershipApplicationRequest;
use App\Http\Requests\Api\V1\ReviewMembershipApplicationRequest;
use App\Http\Resources\Api\V1\MemberResource;
use App\Http\Resources\Api\V1\MembershipApplicationListResource;
use App\Http\Resources\Api\V1\MembershipApplicationResource;
use App\Models\MembershipApplication;
use App\Services\MembershipApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMembershipApplicationController extends Controller
{
    public function __construct(private readonly MembershipApplicationService $service)
    {
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/admin/membership-applications
     * Paginated, searchable, filterable list.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MembershipApplication::class);

        $query = MembershipApplication::query();

        // Search: name, mobile, email, application_no
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name',      'like', "%{$search}%")
                  ->orWhere('mobile',       'like', "%{$search}%")
                  ->orWhere('email',        'like', "%{$search}%")
                  ->orWhere('application_no', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter by district / division
        if ($district = $request->query('district')) {
            $query->where('district_name', $district);
        }
        if ($division = $request->query('division')) {
            $query->where('division_name', $division);
        }

        // Filter by date range (created_at)
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $applications = $query->latest()->paginate(20);

        return $this->success(
            data: MembershipApplicationListResource::collection($applications),
            message: 'Applications retrieved.',
            meta: [
                'current_page' => $applications->currentPage(),
                'last_page'    => $applications->lastPage(),
                'per_page'     => $applications->perPage(),
                'total'        => $applications->total(),
            ],
        );
    }

    // ─── Detail ───────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/admin/membership-applications/{id}
     */
    public function show(int $id): JsonResponse
    {
        $application = MembershipApplication::with([
            'reviewer',
            'approver',
            'rejector',
            'statusHistories.changedByUser',
            'member',
        ])->findOrFail($id);

        $this->authorize('view', $application);

        return $this->success(
            data: new MembershipApplicationResource($application),
            message: 'Application detail retrieved.',
        );
    }

    // ─── Review ───────────────────────────────────────────────────────────────

    /**
     * PUT /api/v1/admin/membership-applications/{id}/review
     */
    public function review(ReviewMembershipApplicationRequest $request, int $id): JsonResponse
    {
        $application = MembershipApplication::findOrFail($id);

        $updated = $this->service->markUnderReview(
            $application,
            $request->user(),
            $request->remarks,
        );

        return $this->success(
            data: new MembershipApplicationResource($updated),
            message: 'Application marked as under review.',
        );
    }

    // ─── Approve ──────────────────────────────────────────────────────────────

    /**
     * PUT /api/v1/admin/membership-applications/{id}/approve
     */
    public function approve(ApproveMembershipApplicationRequest $request, int $id): JsonResponse
    {
        $application = MembershipApplication::findOrFail($id);

        [, $member] = $this->service->approve(
            $application,
            $request->user(),
            $request->remarks,
        );

        return $this->success(
            data: [
                'application' => new MembershipApplicationResource($application->refresh()),
                'member'      => new MemberResource($member),
            ],
            message: 'Application approved. Member account has been created and a password setup link has been sent.',
        );
    }

    // ─── Reject ───────────────────────────────────────────────────────────────

    /**
     * PUT /api/v1/admin/membership-applications/{id}/reject
     */
    public function reject(RejectMembershipApplicationRequest $request, int $id): JsonResponse
    {
        $application = MembershipApplication::findOrFail($id);

        $updated = $this->service->reject(
            $application,
            $request->user(),
            $request->rejection_reason,
        );

        return $this->success(
            data: new MembershipApplicationResource($updated),
            message: 'Application rejected.',
        );
    }

    // ─── Hold ─────────────────────────────────────────────────────────────────

    /**
     * PUT /api/v1/admin/membership-applications/{id}/hold
     */
    public function hold(HoldMembershipApplicationRequest $request, int $id): JsonResponse
    {
        $application = MembershipApplication::findOrFail($id);

        $updated = $this->service->hold(
            $application,
            $request->user(),
            $request->remarks,
        );

        return $this->success(
            data: new MembershipApplicationResource($updated),
            message: 'Application placed on hold.',
        );
    }

    // ─── Soft Delete ──────────────────────────────────────────────────────────

    /**
     * DELETE /api/v1/admin/membership-applications/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $application = MembershipApplication::findOrFail($id);
        $this->authorize('delete', $application);

        $application->delete();

        return $this->success(message: 'Application deleted.');
    }

    /**
     * PUT /api/v1/admin/membership-applications/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        $application = MembershipApplication::withTrashed()->findOrFail($id);
        $this->authorize('restore', $application);

        $application->restore();

        return $this->success(message: 'Application restored.');
    }
}
