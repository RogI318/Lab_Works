<?php

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ProcurementWidgetController;
use Illuminate\Support\Facades\Route;

/*
 * Lab 6 Part 7: JSON consumed by the dashboard's 30-second poller.
 * Same-origin session auth, so the routes run through the web group.
 */
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard/stats', [DashboardApiController::class, 'stats']);
    Route::get('/products/low-stock', [DashboardApiController::class, 'lowStock']);
    Route::get('/transactions/recent', [DashboardApiController::class, 'recent']);

    /*
     * Lab 7 Part 5 Step 4: the four procurement widgets, same group and same
     * poller. No model binding and no route parameters — each endpoint answers
     * one whole widget, which is what keeps them cheap enough to ask for every
     * thirty seconds: the cached rows the page already rendered are still warm.
     */
    Route::get('/dashboard/pending-orders', [ProcurementWidgetController::class, 'pendingOrders'])->name('api.dashboard.pending-orders');
    Route::get('/dashboard/recent-orders', [ProcurementWidgetController::class, 'recentOrders'])->name('api.dashboard.recent-orders');
    Route::get('/dashboard/supplier-performance', [ProcurementWidgetController::class, 'supplierPerformance'])->name('api.dashboard.supplier-performance');
    Route::get('/dashboard/reorder-alerts', [ProcurementWidgetController::class, 'reorderAlerts'])->name('api.dashboard.reorder-alerts');
});
