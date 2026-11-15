<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Lab 7 Part 6 Step 1: the supplier scorecard.
 *
 * One query answers the whole question. The naive version asks each supplier in
 * turn — orders placed, orders on time, average days, lines received, lines
 * exact, money spent — which is six queries per vendor and a report that gets
 * slower for every supplier the business signs. Here the answer for every
 * vendor at once is a single GROUP BY: the CASE WHENs do the counting inside
 * the engine and the application only ever receives one row per supplier.
 *
 * THE FIVE FORMULAS (each one is also commented where it is computed):
 *
 *   total_orders          = COUNT of orders actually placed: sent, partially
 *                           received or received. A draft, a pending or an
 *                           approved order is an intention; a rejected or a
 *                           cancelled one never left the building. Only what
 *                           the vendor was really asked for can be judged.
 *   on_time_percentage    = orders whose DATE(received_at) is on or before
 *                           expected_delivery_date, over the orders that carry
 *                           both stamps. Kept to the calendar DAY, not the
 *                           clock — a 4 pm drop-off against a Friday deadline
 *                           is on time, the same rule as
 *                           PurchaseOrder::deliveryVerdict().
 *   average_delivery_days = AVG of DATEDIFF(received_at, sent_at): the calendar
 *                           days between the vendor receiving the order and the
 *                           goods landing. Compared with the lead time the
 *                           vendor QUOTED (suppliers.lead_time_days); that
 *                           difference is the honesty of the quote — positive
 *                           means slower than promised.
 *   order_accuracy        = lines whose received_quantity equals quantity, over
 *                           every line of the received orders. A truck that is
 *                           on time with four rolls missing from a ten-roll
 *                           line was not a good delivery.
 *   total_order_value     = SUM(total) of the placed orders — the header column
 *                           the Part 2 invoice maths already wrote.
 *
 * WHY A RECEIPT IS `status = received AND received_at IS NOT NULL`: only a
 * COMPLETE receipt stamps it (PurchaseOrderController::receive() sets it on the
 * last outstanding unit and back to null for a partial), so a partially
 * received order is still in flight. Counting it as late would punish a vendor
 * for a delivery that has not happened yet, and counting its lines would punish
 * them for units nobody has signed for. It still counts in total_orders and in
 * the money, because that order was genuinely placed.
 *
 * CACHE: the all-time answer lives under the key PurchaseOrderObserver already
 * deletes, so a status change makes it stale immediately. A date-filtered answer
 * cannot be forgotten by name — the range is part of its key — so ranged keys
 * also carry an epoch stamp that the same flush removes, and every ranged
 * variant goes stale together without scanning the cache table (which a
 * database store cannot do by tag anyway).
 */
class SupplierPerformanceService
{
    /**
     * One delivery scores 0 % or 100 %, and neither says anything about a
     * vendor. The Part 6 top/bottom cards ignore suppliers under this many
     * received orders, and the report says so out loud.
     */
    public const MIN_SCORED_ORDERS = 2;

    /* ==================================================================
       Readers
       ================================================================== */

    /**
     * Every supplier with at least one placed order, as one cached aggregate.
     *
     * @return array{rows: array<int, array<string, mixed>>, range: array<int, ?string>, generated_at: string}
     */
    public function forAll(?string $from = null, ?string $to = null): array
    {
        return Cache::remember(
            $this->cacheKey($from, $to),
            (int) config('procurement.cache.supplier_performance_ttl'),
            fn (): array => [
                'rows'         => $this->aggregate($from, $to),
                'range'        => [$from, $to],
                'generated_at' => now()->toIso8601String(),
            ]
        );
    }

