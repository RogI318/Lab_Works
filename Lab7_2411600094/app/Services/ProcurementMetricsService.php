<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Cache;

/**
 * Lab 7 Part 5 Step 1: the four numbers behind the procurement widgets.
 *
 * The sheet puts Cache::remember() in the view composers; this class is where
 * that call actually lives, because the composers AND the Part 5 Step 4 API
 * endpoints need the exact same rows. Two copies of a query drift — the widget
 * would show one number and the polling JSON another under it — so there is one
 * implementation and two readers. The composer still earns its keep: it decides
 * *when* the query runs (only if the partial renders), this class only decides
 * *how* it runs.
 *
 * Each method follows the same three rules:
 *   1. The answer is cached under a key from config('procurement.cache.keys')
 *      for the TTL next to it, so a dashboard refresh costs no queries at all.
 *   2. What goes into the cache is a plain array, never Eloquent models: a
 *      serialized model in the cache carries its relations and its dirty
 *      attributes, and half of them are not even read by the view.
 *   3. Anything the widget only counts is counted by the database.
 *
 * Invalidating is not this class's job — see flushWidgets() and the callers in
 * PurchaseOrderObserver and ReorderService.
 */
class ProcurementMetricsService
{
    public function __construct(
        private readonly ReorderService $reorder,
        private readonly SupplierPerformanceService $performance,
    ) {
    }

    /* ==================================================================
       Widget 1 — orders waiting for a signature
       ================================================================== */

    /**
     * @return array{count: int, value: float, generated_at: string}
     */
    public function pendingOrders(): array
    {
        return Cache::remember(
            config('procurement.cache.keys.pending_orders'),
            (int) config('procurement.cache.pending_orders_ttl'),
            function (): array {
                // Optimization: one aggregate row instead of a collection. The
                // widget shows two numbers, so a `->get()` of every pending
                // order would fetch whole models — and their line items are
                // never read here. COUNT/SUM do the work in the engine and
                // return 4 bytes. Soft deletes are added by the model, so a
                // deleted draft stops being a commitment immediately.
                $row = PurchaseOrder::query()
                    ->pending()
                    ->selectRaw('COUNT(*) AS aggregate, COALESCE(SUM(total), 0) AS value')
                    ->first();

                return [
                    'count'        => (int) ($row->aggregate ?? 0),
                    'value'        => (float) ($row->value ?? 0),
                    'generated_at' => now()->toIso8601String(),
                ];
            }
        );
    }

    /* ==================================================================
       Widget 2 — the last five orders raised
       ================================================================== */

    /**
     * @return array{rows: array<int, array<string, mixed>>, generated_at: string}
     */
    public function recentOrders(): array
    {
        return Cache::remember(
            config('procurement.cache.keys.recent_orders'),
            (int) config('procurement.cache.recent_orders_ttl'),
            function (): array {
                $orders = PurchaseOrder::query()
                    // Optimization: `supplier:id,name` eager loads the vendor in
                    // a second query AND selects only the two columns the card
                    // prints. Plain `with('supplier')` would pull every supplier
                    // column, including notes and terms nobody reads.
                    ->with('supplier:id,name')
                    // Optimization: the same seven columns the view touches —
                    // no description of the whole row is shipped to PHP.
                    ->select([
                        'id', 'reference_number', 'supplier_id', 'status',
                        'order_date', 'total', 'is_auto_generated',
                    ])
                    // The list is "newest first", and the newest thing a buyer
                    // cares about is the order date; id breaks ties the way the
                    // index page does, so both screens agree.
                    ->orderByDesc('order_date')
                    ->orderByDesc('id')
                    ->take(5)
                    ->get();

                return [
                    'rows' => $orders->map(fn (PurchaseOrder $order): array => [
                        'id'            => $order->id,
                        'reference'     => $order->reference_number,
                        'url'           => route('purchase-orders.show', $order),
                        'supplier'      => $order->supplier?->name ?? '(no supplier)',
                        'status'        => $order->status,
                        'status_label'  => $order->statusLabel(),
                        'badge_class'   => $order->badgeClass(),
                        'total'         => (float) $order->total,
                        'order_date'    => $order->order_date?->format('M j, Y') ?? '—',
                        'auto'          => $order->is_auto_generated,
                    ])->all(),
                    'generated_at' => now()->toIso8601String(),
                ];
            }
        );
    }

