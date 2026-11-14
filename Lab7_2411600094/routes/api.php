<?php

use App\Http\Controllers\Api\DashboardApiController;
use Illuminate\Support\Facades\Route;

/*
 * Lab 6 Part 7: JSON consumed by the dashboard's 30-second poller.
 * Same-origin session auth, so the routes run through the web group.
 */
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard/stats', [DashboardApiController::class, 'stats']);
    Route::get('/products/low-stock', [DashboardApiController::class, 'lowStock']);
    Route::get('/transactions/recent', [DashboardApiController::class, 'recent']);
});