    /**
     * One supplier, read out of the same cached aggregate.
     *
     * Asking for the whole set costs the same single query, so the supplier
     * list, the profile page and the Part 5 widget together are one query per
     * TTL. The accessor this replaces ran one query per supplier per page.
     *
     * @return array<string, mixed>|null  null while nothing has been ordered
     */
    public function forSupplier(Supplier|int $supplier, ?string $from = null, ?string $to = null): ?array
    {
        $id = $supplier instanceof Supplier ? $supplier->id : $supplier;

        foreach ($this->forAll($from, $to)['rows'] as $row) {
            if ($row['supplier_id'] === $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * On-time versus late deliveries, one bucket per month of the window.
     *
     * Bucketed by order_date, the same column the from/to filter slices — a
     * chart grouped by the month the goods landed would silently count a
     * different set of orders than the table printed above it.
     *
     * @return array<int, array{month: string, label: string, on_time: int, late: int}>
     */
    public function deliveryTrend(?string $from = null, ?string $to = null): array
    {
        return Cache::remember(
            $this->cacheKey($from, $to, 'trend'),
            (int) config('procurement.cache.supplier_performance_ttl'),
            function () use ($from, $to): array {
                // sqlite has no DATE_FORMAT; both expressions yield 'YYYY-MM'.
                $month = DB::connection()->getDriverName() === 'sqlite'
                    ? "strftime('%Y-%m', purchase_orders.order_date)"
                    : "DATE_FORMAT(purchase_orders.order_date, '%Y-%m')";

                $onTime = $this->onTimeSql();

                $buckets = PurchaseOrder::query()
                    // A delivery verdict needs the promise and the receipt.
                    ->whereNotNull('received_at')
                    ->whereNotNull('expected_delivery_date')
                    ->betweenDates($from, $to)
                    ->selectRaw($month.' AS bucket, '
                        .'SUM(CASE WHEN '.$onTime.' THEN 1 ELSE 0 END) AS on_time, '
                        .'SUM(CASE WHEN '.$onTime.' THEN 0 ELSE 1 END) AS late')
                    ->groupByRaw($month)
                    ->get()
                    ->mapWithKeys(fn ($row): array => [
                        (string) $row->bucket => ['on_time' => (int) $row->on_time, 'late' => (int) $row->late],
                    ])
                    ->all();

                return $this->zeroFill($buckets, $from, $to);
            }
        );
    }

    /* ==================================================================
       The single grouped aggregate
       ================================================================== */

    /**
     * @return array<int, array<string, mixed>>
     */
    private function aggregate(?string $from, ?string $to): array
    {
        $received = $this->receiptCondition();
        $onTime   = $this->onTimeSql();
        $days     = $this->daysSql();

        /*
         * The line counters are grouped by order FIRST and joined 1:1, so a
         * three-line order cannot multiply its header row and inflate
         * SUM(total). Joining the raw items table and aggregating in the outer
         * query is the classic way to get a spend figure three times too big.
         */
        $lines = DB::table('purchase_order_items')
            ->selectRaw('purchase_order_id, '
                .'COUNT(*) AS line_count, '
                .'SUM(CASE WHEN received_quantity = quantity THEN 1 ELSE 0 END) AS exact_count')
            ->groupBy('purchase_order_id');

        $rows = PurchaseOrder::query()
            // Inner join on purpose: an order whose supplier row is gone cannot
            // be reported on. A TRASHED supplier still joins — retired vendor,
            // real history — and arrives flagged so the view can say so instead
            // of scoring a ghost.
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->joinSub($lines, 'lines', function ($join): void {
                $join->on('lines.purchase_order_id', '=', 'purchase_orders.id');
            })
            ->whereIn('purchase_orders.status', PurchaseOrder::PLACED_STATUSES)
            ->betweenDates($from, $to)
            ->selectRaw('purchase_orders.supplier_id AS supplier_id, '
                .'suppliers.name AS name, '
                .'suppliers.lead_time_days AS lead_time_days, '
                .'suppliers.is_active AS is_active, '

                // total_orders — every order the vendor was actually given.
                .'COUNT(*) AS total_orders, '

                // total_order_value — header totals; the lines join is 1:1.
                .'COALESCE(SUM(purchase_orders.total), 0) AS total_order_value, '

                // the denominator of every delivery ratio
                .'SUM(CASE WHEN '.$received.' THEN 1 ELSE 0 END) AS received_orders, '

                // on_time_orders — to the calendar day, not the minute
                .'SUM(CASE WHEN '.$received.' AND '.$onTime.' THEN 1 ELSE 0 END) AS on_time_orders, '

                // average_delivery_days — a CASE with no ELSE yields NULL and
                // AVG skips NULLs, so an order that has not landed cannot drag
                // the average down (and an order without sent_at is skipped).
                .'AVG(CASE WHEN '.$received.' AND purchase_orders.sent_at IS NOT NULL '
                    .'THEN '.$days.' ELSE NULL END) AS average_delivery_days, '

                // order_accuracy — lines that arrived exactly as ordered
                .'SUM(CASE WHEN '.$received.' THEN COALESCE(lines.line_count, 0)  ELSE 0 END) AS lines_received, '
                .'SUM(CASE WHEN '.$received.' THEN COALESCE(lines.exact_count, 0) ELSE 0 END) AS lines_exact')
            // name/lead time/active are functionally dependent on the group key,
            // and listing them keeps ONLY_FULL_GROUP_BY happy on MySQL 8.
            ->groupBy('purchase_orders.supplier_id', 'suppliers.name', 'suppliers.lead_time_days', 'suppliers.is_active')
            ->get();

        return $rows->map(fn (object $row): array => $this->shape($row))->all();
    }

    /**
     * Raw counts in, percentages out: the one place a ratio is derived, so the
     * widget, the profile page and the report cannot round differently.
     *
     * @return array<string, mixed>
     */
    private function shape(object $row): array
    {
        $received = (int) $row->received_orders;
        $onTime   = (int) $row->on_time_orders;
        $lines    = (int) $row->lines_received;
        $exact    = (int) $row->lines_exact;
        $quoted   = $row->lead_time_days === null ? null : (int) $row->lead_time_days;

        // on_time_percentage: percent = on time / received * 100, or null —
        // never a fake 100 % for a vendor who has not delivered yet.
        $percent = $received === 0 ? null : round($onTime / $received * 100, 1);

        // order_accuracy: percent = exact lines / all lines on those orders * 100
        $accuracy = $lines === 0 ? null : round($exact / $lines * 100, 1);

        // average_delivery_days straight out of SQL, one decimal is a day and a
        // fifth of a day — rounding further would hide a systematic half-day.
        $average = $row->average_delivery_days === null ? null : round((float) $row->average_delivery_days, 1);

        // lead_time_variance: measured average minus what the vendor quoted.
        $variance = $average === null || $quoted === null ? null : round($average - $quoted, 1);

        return [
            'supplier_id'        => (int) $row->supplier_id,
            'name'               => (string) $row->name,
            'is_active'          => (bool) $row->is_active,
            'lead_time_days'     => $quoted,

            'total_orders'       => (int) $row->total_orders,
            'total_order_value'  => (float) $row->total_order_value,

            'received_orders'    => $received,
            'on_time_orders'     => $onTime,
            'on_time_percentage' => $percent,

            'average_delivery_days'   => $average,
            'lead_time_variance_days' => $variance,

            'lines_received'           => $lines,
            'lines_exact'              => $exact,
            'order_accuracy_percentage' => $accuracy,

            // Ranking needs a sample; a single delivery is not one.
            'is_rankable' => $received >= self::MIN_SCORED_ORDERS,

            'rating_badge_class' => self::badgeClassFor($percent),
        ];
    }

    /* ==================================================================
       Presentation helpers, shared so the numbers never disagree on screen
       ================================================================== */

    /** The colour bands, written once. Same thresholds the Part 1 accessor used. */
    public static function badgeClassFor(?float $percentage): string
    {
        return match (true) {
            $percentage === null => 'bg-secondary',
            $percentage >= 90    => 'bg-success',
            $percentage >= 75    => 'bg-warning text-dark',
            default              => 'bg-danger',
        };
    }

    /** "+3.5 days late" / "1 day early" / "exactly on quote". */
    public static function varianceLabel(?float $variance): string
    {
        return match (true) {
            $variance === null => '—',
            $variance > 0      => '+'.rtrim(rtrim(number_format($variance, 1), '0'), '.').' days late',
            $variance < 0      => rtrim(rtrim(number_format(abs($variance), 1), '0'), '.').' days early',
            default            => 'exactly on quote',
        };
    }

    /* ==================================================================
       SQL fragments — the lab runs on MySQL, the test suite on sqlite
       ================================================================== */

    /** A closed receipt: received, and measurable against a promise. */
    private function receiptCondition(): string
    {
        // The status is a value from PurchaseOrder's own constants, never
        // request input, so it is written into the SQL rather than bound.
        return "purchase_orders.status = '".PurchaseOrder::STATUS_RECEIVED."' "
            .'AND purchase_orders.received_at IS NOT NULL '
            .'AND purchase_orders.expected_delivery_date IS NOT NULL';
    }

    /** DATE(received_at) <= expected_delivery_date, per engine. */
    private function onTimeSql(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? 'substr(purchase_orders.received_at, 1, 10) <= purchase_orders.expected_delivery_date'
            : 'DATE(purchase_orders.received_at) <= purchase_orders.expected_delivery_date';
    }

    /** Whole calendar days between sending the order and the goods landing. */
    private function daysSql(): string
    {
        // MySQL's DATEDIFF compares the dates and ignores the clock; sqlite has
        // no DATEDIFF, so the same truncation is done on the two bare dates.
        return DB::connection()->getDriverName() === 'sqlite'
            ? 'CAST(julianday(date(purchase_orders.received_at)) - julianday(date(purchase_orders.sent_at)) AS INTEGER)'
            : 'DATEDIFF(purchase_orders.received_at, purchase_orders.sent_at)';
    }

    /* ==================================================================
       Cache keys
       ================================================================== */

    /**
     * All-time reads the plain configured key, which is exactly what
     * PurchaseOrderObserver forgets by name. Anything else — a range, or the
     * trend — carries the range and the epoch, and is cleared by the epoch.
     */
    private function cacheKey(?string $from, ?string $to, string $variant = ''): string
    {
        $base = (string) config('procurement.cache.keys.supplier_performance');

        if ($variant === '' && $from === null && $to === null) {
            return $base;
        }

        return implode('.', array_filter([
            $base,
            $variant !== '' ? $variant : null,
            $this->epoch(),
            ($from ?? 'any').'_'.($to ?? 'any'),
        ]));
    }

    /**
     * The label of the current generation of ranged entries.
     *
     * The observer clears config('procurement.cache.keys') by name and cannot
     * know which ranges anybody has filtered by, so it deletes this stamp
     * instead: the next read mints a new one, every older ranged entry becomes
     * unreachable at once, and the rows themselves expire on their TTL.
     */
    private function epoch(): string
    {
        return (string) Cache::rememberForever(
            (string) config('procurement.cache.keys.supplier_performance_epoch'),
            fn (): string => substr(hash('sha256', (string) Str::uuid()), 0, 8)
        );
    }

    /* ==================================================================
       Chart scaffolding
       ================================================================== */

    /**
     * A month with no delivery still belongs on the axis — otherwise a blank
     * quarter quietly disappears instead of showing as a blank quarter.
     *
     * @param  array<string, array{on_time: int, late: int}>  $buckets
     * @return array<int, array{month: string, label: string, on_time: int, late: int}>
     */
    private function zeroFill(array $buckets, ?string $from, ?string $to): array
    {
        $first = $from
            ? Carbon::parse($from)->startOfMonth()
            : ($buckets ? Carbon::parse(array_key_first($buckets).'-01') : Carbon::parse(now()->startOfMonth()));

        $last = $to ? Carbon::parse($to)->startOfMonth() : Carbon::parse(now()->startOfMonth());

        $months = [];

        for ($cursor = $first; $cursor->lte($last) && count($months) < 60; $cursor->addMonth()) {
            $key = $cursor->format('Y-m');

            $months[] = [
                'month'   => $key,
                'label'   => $cursor->format('M Y'),
                'on_time' => (int) ($buckets[$key]['on_time'] ?? 0),
                'late'    => (int) ($buckets[$key]['late'] ?? 0),
            ];
        }

        return $months;
    }
}
