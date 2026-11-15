<?php

namespace App\Http\Controllers;

use App\Services\SupplierPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Lab 7 Part 6 Step 2: the supplier performance report.
 *
 * A second report controller rather than a fourth tab inside ReportController,
 * because ReportController is Lab 5/6 inventory reporting — it answers "what is
 * on the shelf" from the ledger — while this page answers "what is this vendor
 * like to buy from" from the purchase order book. Different gate
 * (view-supplier-reports: admin and manager only, a warehouse clerk does not
 * judge vendors), different data, different owner.
 *
 * The controller deliberately does no arithmetic: every figure comes from
 * SupplierPerformanceService, so the profile page, the dashboard widget and this
 * report are the same numbers read three ways. What is decided here is only the
 * window, the ranking cut and what is safe to rank on.
 */
class SupplierPerformanceController extends Controller
{
    /** The sheet's trend window: six monthly buckets, this one included. */
    private const TREND_MONTHS = 6;

    public function index(Request $request, SupplierPerformanceService $performance)
    {
        Gate::authorize('view-supplier-reports');

        [$from, $to] = $this->range($request);

        // One ranged, one un-ranged read: the aggregate plus the monthly trend.
        // Both are cache reads after the first call, and both carry the window in
        // their key — see SupplierPerformanceService::cacheKey().
        $scorecard = $performance->forAll($from, $to);
        $trend     = $performance->deliveryTrend($from, $to);

        // Best on the top: a supplier with nothing measured yet goes to the
        // bottom of the list rather than to the middle, where an empty cell
        // would look like a mediocre score.
        $rows = collect($scorecard['rows'])
            ->sortByDesc(fn (array $row): array => [
                (int) ($row['on_time_percentage'] !== null),
                (float) ($row['on_time_percentage'] ?? 0),
            ])
            ->values();

        return view('reports.supplier-performance', [
            'rows'    => $rows,
            'trend'   => $trend,
            'from'    => $from,
            'to'      => $to,
            'default' => $this->defaultRange(),
            'summary' => $this->summary($rows, $trend),
            'totals'  => [
                'suppliers' => $rows->count(),
                'orders'    => (int) $rows->sum('total_orders'),
                'spend'     => (float) $rows->sum('total_order_value'),
                'received'  => (int) $rows->sum('received_orders'),
            ],
        ]);
    }

    /* ==================================================================
       The ranking cut
       ================================================================== */

    /**
     * Top three, bottom three, and the honest label on both.
     *
     * Only suppliers with at least SupplierPerformanceService::MIN_SCORED_ORDERS
     * received orders may be ranked at all: a vendor with one delivery scores
     * either 100 % or 0 %, and neither is a pattern. With fewer than six such
     * vendors the bottom card would just repeat the top one in reverse, so it
     * stays empty and says why instead.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{top: array<int, array<string, mixed>>, bottom: array<int, array<string, mixed>>,
     *               ignored: array<int, array<string, mixed>>, rankable: int, months: int, min: int}
     */
    private function summary(Collection $rows, array $trend): array
    {
        $rankable = $rows->filter(fn (array $row): bool => $row['is_rankable'])->values();
        $sorted   = $rankable->sortByDesc('on_time_percentage')->values();

        return [
            'top'      => $sorted->take(3)->all(),
            'bottom'   => $sorted->count() > 3 ? $sorted->reverse()->take(3)->values()->all() : [],
            'ignored'  => $rows->reject(fn (array $row): bool => $row['is_rankable'])->values()->all(),
            'rankable' => $sorted->count(),
            'months'   => count($trend),
            'min'      => SupplierPerformanceService::MIN_SCORED_ORDERS,
        ];
    }

    /* ==================================================================
       Window
       ================================================================== */

    /**
     * The dates in the form, or the default six months. Anything that is not a
     * real calendar date is ignored rather than passed to the query, so a
     * hand-edited ?from=<script> cannot reach a cache key or a where clause.
     *
     * @return array{0: string, 1: string}
     */
    private function range(Request $request): array
    {
        [$defaultFrom, $defaultTo] = $this->defaultRange();

        return [
            $this->date($request->input('from')) ?? $defaultFrom,
            $this->date($request->input('to')) ?? $defaultTo,
        ];
    }

    /** @return array{0: string, 1: string} */
    private function defaultRange(): array
    {
        return [
            Carbon::now()->subMonths(self::TREND_MONTHS - 1)->startOfMonth()->toDateString(),
            Carbon::now()->toDateString(),
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            // Strict parse, then the round trip: 2026-02-30 is a well-formed
            // string that Carbon would happily roll over to 2 March.
            return Carbon::createFromFormat('!Y-m-d', $value)->toDateString() === $value ? $value : null;
        } catch (Throwable) {
            return null;
        }
    }
}
