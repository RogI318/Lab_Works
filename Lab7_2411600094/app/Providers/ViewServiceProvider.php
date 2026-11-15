<?php

namespace App\Providers;

use App\Services\ProcurementMetricsService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Lab 7 Part 5 Step 1: the procurement widgets get their own data bindings.
 *
 * A view composer is the reason these four cards cost nothing when nobody
 * looks at them. DashboardController::index() builds ten Lab 6 numbers on every
 * request; if it also built the procurement figures, then (a) staff — who never
 * see a widget — would pay for four queries per page view, and (b) the queries
 * would run even when the section is not rendered at all. Bound to the partials
 * instead, a composer fires at the moment its own view renders: no widget, no
 * query.
 *
 * The numbers themselves come from ProcurementMetricsService, which is where
 * Cache::remember() and the optimizations live (a query duplicated between a
 * composer and the Step 4 API endpoint is a widget whose JSON disagrees with
 * the HTML underneath it). Each method below is one cache read:
 *
 *   pendingOrders()      COUNT + SUM over pending orders   — 1 query, 60 s TTL
 *   recentOrders()       5 rows, 7 columns, supplier:id,name — 2 queries, 60 s
 *   supplierPerformance() grouped CASE/SUM per vendor        — 2 queries, 30 min
 *   reorderAlerts()      the engine's 3 batch scans          — 3 queries, 10 min
 *
 * So a logged-in admin's dashboard costs at most 8 queries, and the second one
 * within the TTL costs none — PurchaseOrderObserver and ReorderService delete
 * the keys the moment the facts behind them change.
 */
class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $metrics = fn (): ProcurementMetricsService => $this->app->make(ProcurementMetricsService::class);

        // "Orders waiting for me": how many, and how much money that is.
        View::composer('widgets.pending-orders', function ($view) use ($metrics): void {
            $view->with('pending', $metrics()->pendingOrders());
        });

        // The five most recent purchase orders, newest first.
        View::composer('widgets.recent-orders', function ($view) use ($metrics): void {
            $view->with('orders', $metrics()->recentOrders());
        });

        // Top five suppliers by on-time delivery percentage (Part 6 extends
        // this same binding with the full cached performance service).
        View::composer('widgets.supplier-performance', function ($view) use ($metrics): void {
            $view->with('performance', $metrics()->supplierPerformance());
        });

        // The six most urgent short items, with the quantity and vendor the
        // reorder engine would use.
        View::composer('widgets.reorder-alerts', function ($view) use ($metrics): void {
            $view->with('reorder', $metrics()->reorderAlerts());
        });
    }
}