    /* ==================================================================
       Widget 3 — who delivers on time
       ================================================================== */

    /**
     * Top five suppliers by on-time delivery percentage.
     *
     * Lab 7 Part 6 Step 1 replaced the query underneath this method with the
     * real scorecard service, exactly as the Part 5 note promised: the composer
     * and the Step 4 API endpoint still read the same keys, so the widget view,
     * its resource and the polling JavaScript did not change. What did change is
     * that the numbers now come from the same cached rows the supplier pages
     * read, instead of a second copy of a similar query.
     *
     * There is no Cache::remember() here any more on purpose: the service holds
     * the one entry under procurement.supplier_performance, and remembering this
     * shaped copy again would put a second version of the same rows in the cache
     * table for the observer to fall out of step with.
     *
     * @return array{rows: array<int, array<string, mixed>>, generated_at: string}
     */
    public function supplierPerformance(): array
    {
        $scorecard = $this->performance->forAll();

        $rows = collect($scorecard['rows'])
            // A retired vendor is not a performer — Part 5's rule, kept.
            ->filter(fn (array $row): bool => $row['is_active'])
            // Nothing delivered yet means nothing to rank (null percentage).
            ->filter(fn (array $row): bool => $row['received_orders'] > 0)
            ->sortByDesc('on_time_percentage')
            ->take(5)
            ->map(fn (array $row): array => [
                'id'        => $row['supplier_id'],
                'name'      => $row['name'],
                'received'  => $row['received_orders'],
                'on_time'   => $row['on_time_orders'],
                'percent'   => $row['on_time_percentage'],
                // The band travels with the data, so the widget and the polled
                // JSON can never paint the same supplier two different colours.
                'bar_class' => $row['rating_badge_class'],
                'url'       => route('suppliers.show', ['supplier' => $row['supplier_id']]),
            ])
            ->values()
            ->all();

        return ['rows' => $rows, 'generated_at' => $scorecard['generated_at']];
    }

    /* ==================================================================
       Widget 4 — what the reorder engine wants to buy
       ================================================================== */

    /**
     * The six most urgent short items, with the quantity the engine would order
     * and the vendor it would order them from.
     *
     * @return array{rows: array<int, array<string, mixed>>, generated_at: string}
     */
    public function reorderAlerts(): array
    {
        return Cache::remember(
            config('procurement.cache.keys.reorder_report'),
            (int) config('procurement.cache.reorder_report_ttl'),
            function (): array {
                // Optimization: ReorderService::shortItems() is already the
                // cheapest possible scan — three batch queries for the whole
                // catalogue (products, usage per product, units on open order),
                // memoised on the instance, not one per product. Caching its
                // output on top means the daily dashboard pays for those three
                // once per TTL instead of once per page view.
                $short = $this->reorder->shortItems();

                // Urgency, not the catalogue order the e-mail digest uses: an
                // item the engine can actually buy first, then how far under its
                // reorder point the stock sits.
                usort($short, fn (array $a, array $b): int => $b['suggested'] <=> $a['suggested']
                    ?: ($b['point'] - $b['on_hand']) <=> ($a['point'] - $a['on_hand']));

                $rows = array_map(fn (array $row): array => [
                    'id'        => $row['id'],
                    'sku'       => $row['sku'],
                    'name'      => $row['name'],
                    'url'       => route('products.show', ['product' => $row['id']]),
                    'on_hand'   => $row['on_hand'],
                    'point'     => $row['point'],
                    'suggested' => $row['suggested'],
                    'on_order'  => $row['on_order'],
                    'supplier'  => $row['supplier'],
                ], array_slice($short, 0, 6));

                return ['rows' => $rows, 'generated_at' => now()->toIso8601String()];
            }
        );
    }

    /* ==================================================================
       Invalidation
       ================================================================== */

    /**
     * Delete every cached widget reading.
     *
     * Called by PurchaseOrderObserver on any status change and by ReorderService
     * after it raises drafts. It loops over the config list rather than naming
     * keys, so the rule is "a change clears the whole procurement cache" and a
     * widget added later cannot be forgotten — literally.
     */
    public static function flushWidgets(): void
    {
        foreach ((array) config('procurement.cache.keys') as $key) {
            Cache::forget((string) $key);
        }
    }
}
