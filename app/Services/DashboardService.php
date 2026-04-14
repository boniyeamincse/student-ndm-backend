<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * DashboardService
 *
 * Builds role-based dashboard payloads.
 * Thin caching layer surrounds admin/superadmin aggregate cards (TTL 5 min).
 * Member-specific data is not cached since it is personalised.
 *
 * Future extension: pipe cache keys into Redis tag groups for
 * targeted invalidation when seeding or bulk-importing data.
 */
class DashboardService
{
    public function __construct(
        private readonly ActivityFeedService $activityFeedService,
        private readonly AnalyticsService $analyticsService,
    ) {
    }

    // ─── Main Entry ────────────────────────────────────────────────────────────

    /**
     * Build a complete role-appropriate dashboard payload.
     */
    public function buildForUser(User $user): array
    {
        $roles = $user->getRoleNames()->all();

        if (in_array('superadmin', $roles, true) || in_array('admin', $roles, true)) {
            $role = in_array('superadmin', $roles, true) ? 'superadmin' : 'admin';

            return [
                'role'            => $role,
                'cards'           => $this->adminCards(),
                'pending_items'   => $this->adminPendingItems(),
                'recent_activity' => $this->activityFeedService
                    ->getRecentActivities(15)
                    ->values()
                    ->all(),
                'latest_content'  => $this->latestContent(),
                'quick_links'     => $this->adminQuickLinks(),
            ];
        }

        // member (or any other role)
        return [
            'role'            => 'member',
            'cards'           => $this->memberCards($user),
            'pending_items'   => $this->memberPendingItems($user),
            'latest_content'  => $this->latestContent(memberOnly: true),
            'quick_links'     => $this->memberQuickLinks(),
        ];
    }

    // ─── Card Builders ─────────────────────────────────────────────────────────

    /**
     * Returns summary stat cards for admin/superadmin.
     * Cached for 5 minutes — acceptable staleness for a dashboard.
     */
    public function adminCards(): array
    {
        return Cache::remember('dashboard.admin.cards', 300, function () {
            return [
                'total_members'                   => DB::table('members')->count(),
                'active_members'                  => DB::table('members')->where('status', 'active')->count(),
                'inactive_members'                => DB::table('members')->where('status', 'inactive')->count(),
                'suspended_members'               => DB::table('members')->where('status', 'suspended')->count(),
                'total_applications'              => DB::table('membership_applications')->whereNull('deleted_at')->count(),
                'pending_applications'            => DB::table('membership_applications')
                    ->whereIn('status', ['pending', 'under_review'])
                    ->whereNull('deleted_at')
                    ->count(),
                'total_committees'                => DB::table('committees')->whereNull('deleted_at')->count(),
                'active_committees'               => DB::table('committees')->where('status', 'active')->whereNull('deleted_at')->count(),
                'total_assignments'               => DB::table('committee_member_assignments')->whereNull('deleted_at')->count(),
                'active_assignments'              => DB::table('committee_member_assignments')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->count(),
                'published_posts'                 => DB::table('posts')->where('status', 'published')->whereNull('deleted_at')->count(),
                'published_notices'               => DB::table('notices')->where('status', 'published')->whereNull('deleted_at')->count(),
                'profile_update_requests_pending' => DB::table('profile_update_requests')
                    ->where('status', 'pending')
                    ->whereNull('deleted_at')
                    ->count(),
            ];
        });
    }

