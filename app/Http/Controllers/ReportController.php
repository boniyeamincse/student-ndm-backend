<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivitySummaryReportRequest;
use App\Http\Requests\CommitteeAssignmentsReportRequest;
use App\Http\Requests\CommitteesReportRequest;
use App\Http\Requests\MembershipApplicationsReportRequest;
use App\Http\Requests\MembersReportRequest;
use App\Http\Requests\NoticesReportRequest;
use App\Http\Requests\PostsReportRequest;
use App\Http\Requests\ProfileUpdateRequestsReportRequest;
use App\Http\Requests\ReportingHierarchyReportRequest;
use App\Http\Requests\ReportsOverviewRequest;
use App\Http\Resources\ActivitySummaryReportResource;
use App\Http\Resources\CommitteeAssignmentsReportResource;
use App\Http\Resources\CommitteesReportResource;
use App\Http\Resources\MembershipApplicationsReportResource;
use App\Http\Resources\MembersReportResource;
use App\Http\Resources\NoticesReportResource;
use App\Http\Resources\PostsReportResource;
use App\Http\Resources\ProfileUpdateRequestsReportResource;
use App\Http\Resources\ReportingHierarchyReportResource;
use App\Http\Resources\ReportsOverviewResource;
use App\Services\ActivityFeedService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ActivityFeedService $activityFeedService,
    ) {
    }

    // ─── GET /api/v1/reports/membership-applications ──────────────────────────

    public function membershipApplications(MembershipApplicationsReportRequest $request): JsonResponse
    {
        if (! auth()->user()->can('report.membership.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $data = $this->reportService->membershipApplicationsReport($request->validated());

        return $this->success(
            new MembershipApplicationsReportResource($data),
            'Membership applications report retrieved.',
        );
    }

    // ─── GET /api/v1/reports/members ──────────────────────────────────────────

    public function members(MembersReportRequest $request): JsonResponse
    {
        if (! auth()->user()->can('report.members.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $data = $this->reportService->membersReport($request->validated());

        return $this->success(
            new MembersReportResource($data),
            'Members report retrieved.',
        );
    }

    // ─── GET /api/v1/reports/committees ───────────────────────────────────────

    public function committees(CommitteesReportRequest $request): JsonResponse
    {
        if (! auth()->user()->can('report.committees.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $data = $this->reportService->committeesReport($request->validated());

        return $this->success(
            new CommitteesReportResource($data),
            'Committees report retrieved.',
        );
    }

    // ─── GET /api/v1/reports/committee-assignments ────────────────────────────

    public function committeeAssignments(CommitteeAssignmentsReportRequest $request): JsonResponse
    {
        if (! auth()->user()->can('report.assignments.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $data = $this->reportService->committeeAssignmentsReport($request->validated());

        return $this->success(
            new CommitteeAssignmentsReportResource($data),
            'Committee assignments report retrieved.',
        );
    }

    // ─── GET /api/v1/reports/reporting-hierarchy ──────────────────────────────

    public function reportingHierarchy(ReportingHierarchyReportRequest $request): JsonResponse
    {
        if (! auth()->user()->can('report.hierarchy.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $data = $this->reportService->reportingHierarchyReport($request->validated());

        return $this->success(
            new ReportingHierarchyReportResource($data),
            'Reporting hierarchy report retrieved.',
        );
    }

    // ─── GET /api/v1/reports/posts ────────────────────────────────────────────

    public function posts(PostsReportRequest $request): JsonResponse
    {
        if (! auth()->user()->can('report.posts.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $data = $this->reportService->postsReport($request->validated());

        return $this->success(
            new PostsReportResource($data),
            'Posts report retrieved.',
        );
    }

    // ─── GET /api/v1/reports/notices ──────────────────────────────────────────

    public function notices(NoticesReportRequest $request): JsonResponse
    {
        if (! auth()->user()->can('report.notices.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $data = $this->reportService->noticesReport($request->validated());

        return $this->success(
            new NoticesReportResource($data),
            'Notices report retrieved.',
        );
    }

    // ─── GET /api/v1/reports/profile-update-requests ─────────────────────────

    public function profileUpdateRequests(ProfileUpdateRequestsReportRequest $request): JsonResponse
    {
        if (! auth()->user()->can('report.profile-requests.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $data = $this->reportService->profileUpdateRequestsReport($request->validated());

        return $this->success(
            new ProfileUpdateRequestsReportResource($data),
            'Profile update requests report retrieved.',
        );
    }

    // ─── GET /api/v1/reports/activity-summary ────────────────────────────────

    public function activitySummary(ActivitySummaryReportRequest $request): JsonResponse
    {
        if (! auth()->user()->can('report.activity.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $from    = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
        $to      = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;
        $actorId = $request->input('actor_id') ? (int) $request->input('actor_id') : null;

        $data = $this->activityFeedService->getActivitySummary(
            module: $request->input('module'),
            actorId: $actorId,
            from: $from,
            to: $to,
        );

        return $this->success(
            new ActivitySummaryReportResource($data),
            'Activity summary report retrieved.',
        );
    }

    // ─── GET /api/v1/reports/overview ─────────────────────────────────────────

    public function overview(ReportsOverviewRequest $request): JsonResponse
    {
        if (! auth()->user()->can('report.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $data = $this->reportService->reportOverview($request->validated());

        return $this->success(
            new ReportsOverviewResource($data),
            'Reports overview retrieved.',
        );
    }
}
