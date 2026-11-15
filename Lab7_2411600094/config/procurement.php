<?php

/**
 * Lab 7 Part 2 Step 3: the numbers the procurement engine is built on.
 *
 * Everything the purchase order, reorder and supplier-report code needs to
 * agree about lives here instead of being typed into a formula, so a change of
 * VAT rate or review cadence is a one-line edit rather than a hunt through the
 * app. Values follow the decisions made for this exercise; read them as policy,
 * not as constants in code.
 */

return [

    // Output VAT applied to every purchase order subtotal.
    'tax_rate' => (float) env('PROCUREMENT_TAX_RATE', 0.12),

    // Part 3 — demand is averaged over this many days of ledger history.
    'lookback_days' => (int) env('PROCUREMENT_LOOKBACK_DAYS', 30),

    // Part 3 — used when a supplier has no lead time on file.
    'default_lead_time_days' => 7,

    // Part 3 — buffer kept on top of lead-time demand.
    'default_safety_stock_days' => 3,

    // Part 3/6 — how often the same item may be reordered automatically.
    'review_period_days' => 14,

    // Part 3 — the daily reorder sweep time (routes/console.php).
    'reorder_check_time' => '06:00',

    // CACHE_STORE is `database`, so tagged caching is unavailable; these are
    // plain key lifetimes for Cache::remember().
    'cache' => [
        'supplier_performance_ttl' => 60 * 30,   // 30 minutes
        'reorder_report_ttl'       => 60 * 10,   // 10 minutes

        // Part 5 — the two order widgets read rows that move while somebody is
        // looking at the dashboard, so their entries are short-lived. The TTL is
        // only the backstop: PurchaseOrderObserver deletes these keys on every
        // status change, which is why an approval shows up inside one poll.
        'pending_orders_ttl'       => 60,        // 1 minute
        'recent_orders_ttl'        => 60,        // 1 minute

        // The key names live here too, so the writer (the Part 3 reorder engine,
        // the Part 4 observer) and the reader (the Part 5 dashboard widgets) can
        // never drift apart: every key listed here is exactly what a change
        // clears, so a widget added in Part 6 is invalidated for free.
        'keys' => [
            'reorder_report'       => 'procurement.reorder_report',
            'supplier_performance' => 'procurement.supplier_performance',
            'pending_orders'       => 'procurement.pending_orders',
            'recent_orders'        => 'procurement.recent_orders',

            // Part 6 Step 1 — the supplier report caches one entry PER DATE
            // RANGE, and a range cannot be guessed by name. So the observer
            // clears this stamp instead: the next read mints a new one and every
            // older ranged entry stops being reachable at once. Adding it to
            // this list is all it took — the flush already loops over these.
            'supplier_performance_epoch' => 'procurement.supplier_performance.epoch',
        ],
    ],
];