    /**
     * Returns personalised stat cards for a member user.
     */
    public function memberCards(User $user): array
    {
        $memberId = DB::table('members')->where('user_id', $user->id)->value('id');

        $assignmentQuery = DB::table('committee_member_assignments')
            ->whereNull('deleted_at')
            ->where('is_active', true);

        if ($memberId) {
            $assignmentQuery->where('member_id', $memberId);
        } else {
            $assignmentQuery->whereRaw('1=0'); // no assignments if no member record
        }

        $assignments      = $assignmentQuery->get();
        $subordinateCount = 0;

        if ($memberId) {
            $assignmentIds = DB::table('committee_member_assignments')
                ->where('member_id', $memberId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->pluck('id');

            $subordinateCount = DB::table('member_reporting_relations')
                ->whereIn('superior_assignment_id', $assignmentIds)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->count();
        }

        return [
            'my_active_assignments'   => $assignments->count(),
            'my_leadership_assignments' => $assignments->where('is_leadership', true)->count(),
            'my_subordinates_count'   => $subordinateCount,
            'visible_notices_count'   => $this->visibleNoticesCount($user),
            'pending_profile_requests'=> DB::table('profile_update_requests')
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->whereNull('deleted_at')
                ->count(),
            'my_member_status'        => $memberId
                ? DB::table('members')->where('id', $memberId)->value('status')
                : 'not_member',
        ];
    }

    // ─── Pending Items ─────────────────────────────────────────────────────────

    public function adminPendingItems(): array
    {
        $items = [];

        $pendingApps = DB::table('membership_applications')
            ->whereIn('status', ['pending', 'under_review'])
            ->whereNull('deleted_at')
            ->count();

        if ($pendingApps > 0) {
            $items[] = [
                'type'       => 'pending_applications',
                'label'      => 'Pending Membership Applications',
                'count'      => $pendingApps,
                'action_url' => '/admin/membership-applications?status=pending',
                'priority'   => 'high',
            ];
        }

        $pendingProfileReqs = DB::table('profile_update_requests')
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->count();

        if ($pendingProfileReqs > 0) {
            $items[] = [
                'type'       => 'pending_profile_requests',
                'label'      => 'Pending Profile Update Requests',
                'count'      => $pendingProfileReqs,
                'action_url' => '/admin/profile-update-requests?status=pending',
                'priority'   => 'normal',
            ];
        }

        $expiringNotices = DB::table('notices')
            ->where('status', 'published')
            ->where('expires_at', '<=', Carbon::now()->addDays(3))
            ->where('expires_at', '>', Carbon::now())
            ->whereNull('deleted_at')
            ->count();

        if ($expiringNotices > 0) {
            $items[] = [
                'type'       => 'expiring_notices',
                'label'      => 'Notices Expiring Within 3 Days',
                'count'      => $expiringNotices,
                'action_url' => '/admin/notices?expiring_soon=1',
                'priority'   => 'normal',
            ];
        }

        $pendingPosts = DB::table('posts')
            ->where('status', 'pending_review')
            ->whereNull('deleted_at')
            ->count();

        if ($pendingPosts > 0) {
            $items[] = [
                'type'       => 'pending_review_posts',
                'label'      => 'Posts Awaiting Review',
                'count'      => $pendingPosts,
                'action_url' => '/admin/posts?status=pending_review',
                'priority'   => 'normal',
            ];
        }

        $pendingNotices = DB::table('notices')
            ->where('status', 'pending_review')
            ->whereNull('deleted_at')
            ->count();

        if ($pendingNotices > 0) {
            $items[] = [
                'type'       => 'pending_review_notices',
                'label'      => 'Notices Awaiting Review',
                'count'      => $pendingNotices,
                'action_url' => '/admin/notices?status=pending_review',
                'priority'   => 'normal',
            ];
        }

        return $items;
    }

    public function memberPendingItems(User $user): array
    {
        $items = [];

        $pendingReqs = DB::table('profile_update_requests')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->count();

        if ($pendingReqs > 0) {
            $items[] = [
                'type'       => 'pending_profile_requests',
                'label'      => 'Your Pending Profile Update Requests',
                'count'      => $pendingReqs,
                'action_url' => '/me/profile-requests?status=pending',
                'priority'   => 'normal',
            ];
        }

        return $items;
    }

    // ─── Latest Content ────────────────────────────────────────────────────────

    public function latestContent(bool $memberOnly = false): array
    {
        $postQuery = DB::table('posts')
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->orderByDesc('published_at')
            ->limit(5);

        if ($memberOnly) {
            $postQuery->whereIn('visibility', ['public', 'members_only']);
        }

        $noticeQuery = DB::table('notices')
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->orderByDesc('publish_at')
            ->limit(5);

        if ($memberOnly) {
            $noticeQuery->whereIn('visibility', ['public', 'members_only']);
        }

        return [
            'latest_posts'   => $postQuery->get(['id', 'title', 'slug', 'content_type', 'published_at'])->all(),
            'latest_notices' => $noticeQuery->get(['id', 'title', 'slug', 'priority', 'publish_at'])->all(),
        ];
    }

    // ─── Charts ────────────────────────────────────────────────────────────────

    /**
     * Build chart-ready datasets for a given period.
     *
     * @param string $period  '7d'|'30d'|'90d'|'12m'
     * @param bool   $includeMembershipTrend
     * @param bool   $includeMemberGrowth
     * @param bool   $includeContentSummary
     */
    public function buildCharts(
        string $period = '12m',
        bool $includeMembershipTrend = true,
        bool $includeMemberGrowth = true,
        bool $includeContentSummary = true,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        [$from, $to] = $this->analyticsService->resolveDateRange($period, $startDate, $endDate);
        $granularity  = $this->analyticsService->resolveBucketGranularity($period);

        $charts = [];

        if ($includeMembershipTrend) {
            $charts['membership_application_trend'] = [
                'title'       => 'Membership Applications',
                'granularity' => $granularity,
                'data'        => $this->analyticsService->buildTrend(
                    'membership_applications', 'created_at', $from, $to, $granularity
                ),
            ];
        }

        if ($includeMemberGrowth) {
            $charts['member_growth'] = [
                'title'       => 'Member Growth',
                'granularity' => $granularity,
                'data'        => $this->analyticsService->buildTrend(
                    'members', 'joined_at', $from, $to, $granularity
                ),
            ];
        }

        if ($includeContentSummary) {
            $charts['posts_by_status'] = [
                'title' => 'Posts by Status',
                'data'  => $this->analyticsService->groupByCount('posts', 'status')->all(),
            ];

            $charts['notices_by_priority'] = [
                'title' => 'Notices by Priority',
                'data'  => $this->analyticsService->groupByCount('notices', 'priority')->all(),
            ];
        }

        $charts['members_by_status'] = [
            'title' => 'Members by Status',
            'data'  => $this->analyticsService->groupByCount('members', 'status')->all(),
        ];

        $charts['committees_by_status'] = [
            'title' => 'Committees by Status',
            'data'  => $this->analyticsService->groupByCount('committees', 'status')->all(),
        ];

        return $charts;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function visibleNoticesCount(User $user): int
    {
        return DB::table('notices')
            ->where('status', 'published')
            ->whereIn('visibility', ['public', 'members_only'])
            ->whereNull('deleted_at')
            ->count();
    }

    private function adminQuickLinks(): array
    {
        return [
            ['label' => 'Membership Applications', 'url' => '/admin/membership-applications'],
            ['label' => 'Members',                  'url' => '/admin/members'],
            ['label' => 'Committees',               'url' => '/admin/committees'],
            ['label' => 'Posts',                    'url' => '/admin/posts'],
            ['label' => 'Notices',                  'url' => '/admin/notices'],
        ];
    }

    private function memberQuickLinks(): array
    {
        return [
            ['label' => 'My Profile',          'url' => '/me/profile'],
            ['label' => 'My Assignments',      'url' => '/me/committee-assignments'],
            ['label' => 'Profile Requests',    'url' => '/me/profile-requests'],
            ['label' => 'Notices',             'url' => '/member/notices'],
        ];
    }
}
