<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ActivityFeedService
 *
 * Builds a normalised activity feed from the various history/status tables
 * across all modules. No central audit table exists yet; this service
 * composes feeds from module-specific history tables.
 *
 * Future extension points:
 * - Add a central `activity_logs` table and let each module write to it
 * - Connect to an event-sourcing stream
 * - Emit events for real-time dashboard feeds (Reverb/Pusher)
 */
class ActivityFeedService
{
    // ─── Public API ────────────────────────────────────────────────────────────

    /**
     * Return a unified, sorted, normalised activity feed.
     *
     * @param int         $limit   Max items (capped at 100)
     * @param string|null $module  Filter to one module source
     * @param int|null    $actorId Filter by actor user_id
     * @param Carbon|null $from
     * @param Carbon|null $to
     */
    public function getRecentActivities(
        int $limit = 20,
        ?string $module = null,
        ?int $actorId = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Collection {
        $limit = min($limit, 100);

        $sources = $this->buildSources($module, $actorId, $from, $to);

        // Merge and sort all rows latest-first, then take limit
        $merged = collect();
        foreach ($sources as $rows) {
            $merged = $merged->merge($rows);
        }

        return $merged->sortByDesc('created_at')->take($limit)->values();
    }

    /**
     * Return cross-module activity counts grouped by module and/or date.
     */
    public function getActivitySummary(
        ?string $module = null,
        ?int $actorId = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): array {
        $activities = $this->getRecentActivities(1000, $module, $actorId, $from, $to);

        $byModule = $activities->groupBy('module')->map->count()->sortDesc();

        $byDate = $activities
            ->groupBy(fn ($a) => substr($a['created_at'] ?? '', 0, 10))
            ->map->count()
            ->sortKeys();

        return [
            'total'      => $activities->count(),
            'by_module'  => $byModule->all(),
            'by_date'    => $byDate->all(),
            'items'      => $activities->take(50)->values()->all(),
        ];
    }

    // ─── Source Builders ───────────────────────────────────────────────────────

    private function buildSources(?string $module, ?int $actorId, ?Carbon $from, ?Carbon $to): array
    {
        $all = [
            'applications'    => fn () => $this->fromApplicationStatusHistories($actorId, $from, $to),
            'members'         => fn () => $this->fromMemberStatusHistories($actorId, $from, $to),
            'committees'      => fn () => $this->fromCommitteeStatusHistories($actorId, $from, $to),
            'assignments'     => fn () => $this->fromAssignmentHistories($actorId, $from, $to),
            'hierarchy'       => fn () => $this->fromHierarchyHistories($actorId, $from, $to),
            'posts'           => fn () => $this->fromPostStatusHistories($actorId, $from, $to),
            'notices'         => fn () => $this->fromNoticeStatusHistories($actorId, $from, $to),
            'profile_requests'=> fn () => $this->fromProfileRequestHistories($actorId, $from, $to),
        ];

        if ($module && isset($all[$module])) {
            return [$module => $all[$module]()];
        }

        return array_map(fn ($fn) => $fn(), $all);
    }

    // ─── Individual source queries ─────────────────────────────────────────────

    private function fromApplicationStatusHistories(?int $actorId, ?Carbon $from, ?Carbon $to): Collection
    {
        $query = DB::table('application_status_histories as ash')
            ->join('membership_applications as ma', 'ma.id', '=', 'ash.membership_application_id')
            ->leftJoin('users as u', 'u.id', '=', 'ash.changed_by')
            ->select([
                'ash.created_at',
                'ash.new_status as status',
                'ash.changed_by as actor_id',
                'u.name as actor_name',
                'ma.id as entity_id',
                'ma.full_name as entity_label',
                'ma.application_no',
            ])
            ->orderByDesc('ash.created_at')
            ->limit(50);

        if ($actorId) {
            $query->where('ash.changed_by', $actorId);
        }
        if ($from) {
            $query->where('ash.created_at', '>=', $from);
        }
        if ($to) {
            $query->where('ash.created_at', '<=', $to);
        }

        return $query->get()->map(fn ($r) => [
            'type'                => 'application_status_changed',
            'module'              => 'applications',
            'title'               => "Application {$r->application_no} → {$r->status}",
            'description'         => "Membership application by {$r->entity_label} status changed to {$r->status}.",
            'actor'               => $r->actor_name,
            'related_entity_type' => 'membership_application',
            'related_entity_id'   => $r->entity_id,
            'created_at'          => $r->created_at,
        ]);
    }

    private function fromMemberStatusHistories(?int $actorId, ?Carbon $from, ?Carbon $to): Collection
    {
        $query = DB::table('member_status_histories as msh')
            ->join('members as m', 'm.id', '=', 'msh.member_id')
            ->leftJoin('users as u', 'u.id', '=', 'msh.changed_by')
            ->select([
                'msh.created_at',
                'msh.new_status as status',
                'msh.changed_by as actor_id',
                'u.name as actor_name',
                'm.id as entity_id',
                'm.full_name as entity_label',
                'm.member_no',
            ])
            ->orderByDesc('msh.created_at')
            ->limit(50);

        if ($actorId) {
            $query->where('msh.changed_by', $actorId);
        }
        if ($from) {
            $query->where('msh.created_at', '>=', $from);
        }
        if ($to) {
            $query->where('msh.created_at', '<=', $to);
        }

        return $query->get()->map(fn ($r) => [
            'type'                => 'member_status_changed',
            'module'              => 'members',
            'title'               => "Member {$r->member_no} → {$r->status}",
            'description'         => "Member {$r->entity_label} status changed to {$r->status}.",
            'actor'               => $r->actor_name,
            'related_entity_type' => 'member',
            'related_entity_id'   => $r->entity_id,
            'created_at'          => $r->created_at,
        ]);
    }

    private function fromCommitteeStatusHistories(?int $actorId, ?Carbon $from, ?Carbon $to): Collection
    {
        $query = DB::table('committee_status_histories as csh')
            ->join('committees as c', 'c.id', '=', 'csh.committee_id')
            ->leftJoin('users as u', 'u.id', '=', 'csh.changed_by')
            ->select([
                'csh.created_at',
                'csh.new_status as status',
                'csh.changed_by as actor_id',
                'u.name as actor_name',
                'c.id as entity_id',
                'c.name as entity_label',
                'c.committee_no',
            ])
            ->orderByDesc('csh.created_at')
            ->limit(50);

        if ($actorId) {
            $query->where('csh.changed_by', $actorId);
        }
        if ($from) {
            $query->where('csh.created_at', '>=', $from);
        }
        if ($to) {
            $query->where('csh.created_at', '<=', $to);
        }

        return $query->get()->map(fn ($r) => [
            'type'                => 'committee_status_changed',
            'module'              => 'committees',
            'title'               => "Committee {$r->committee_no} → {$r->status}",
            'description'         => "Committee {$r->entity_label} status changed to {$r->status}.",
            'actor'               => $r->actor_name,
            'related_entity_type' => 'committee',
            'related_entity_id'   => $r->entity_id,
            'created_at'          => $r->created_at,
        ]);
    }

    private function fromAssignmentHistories(?int $actorId, ?Carbon $from, ?Carbon $to): Collection
    {
        $query = DB::table('committee_member_assignment_histories as amh')
            ->join('committee_member_assignments as cma', 'cma.id', '=', 'amh.committee_member_assignment_id')
            ->leftJoin('users as u', 'u.id', '=', 'amh.changed_by')
            ->leftJoin('members as m', 'm.id', '=', 'cma.member_id')
            ->select([
                'amh.created_at',
                'amh.action as status',
                'amh.changed_by as actor_id',
                'u.name as actor_name',
                'cma.id as entity_id',
                'cma.assignment_no',
                'm.full_name as member_name',
            ])
            ->orderByDesc('amh.created_at')
            ->limit(50);

        if ($actorId) {
            $query->where('amh.changed_by', $actorId);
        }
        if ($from) {
            $query->where('amh.created_at', '>=', $from);
        }
        if ($to) {
            $query->where('amh.created_at', '<=', $to);
        }

        return $query->get()->map(fn ($r) => [
            'type'                => 'assignment_status_changed',
            'module'              => 'assignments',
            'title'               => "Assignment {$r->assignment_no} → {$r->status}",
            'description'         => "Committee assignment for {$r->member_name} status changed to {$r->status}.",
            'actor'               => $r->actor_name,
            'related_entity_type' => 'committee_member_assignment',
            'related_entity_id'   => $r->entity_id,
            'created_at'          => $r->created_at,
        ]);
    }

    private function fromHierarchyHistories(?int $actorId, ?Carbon $from, ?Carbon $to): Collection
    {
        $query = DB::table('member_reporting_relation_histories as mrh')
            ->join('member_reporting_relations as r', 'r.id', '=', 'mrh.member_reporting_relation_id')
            ->leftJoin('users as u', 'u.id', '=', 'mrh.changed_by')
            ->select([
                'mrh.created_at',
                'mrh.action as status',
                'mrh.changed_by as actor_id',
                'u.name as actor_name',
                'r.id as entity_id',
                'r.relation_no',
            ])
            ->orderByDesc('mrh.created_at')
            ->limit(50);

        if ($actorId) {
            $query->where('mrh.changed_by', $actorId);
        }
        if ($from) {
            $query->where('mrh.created_at', '>=', $from);
        }
        if ($to) {
            $query->where('mrh.created_at', '<=', $to);
        }

        return $query->get()->map(fn ($r) => [
            'type'                => 'hierarchy_relation_changed',
            'module'              => 'hierarchy',
            'title'               => "Hierarchy relation {$r->relation_no} → {$r->status}",
            'description'         => "Reporting relation {$r->relation_no} status changed to {$r->status}.",
            'actor'               => $r->actor_name,
            'related_entity_type' => 'member_reporting_relation',
            'related_entity_id'   => $r->entity_id,
            'created_at'          => $r->created_at,
        ]);
    }

    private function fromPostStatusHistories(?int $actorId, ?Carbon $from, ?Carbon $to): Collection
    {
        $query = DB::table('post_status_histories as psh')
            ->join('posts as p', 'p.id', '=', 'psh.post_id')
            ->leftJoin('users as u', 'u.id', '=', 'psh.changed_by')
            ->select([
                'psh.created_at',
                'psh.new_status as status',
                'psh.changed_by as actor_id',
                'u.name as actor_name',
                'p.id as entity_id',
                'p.title',
                'p.post_no',
            ])
            ->orderByDesc('psh.created_at')
            ->limit(50);

        if ($actorId) {
            $query->where('psh.changed_by', $actorId);
        }
        if ($from) {
            $query->where('psh.created_at', '>=', $from);
        }
        if ($to) {
            $query->where('psh.created_at', '<=', $to);
        }

        return $query->get()->map(fn ($r) => [
            'type'                => 'post_status_changed',
            'module'              => 'posts',
            'title'               => "Post \"{$r->title}\" → {$r->status}",
            'description'         => "Post {$r->post_no} status changed to {$r->status}.",
            'actor'               => $r->actor_name,
            'related_entity_type' => 'post',
            'related_entity_id'   => $r->entity_id,
            'created_at'          => $r->created_at,
        ]);
    }

    private function fromNoticeStatusHistories(?int $actorId, ?Carbon $from, ?Carbon $to): Collection
    {
        $query = DB::table('notice_status_histories as nsh')
            ->join('notices as n', 'n.id', '=', 'nsh.notice_id')
            ->leftJoin('users as u', 'u.id', '=', 'nsh.changed_by')
            ->select([
                'nsh.created_at',
                'nsh.new_status as status',
                'nsh.changed_by as actor_id',
                'u.name as actor_name',
                'n.id as entity_id',
                'n.title',
                'n.notice_no',
            ])
            ->orderByDesc('nsh.created_at')
            ->limit(50);

        if ($actorId) {
            $query->where('nsh.changed_by', $actorId);
        }
        if ($from) {
            $query->where('nsh.created_at', '>=', $from);
        }
        if ($to) {
            $query->where('nsh.created_at', '<=', $to);
        }

        return $query->get()->map(fn ($r) => [
            'type'                => 'notice_status_changed',
            'module'              => 'notices',
            'title'               => "Notice \"{$r->title}\" → {$r->status}",
            'description'         => "Notice {$r->notice_no} status changed to {$r->status}.",
            'actor'               => $r->actor_name,
            'related_entity_type' => 'notice',
            'related_entity_id'   => $r->entity_id,
            'created_at'          => $r->created_at,
        ]);
    }

    private function fromProfileRequestHistories(?int $actorId, ?Carbon $from, ?Carbon $to): Collection
    {
        $query = DB::table('profile_update_request_histories as purh')
            ->join('profile_update_requests as pur', 'pur.id', '=', 'purh.profile_update_request_id')
            ->leftJoin('users as u', 'u.id', '=', 'purh.changed_by')
            ->select([
                'purh.created_at',
                'purh.new_status as status',
                'purh.changed_by as actor_id',
                'u.name as actor_name',
                'pur.id as entity_id',
                'pur.request_no',
                'pur.request_type',
            ])
            ->orderByDesc('purh.created_at')
            ->limit(50);

        if ($actorId) {
            $query->where('purh.changed_by', $actorId);
        }
        if ($from) {
            $query->where('purh.created_at', '>=', $from);
        }
        if ($to) {
            $query->where('purh.created_at', '<=', $to);
        }

        return $query->get()->map(fn ($r) => [
            'type'                => 'profile_request_status_changed',
            'module'              => 'profile_requests',
            'title'               => "Profile request {$r->request_no} → {$r->status}",
            'description'         => "Profile update request ({$r->request_type}) {$r->request_no} status changed to {$r->status}.",
            'actor'               => $r->actor_name,
            'related_entity_type' => 'profile_update_request',
            'related_entity_id'   => $r->entity_id,
            'created_at'          => $r->created_at,
        ]);
    }
}
