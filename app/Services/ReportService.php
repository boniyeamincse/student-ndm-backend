<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * ReportService
 *
 * Generates structured report payloads for each domain module.
 *
 * Architecture notes:
 * - All report methods return a consistent array with keys:
 *     filters, summary, groups, items, meta
 * - Filters are echoed back so consumers can render active-filter UI
 * - items are paginated DB results (stdClass rows, not Eloquent models — lighter)
 * - groups are aggregate breakdowns
 * - summary holds scalar KPI counts
 *
 * Future extension:
 * - Replace DB::table() queries with Eloquent and resource transformers for richer items
 * - Add Excel/CSV export by passing results to a dedicated ExportService
 * - Add scheduled report email by persisting filter sets + cron
 */
class ReportService
{
    // ─── Membership Applications ───────────────────────────────────────────────

    public function membershipApplicationsReport(array $filters): array
    {
        $query = DB::table('membership_applications')
            ->whereNull('deleted_at');

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($division = $filters['division_name'] ?? null) {
            $query->where('division_name', 'like', "%{$division}%");
        }
        if ($district = $filters['district_name'] ?? null) {
            $query->where('district_name', 'like', "%{$district}%");
        }
        if ($source = $filters['source'] ?? null) {
            $query->where('source', $source);
        }
        if ($reviewer = $filters['reviewer_id'] ?? null) {
            $query->where('reviewed_by', $reviewer);
        }
        if ($approver = $filters['approved_by'] ?? null) {
            $query->where('approved_by', $approver);
        }

        $this->applyDateRange($query, 'created_at', $filters);

        $total = (clone $query)->count();

        // Summary counts
        $summary = [
            'total'        => $total,
            'pending'      => (clone $query)->where('status', 'pending')->count(),
            'under_review' => (clone $query)->where('status', 'under_review')->count(),
            'approved'     => (clone $query)->where('status', 'approved')->count(),
            'rejected'     => (clone $query)->where('status', 'rejected')->count(),
            'on_hold'      => (clone $query)->where('status', 'on_hold')->count(),
        ];

        $summary['approval_ratio'] = $summary['total'] > 0
            ? round(($summary['approved'] / $summary['total']) * 100, 2)
            : 0;

        // Groups
        $groups = [
            'by_division' => $this->groupByCol($query, 'division_name'),
            'by_district' => $this->groupByCol($query, 'district_name'),
            'by_status'   => $this->groupByCol($query, 'status'),
        ];

        // Paginated items
        $sortBy  = in_array($filters['sort_by'] ?? '', ['created_at', 'status', 'division_name', 'district_name'])
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) ($filters['per_page'] ?? 20);

