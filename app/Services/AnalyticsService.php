<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AnalyticsService
 *
 * Provides reusable helpers for:
 * - date range resolution from period string
 * - time-series bucketing (daily / weekly / monthly)
 * - grouped aggregation builders
 *
 * Future extension points:
 * - getCachedTrend() wrapper for caching expensive aggregates
 * - BI/data-warehouse export adapters
 */
class AnalyticsService
{
    // ─── Period Resolution ─────────────────────────────────────────────────────

    /**
     * Resolve a period string or explicit dates into [from, to] Carbon instances.
     *
     * @param string|null $period  '7d'|'30d'|'90d'|'12m'|'custom'|null
     * @param string|null $from
     * @param string|null $to
     * @return array{Carbon, Carbon}
     */
    public function resolveDateRange(?string $period, ?string $from = null, ?string $to = null): array
    {
        $now = Carbon::now();

        if ($period === 'custom' && $from) {
            $start = Carbon::parse($from)->startOfDay();
            $end   = $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay();

            return [$start, $end];
        }

        return match ($period) {
            '7d'  => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            '90d' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            '12m' => [$now->copy()->subMonths(11)->startOfMonth(), $now->copy()->endOfMonth()],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    /**
     * Determine the best bucket granularity for a period.
     *
     * @param string|null $period
     * @return string 'daily'|'weekly'|'monthly'
     */
    public function resolveBucketGranularity(?string $period): string
    {
        return match ($period) {
            '7d'  => 'daily',
            '30d' => 'weekly',
            '90d' => 'weekly',
            '12m' => 'monthly',
            default => 'weekly',
        };
    }

    // ─── Trend Builder ─────────────────────────────────────────────────────────

    /**
     * Build a zero-filled time-series from a DB aggregate query.
     *
     * @param string        $table       DB table name
     * @param string        $dateColumn  e.g. 'created_at' | 'published_at'
     * @param Carbon        $from
     * @param Carbon        $to
     * @param string        $granularity 'daily'|'weekly'|'monthly'
     * @param callable|null $queryCallback  Additional where clauses e.g. fn($q) => $q->where(...)
     * @param string        $countAlias
     * @return array  [{label, value}, ...]
     */
    public function buildTrend(
        string $table,
        string $dateColumn,
        Carbon $from,
        Carbon $to,
        string $granularity = 'monthly',
        ?callable $queryCallback = null,
        string $countAlias = 'total'
    ): array {
        [$selectExpr, $groupExpr, $labelFormat] = $this->bucketExpressions($granularity);

        $query = DB::table($table)
            ->selectRaw("{$selectExpr} as bucket_label, COUNT(*) as {$countAlias}")
            ->whereBetween($dateColumn, [$from, $to])
            ->whereNull('deleted_at')
            ->groupByRaw($groupExpr)
            ->orderByRaw($groupExpr);

        if ($queryCallback) {
            $queryCallback($query);
        }

        $rows = $query->get()->keyBy('bucket_label');

        return $this->fillBuckets($from, $to, $granularity, $labelFormat, $rows, $countAlias);
    }

    /**
     * Build a trend grouped by a status/type column.
     *
     * Returns: [{label, series: {key: value, ...}}, ...]
     */
    public function buildGroupedTrend(
        string $table,
        string $dateColumn,
        string $groupColumn,
        Carbon $from,
        Carbon $to,
        string $granularity = 'monthly',
        ?callable $queryCallback = null
    ): array {
        [$selectExpr, $groupExpr, $labelFormat] = $this->bucketExpressions($granularity);

        $query = DB::table($table)
            ->selectRaw("{$selectExpr} as bucket_label, {$groupColumn}, COUNT(*) as total")
            ->whereBetween($dateColumn, [$from, $to])
            ->whereNull('deleted_at')
            ->groupByRaw("{$groupExpr}, {$groupColumn}")
            ->orderByRaw($groupExpr);

        if ($queryCallback) {
            $queryCallback($query);
        }

        $rows = $query->get();

        // Build bucket labels skeleton
        $buckets = collect($this->buildBucketLabels($from, $to, $granularity, $labelFormat))
            ->mapWithKeys(fn ($label) => [$label => []]);

        foreach ($rows as $row) {
            $label = $row->bucket_label;
            if (isset($buckets[$label])) {
                $buckets[$label][$row->{$groupColumn}] = (int) $row->total;
            }
        }

        return $buckets->map(fn ($series, $label) => [
            'label'  => $label,
            'series' => $series,
        ])->values()->all();
    }

    // ─── Simple GroupBy Aggregation ────────────────────────────────────────────

    /**
     * Count rows grouped by a single column, optionally filtered.
     *
     * @return Collection  [{label, value}, ...]
     */
    public function groupByCount(
        string $table,
        string $column,
        ?callable $queryCallback = null
    ): Collection {
        $query = DB::table($table)
            ->selectRaw("{$column} as label, COUNT(*) as value")
            ->whereNull('deleted_at')
            ->groupBy($column)
            ->orderByDesc('value');

        if ($queryCallback) {
            $queryCallback($query);
        }

        return $query->get()->map(fn ($r) => [
            'label' => $r->label,
            'value' => (int) $r->value,
        ]);
    }

    // ─── Internals ─────────────────────────────────────────────────────────────

    /**
     * Return MySQL SELECT, GROUP BY, and PHP label format for a granularity.
     */
    private function bucketExpressions(string $granularity): array
    {
        return match ($granularity) {
            'daily'   => ["DATE_FORMAT(`created_at`, '%Y-%m-%d')", "DATE_FORMAT(`created_at`, '%Y-%m-%d')", 'Y-m-d'],
            'weekly'  => ["DATE_FORMAT(`created_at`, '%x-W%v')",  "DATE_FORMAT(`created_at`, '%x-W%v')",  'Y-\WW'],
            'monthly' => ["DATE_FORMAT(`created_at`, '%Y-%m')",    "DATE_FORMAT(`created_at`, '%Y-%m')",    'Y-m'],
            default   => ["DATE_FORMAT(`created_at`, '%Y-%m')",    "DATE_FORMAT(`created_at`, '%Y-%m')",    'Y-m'],
        };
    }

    /**
     * Generate an ordered list of bucket labels between two dates.
     */
    private function buildBucketLabels(Carbon $from, Carbon $to, string $granularity, string $phpFormat): array
    {
        $labels  = [];
        $current = $from->copy();

        while ($current->lte($to)) {
            $labels[] = $current->format($phpFormat);

            match ($granularity) {
                'daily'  => $current->addDay(),
                'weekly' => $current->addWeek(),
                default  => $current->addMonth(),
            };
        }

        return array_unique($labels);
    }

    /**
     * Zero-fill bucket labels with DB row data.
     */
    private function fillBuckets(
        Carbon $from,
        Carbon $to,
        string $granularity,
        string $phpFormat,
        \Illuminate\Support\Collection $rows,
        string $countAlias
    ): array {
        $labels = $this->buildBucketLabels($from, $to, $granularity, $phpFormat);

        return array_map(function ($label) use ($rows, $countAlias) {
            return [
                'label' => $label,
                'value' => isset($rows[$label]) ? (int) $rows[$label]->{$countAlias} : 0,
            ];
        }, $labels);
    }
}
