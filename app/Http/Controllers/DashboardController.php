<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardChartsRequest;
use App\Http\Requests\DashboardStatsRequest;
use App\Http\Requests\PendingItemsRequest;
use App\Http\Requests\RecentActivitiesRequest;
use App\Http\Resources\DashboardChartsResource;
use App\Http\Resources\DashboardResource;
use App\Http\Resources\DashboardStatsResource;
use App\Http\Resources\PendingItemResource;
use App\Http\Resources\RecentActivityResource;
use App\Services\ActivityFeedService;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly ActivityFeedService $activityFeedService,
    ) {
    }

    // ─── GET /api/v1/dashboard ─────────────────────────────────────────────────

    /**
     * Role-based full dashboard payload.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        if (
            ! $user->can('dashboard.superadmin.view') &&
            ! $user->can('dashboard.admin.view') &&
            ! $user->can('dashboard.member.view')
        ) {
            return $this->error('You are not authorized to view the dashboard.', 403);
        }

        $payload = $this->dashboardService->buildForUser($user);

        return $this->success(new DashboardResource($payload), 'Dashboard retrieved successfully.');
    }

    // ─── GET /api/v1/dashboard/superadmin ──────────────────────────────────────

    public function superadmin(): JsonResponse
    {
        if (! auth()->user()->can('dashboard.superadmin.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $user    = auth()->user();
        $payload = [
            'role'            => 'superadmin',
            'cards'           => $this->dashboardService->adminCards(),
            'pending_items'   => $this->dashboardService->adminPendingItems(),
            'recent_activity' => $this->activityFeedService->getRecentActivities(20)->all(),
            'latest_content'  => $this->dashboardService->latestContent(),
        ];

        return $this->success(new DashboardResource($payload), 'Superadmin dashboard retrieved.');
    }

    // ─── GET /api/v1/dashboard/admin ──────────────────────────────────────────

    public function admin(): JsonResponse
    {
        if (! auth()->user()->can('dashboard.admin.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $payload = [
            'role'            => 'admin',
            'cards'           => $this->dashboardService->adminCards(),
            'pending_items'   => $this->dashboardService->adminPendingItems(),
            'recent_activity' => $this->activityFeedService->getRecentActivities(15)->all(),
            'latest_content'  => $this->dashboardService->latestContent(),
        ];

        return $this->success(new DashboardResource($payload), 'Admin dashboard retrieved.');
    }

    // ─── GET /api/v1/dashboard/member ─────────────────────────────────────────

    public function member(): JsonResponse
    {
        if (! auth()->user()->can('dashboard.member.view')) {
            return $this->error('Unauthorized.', 403);
        }

        $user    = auth()->user();
        $payload = [
            'role'          => 'member',
            'cards'         => $this->dashboardService->memberCards($user),
            'pending_items' => $this->dashboardService->memberPendingItems($user),
            'latest_content'=> $this->dashboardService->latestContent(memberOnly: true),
        ];

        return $this->success(new DashboardResource($payload), 'Member dashboard retrieved.');
    }

    // ─── GET /api/v1/dashboard/stats ──────────────────────────────────────────

    public function stats(DashboardStatsRequest $request): JsonResponse
    {
        if (
            ! auth()->user()->can('dashboard.superadmin.view') &&
            ! auth()->user()->can('dashboard.admin.view') &&
            ! auth()->user()->can('dashboard.member.view')
        ) {
            return $this->error('Unauthorized.', 403);
        }

        $user = auth()->user();

        if ($user->can('dashboard.superadmin.view') || $user->can('dashboard.admin.view')) {
            $stats = $this->dashboardService->adminCards();
        } else {
            $stats = $this->dashboardService->memberCards($user);
        }

        return $this->success(new DashboardStatsResource($stats), 'Dashboard stats retrieved.');
    }

    // ─── GET /api/v1/dashboard/charts ─────────────────────────────────────────

    public function charts(DashboardChartsRequest $request): JsonResponse
    {
        if (
            ! auth()->user()->can('dashboard.superadmin.view') &&
            ! auth()->user()->can('dashboard.admin.view')
        ) {
            return $this->error('Unauthorized. Charts are available for admin roles only.', 403);
        }

        $charts = $this->dashboardService->buildCharts(
            period: $request->input('period', '12m'),
            includeMembershipTrend: (bool) $request->input('include_membership_trend', true),
            includeMemberGrowth: (bool) $request->input('include_member_growth', true),
            includeContentSummary: (bool) $request->input('include_content_summary', true),
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
        );

        return $this->success(new DashboardChartsResource($charts), 'Dashboard charts retrieved.');
    }

    // ─── GET /api/v1/dashboard/recent-activities ──────────────────────────────

    public function recentActivities(RecentActivitiesRequest $request): JsonResponse
    {
        if (
            ! auth()->user()->can('dashboard.superadmin.view') &&
            ! auth()->user()->can('dashboard.admin.view')
        ) {
            return $this->error('Unauthorized.', 403);
        }

        $limit  = (int) $request->input('limit', 20);
        $module = $request->input('module');

        $activities = $this->activityFeedService->getRecentActivities(
            limit: $limit,
            module: $module,
        );

        return $this->success(
            RecentActivityResource::collection($activities),
            'Recent activities retrieved.',
        );
    }

    // ─── GET /api/v1/dashboard/pending-items ──────────────────────────────────

    public function pendingItems(PendingItemsRequest $request): JsonResponse
    {
        if (
            ! auth()->user()->can('dashboard.superadmin.view') &&
            ! auth()->user()->can('dashboard.admin.view') &&
            ! auth()->user()->can('dashboard.member.view')
        ) {
            return $this->error('Unauthorized.', 403);
        }

        $user = auth()->user();

        if ($user->can('dashboard.superadmin.view') || $user->can('dashboard.admin.view')) {
            $items = $this->dashboardService->adminPendingItems();
        } else {
            $items = $this->dashboardService->memberPendingItems($user);
        }

        return $this->success(
            PendingItemResource::collection(collect($items)),
            'Pending items retrieved.',
        );
    }

    // ─── GET /api/v1/dashboard/latest-content ─────────────────────────────────

    public function latestContent(): JsonResponse
    {
        if (
            ! auth()->user()->can('dashboard.superadmin.view') &&
            ! auth()->user()->can('dashboard.admin.view') &&
            ! auth()->user()->can('dashboard.member.view')
        ) {
            return $this->error('Unauthorized.', 403);
        }

        $user        = auth()->user();
        $memberOnly  = ! $user->can('dashboard.admin.view') && ! $user->can('dashboard.superadmin.view');
        $content     = $this->dashboardService->latestContent(memberOnly: $memberOnly);

        return $this->success($content, 'Latest content retrieved.');
    }
}