        $paginator = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage, ['id', 'application_no', 'full_name', 'status', 'division_name', 'district_name', 'created_at']);

        return [
            'filters' => $filters,
            'summary' => $summary,
            'groups'  => $groups,
            'items'   => $paginator->items(),
            'meta'    => $this->paginatorMeta($paginator),
        ];
    }

    // ─── Members ────────────────────────────────────────────────────────────────

    public function membersReport(array $filters): array
    {
        $query = DB::table('members')->whereNull('deleted_at');

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($gender = $filters['gender'] ?? null) {
            $query->where('gender', $gender);
        }
        if ($division = $filters['division_name'] ?? null) {
            $query->where('division_name', 'like', "%{$division}%");
        }
        if ($district = $filters['district_name'] ?? null) {
            $query->where('district_name', 'like', "%{$district}%");
        }
        if ($upazila = $filters['upazila_name'] ?? null) {
            $query->where('upazila_name', 'like', "%{$upazila}%");
        }
        if ($union = $filters['union_name'] ?? null) {
            $query->where('union_name', 'like', "%{$union}%");
        }
        if ($inst = $filters['educational_institution'] ?? null) {
            $query->where('educational_institution', 'like', "%{$inst}%");
        }

        if ($from = $filters['joined_at_from'] ?? null) {
            $query->where('joined_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to = $filters['joined_at_to'] ?? null) {
            $query->where('joined_at', '<=', Carbon::parse($to)->endOfDay());
        }

        $total = (clone $query)->count();

        $summary = [
            'total'     => $total,
            'active'    => (clone $query)->where('status', 'active')->count(),
            'inactive'  => (clone $query)->where('status', 'inactive')->count(),
            'suspended' => (clone $query)->where('status', 'suspended')->count(),
            'resigned'  => (clone $query)->where('status', 'resigned')->count(),
            'removed'   => (clone $query)->where('status', 'removed')->count(),
        ];

        $groups = [
            'by_division'    => $this->groupByCol($query, 'division_name'),
            'by_district'    => $this->groupByCol($query, 'district_name'),
            'by_gender'      => $this->groupByCol($query, 'gender'),
            'by_status'      => $this->groupByCol($query, 'status'),
            'by_institution' => $this->groupByCol($query, 'educational_institution'),
        ];

        $sortBy  = in_array($filters['sort_by'] ?? '', ['created_at', 'full_name', 'joined_at', 'status'])
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) ($filters['per_page'] ?? 20);

        $paginator = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage, ['id', 'member_no', 'full_name', 'gender', 'status', 'division_name', 'district_name', 'joined_at', 'created_at']);

        return [
            'filters' => $filters,
            'summary' => $summary,
            'groups'  => $groups,
            'items'   => $paginator->items(),
            'meta'    => $this->paginatorMeta($paginator),
        ];
    }

    // ─── Committees ────────────────────────────────────────────────────────────

    public function committeesReport(array $filters): array
    {
        $query = DB::table('committees as c')
            ->leftJoin('committee_types as ct', 'ct.id', '=', 'c.committee_type_id')
            ->whereNull('c.deleted_at');

        if ($typeId = $filters['committee_type_id'] ?? null) {
            $query->where('c.committee_type_id', $typeId);
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('c.status', $status);
        }
        if (isset($filters['is_current'])) {
            $query->where('c.is_current', filter_var($filters['is_current'], FILTER_VALIDATE_BOOLEAN));
        }
        if ($division = $filters['division_name'] ?? null) {
            $query->where('c.division_name', 'like', "%{$division}%");
        }
        if ($district = $filters['district_name'] ?? null) {
            $query->where('c.district_name', 'like', "%{$district}%");
        }
        if ($parent = $filters['parent_id'] ?? null) {
            $query->where('c.parent_id', $parent);
        }

        $this->applyDateRange($query, 'c.created_at', $filters);

        $total = (clone $query)->count();

        $summary = [
            'total'    => $total,
            'active'   => (clone $query)->where('c.status', 'active')->count(),
            'inactive' => (clone $query)->where('c.status', 'inactive')->count(),
            'dissolved'=> (clone $query)->where('c.status', 'dissolved')->count(),
            'archived' => (clone $query)->where('c.status', 'archived')->count(),
            'current'  => (clone $query)->where('c.is_current', true)->count(),
        ];

        $groups = [
            'by_type'     => $this->groupByCol($query, 'ct.name'),
            'by_status'   => $this->groupByCol($query, 'c.status'),
            'by_division' => $this->groupByCol($query, 'c.division_name'),
            'by_district' => $this->groupByCol($query, 'c.district_name'),
        ];

        $sortBy  = in_array($filters['sort_by'] ?? '', ['created_at', 'status', 'name'])
            ? 'c.' . $filters['sort_by']
            : 'c.created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) ($filters['per_page'] ?? 20);

        $paginator = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage, ['c.id', 'c.committee_no', 'c.name', 'ct.name as committee_type', 'c.status', 'c.is_current', 'c.division_name', 'c.district_name', 'c.created_at']);

        return [
            'filters' => $filters,
            'summary' => $summary,
            'groups'  => $groups,
            'items'   => $paginator->items(),
            'meta'    => $this->paginatorMeta($paginator),
        ];
    }

    // ─── Committee Assignments ─────────────────────────────────────────────────

    public function committeeAssignmentsReport(array $filters): array
    {
        $query = DB::table('committee_member_assignments as cma')
            ->leftJoin('members as m', 'm.id', '=', 'cma.member_id')
            ->leftJoin('committees as c', 'c.id', '=', 'cma.committee_id')
            ->leftJoin('committee_types as ct', 'ct.id', '=', 'c.committee_type_id')
            ->leftJoin('positions as p', 'p.id', '=', 'cma.position_id')
            ->whereNull('cma.deleted_at');

        if ($cid = $filters['committee_id'] ?? null) {
            $query->where('cma.committee_id', $cid);
        }
        if ($ctid = $filters['committee_type_id'] ?? null) {
            $query->where('ct.id', $ctid);
        }
        if ($mid = $filters['member_id'] ?? null) {
            $query->where('cma.member_id', $mid);
        }
        if ($pid = $filters['position_id'] ?? null) {
            $query->where('cma.position_id', $pid);
        }
        if ($type = $filters['assignment_type'] ?? null) {
            $query->where('cma.assignment_type', $type);
        }
        if (isset($filters['is_primary'])) {
            $query->where('cma.is_primary', filter_var($filters['is_primary'], FILTER_VALIDATE_BOOLEAN));
        }
        if (isset($filters['is_leadership'])) {
            $query->where('cma.is_leadership', filter_var($filters['is_leadership'], FILTER_VALIDATE_BOOLEAN));
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('cma.status', $status);
        }
        if (($filters['active_only'] ?? false)) {
            $query->where('cma.is_active', true);
        }

        $this->applyDateRange($query, 'cma.created_at', $filters);

        $total = (clone $query)->count();

        $summary = [
            'total'           => $total,
            'active'          => (clone $query)->where('cma.is_active', true)->count(),
            'inactive'        => (clone $query)->where('cma.is_active', false)->count(),
            'leadership'      => (clone $query)->where('cma.is_leadership', true)->count(),
            'primary'         => (clone $query)->where('cma.is_primary', true)->count(),
        ];

        $groups = [
            'by_assignment_type'   => $this->groupByCol($query, 'cma.assignment_type'),
            'by_committee_type'    => $this->groupByCol($query, 'ct.name'),
            'by_status'            => $this->groupByCol($query, 'cma.status'),
        ];

        $sortBy  = in_array($filters['sort_by'] ?? '', ['created_at', 'status'])
            ? 'cma.' . $filters['sort_by']
            : 'cma.created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) ($filters['per_page'] ?? 20);

        $paginator = $query->orderBy($sortBy, $sortDir)
            ->paginate($perPage, [
                'cma.id', 'cma.assignment_no', 'm.full_name as member_name',
                'c.name as committee_name', 'p.title as position_title',
                'cma.assignment_type', 'cma.is_leadership', 'cma.is_active',
                'cma.status', 'cma.created_at',
            ]);

        return [
            'filters' => $filters,
            'summary' => $summary,
            'groups'  => $groups,
            'items'   => $paginator->items(),
            'meta'    => $this->paginatorMeta($paginator),
        ];
    }

    // ─── Reporting Hierarchy ───────────────────────────────────────────────────

    public function reportingHierarchyReport(array $filters): array
    {
        $query = DB::table('member_reporting_relations as mrr')
            ->leftJoin('committees as c', 'c.id', '=', 'mrr.committee_id')
            ->leftJoin('committee_types as ct', 'ct.id', '=', 'c.committee_type_id')
            ->whereNull('mrr.deleted_at');

        if ($cid = $filters['committee_id'] ?? null) {
            $query->where('mrr.committee_id', $cid);
        }
        if ($ctid = $filters['committee_type_id'] ?? null) {
            $query->where('ct.id', $ctid);
        }
        if ($rel = $filters['relation_type'] ?? null) {
            $query->where('mrr.relation_type', $rel);
        }
        if (isset($filters['is_primary'])) {
            $query->where('mrr.is_primary', filter_var($filters['is_primary'], FILTER_VALIDATE_BOOLEAN));
        }
        if (isset($filters['is_active'])) {
            $query->where('mrr.is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $total = (clone $query)->count();

        $summary = [
            'total'           => $total,
            'active'          => (clone $query)->where('mrr.is_active', true)->count(),
            'primary'         => (clone $query)->where('mrr.is_primary', true)->count(),
        ];

        $groups = [
            'by_relation_type'   => $this->groupByCol($query, 'mrr.relation_type'),
            'by_committee_type'  => $this->groupByCol($query, 'ct.name'),
        ];

        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) ($filters['per_page'] ?? 20);

        $paginator = $query->orderBy('mrr.created_at', $sortDir)
            ->paginate($perPage, [
                'mrr.id', 'mrr.relation_no', 'mrr.relation_type',
                'mrr.is_primary', 'mrr.is_active', 'c.name as committee_name',
                'mrr.created_at',
            ]);

        return [
            'filters' => $filters,
            'summary' => $summary,
            'groups'  => $groups,
            'items'   => $paginator->items(),
            'meta'    => $this->paginatorMeta($paginator),
        ];
    }

    // ─── Posts ─────────────────────────────────────────────────────────────────

    public function postsReport(array $filters): array
    {
        $query = DB::table('posts as p')
            ->leftJoin('post_categories as pc', 'pc.id', '=', 'p.post_category_id')
            ->whereNull('p.deleted_at');

        if ($type = $filters['content_type'] ?? null) {
            $query->where('p.content_type', $type);
        }
        if ($catId = $filters['post_category_id'] ?? null) {
            $query->where('p.post_category_id', $catId);
        }
        if ($authorId = $filters['author_id'] ?? null) {
            $query->where('p.author_id', $authorId);
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('p.status', $status);
        }
        if ($vis = $filters['visibility'] ?? null) {
            $query->where('p.visibility', $vis);
        }
        if (isset($filters['is_featured'])) {
            $query->where('p.is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN));
        }

        $this->applyDateRange($query, 'p.created_at', $filters);

        $total = (clone $query)->count();

        $summary = [
            'total'          => $total,
            'draft'          => (clone $query)->where('p.status', 'draft')->count(),
            'pending_review' => (clone $query)->where('p.status', 'pending_review')->count(),
            'published'      => (clone $query)->where('p.status', 'published')->count(),
            'unpublished'    => (clone $query)->where('p.status', 'unpublished')->count(),
            'archived'       => (clone $query)->where('p.status', 'archived')->count(),
            'featured'       => (clone $query)->where('p.is_featured', true)->count(),
            'public'         => (clone $query)->where('p.visibility', 'public')->count(),
            'members_only'   => (clone $query)->where('p.visibility', 'members_only')->count(),
        ];

        $groups = [
            'by_content_type' => $this->groupByCol($query, 'p.content_type'),
            'by_status'       => $this->groupByCol($query, 'p.status'),
            'by_category'     => $this->groupByCol($query, 'pc.name'),
            'by_visibility'   => $this->groupByCol($query, 'p.visibility'),
        ];

        $sortBy  = in_array($filters['sort_by'] ?? '', ['created_at', 'published_at', 'title', 'status'])
            ? 'p.' . $filters['sort_by']
            : 'p.created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) ($filters['per_page'] ?? 20);

        $paginator = $query->orderBy($sortBy, $sortDir)
            ->paginate($perPage, [
                'p.id', 'p.post_no', 'p.title', 'p.content_type', 'p.status',
                'p.visibility', 'p.is_featured', 'p.published_at', 'p.view_count', 'p.created_at',
            ]);

        return [
            'filters' => $filters,
            'summary' => $summary,
            'groups'  => $groups,
            'items'   => $paginator->items(),
            'meta'    => $this->paginatorMeta($paginator),
        ];
    }

    // ─── Notices ───────────────────────────────────────────────────────────────

    public function noticesReport(array $filters): array
    {
        $query = DB::table('notices')->whereNull('deleted_at');

        if ($type = $filters['notice_type'] ?? null) {
            $query->where('notice_type', $type);
        }
        if ($priority = $filters['priority'] ?? null) {
            $query->where('priority', $priority);
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($vis = $filters['visibility'] ?? null) {
            $query->where('visibility', $vis);
        }
        if ($audience = $filters['audience_type'] ?? null) {
            $query->where('audience_type', $audience);
        }
        if ($cid = $filters['committee_id'] ?? null) {
            $query->where('committee_id', $cid);
        }
        if (isset($filters['is_pinned'])) {
            $query->where('is_pinned', filter_var($filters['is_pinned'], FILTER_VALIDATE_BOOLEAN));
        }

        $this->applyDateRange($query, 'created_at', $filters);

        $total = (clone $query)->count();

        $summary = [
            'total'          => $total,
            'draft'          => (clone $query)->where('status', 'draft')->count(),
            'pending_review' => (clone $query)->where('status', 'pending_review')->count(),
            'published'      => (clone $query)->where('status', 'published')->count(),
            'unpublished'    => (clone $query)->where('status', 'unpublished')->count(),
            'archived'       => (clone $query)->where('status', 'archived')->count(),
            'expired'        => (clone $query)->where('status', 'expired')->count(),
            'pinned'         => (clone $query)->where('is_pinned', true)->count(),
            'expiring_soon'  => (clone $query)
                ->where('status', 'published')
                ->where('expires_at', '<=', Carbon::now()->addDays(7))
                ->where('expires_at', '>', Carbon::now())
                ->count(),
        ];

        $groups = [
            'by_notice_type'  => $this->groupByCol($query, 'notice_type'),
            'by_priority'     => $this->groupByCol($query, 'priority'),
            'by_status'       => $this->groupByCol($query, 'status'),
            'by_visibility'   => $this->groupByCol($query, 'visibility'),
            'by_audience'     => $this->groupByCol($query, 'audience_type'),
        ];

        $sortBy  = in_array($filters['sort_by'] ?? '', ['created_at', 'published_at', 'title', 'priority', 'status'])
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) ($filters['per_page'] ?? 20);

        $paginator = $query->orderBy($sortBy, $sortDir)
            ->paginate($perPage, [
                'id', 'notice_no', 'title', 'notice_type', 'priority', 'status',
                'visibility', 'is_pinned', 'publish_at', 'expires_at', 'created_at',
            ]);

        return [
            'filters' => $filters,
            'summary' => $summary,
            'groups'  => $groups,
            'items'   => $paginator->items(),
            'meta'    => $this->paginatorMeta($paginator),
        ];
    }

    // ─── Profile Update Requests ───────────────────────────────────────────────

    public function profileUpdateRequestsReport(array $filters): array
    {
        $query = DB::table('profile_update_requests as pur')
            ->leftJoin('users as u', 'u.id', '=', 'pur.user_id')
            ->leftJoin('members as m', 'm.id', '=', 'pur.member_id')
            ->whereNull('pur.deleted_at');

        if ($type = $filters['request_type'] ?? null) {
            $query->where('pur.request_type', $type);
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('pur.status', $status);
        }
        if ($uid = $filters['user_id'] ?? null) {
            $query->where('pur.user_id', $uid);
        }
        if ($mid = $filters['member_id'] ?? null) {
            $query->where('pur.member_id', $mid);
        }
        if ($reviewer = $filters['reviewed_by'] ?? null) {
            $query->where('pur.reviewed_by', $reviewer);
        }

        $this->applyDateRange($query, 'pur.created_at', $filters);

        $total = (clone $query)->count();

        $summary = [
            'total'     => $total,
            'pending'   => (clone $query)->where('pur.status', 'pending')->count(),
            'approved'  => (clone $query)->where('pur.status', 'approved')->count(),
            'rejected'  => (clone $query)->where('pur.status', 'rejected')->count(),
            'cancelled' => (clone $query)->where('pur.status', 'cancelled')->count(),
        ];

        $groups = [
            'by_request_type' => $this->groupByCol($query, 'pur.request_type'),
            'by_status'       => $this->groupByCol($query, 'pur.status'),
        ];

        $sortBy  = in_array($filters['sort_by'] ?? '', ['created_at', 'status', 'request_type'])
            ? 'pur.' . $filters['sort_by']
            : 'pur.created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) ($filters['per_page'] ?? 20);

        $paginator = $query->orderBy($sortBy, $sortDir)
            ->paginate($perPage, [
                'pur.id', 'pur.request_no', 'pur.request_type', 'pur.status',
                'u.name as user_name', 'm.full_name as member_name',
                'pur.reviewed_at', 'pur.created_at',
            ]);

        return [
            'filters' => $filters,
            'summary' => $summary,
            'groups'  => $groups,
            'items'   => $paginator->items(),
            'meta'    => $this->paginatorMeta($paginator),
        ];
    }

    // ─── Reports Overview ──────────────────────────────────────────────────────

    public function reportOverview(array $filters): array
    {
        return [
            'total_members'             => DB::table('members')->whereNull('deleted_at')->count(),
            'active_members'            => DB::table('members')->where('status', 'active')->whereNull('deleted_at')->count(),
            'total_committees'          => DB::table('committees')->whereNull('deleted_at')->count(),
            'active_committees'         => DB::table('committees')->where('status', 'active')->whereNull('deleted_at')->count(),
            'total_assignments'         => DB::table('committee_member_assignments')->whereNull('deleted_at')->count(),
            'total_published_posts'     => DB::table('posts')->where('status', 'published')->whereNull('deleted_at')->count(),
            'total_published_notices'   => DB::table('notices')->where('status', 'published')->whereNull('deleted_at')->count(),
            'pending_applications'      => DB::table('membership_applications')->whereIn('status', ['pending', 'under_review'])->whereNull('deleted_at')->count(),
            'pending_profile_requests'  => DB::table('profile_update_requests')->where('status', 'pending')->whereNull('deleted_at')->count(),
            'reporting_relations_active'=> DB::table('member_reporting_relations')->where('is_active', true)->whereNull('deleted_at')->count(),
        ];
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function applyDateRange(\Illuminate\Database\Query\Builder $query, string $col, array $filters): void
    {
        if ($from = $filters['start_date'] ?? null) {
            $query->where($col, '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to = $filters['end_date'] ?? null) {
            $query->where($col, '<=', Carbon::parse($to)->endOfDay());
        }
    }

    private function groupByCol(\Illuminate\Database\Query\Builder $query, string $col): array
    {
        return (clone $query)
            ->selectRaw("{$col} as label, COUNT(*) as value")
            ->groupBy($col)
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'value' => (int) $r->value])
            ->all();
    }

    private function paginatorMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ];
    }
}
