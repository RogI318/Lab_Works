<?php
// LAB 6, PART 7: API routes (served under /api)
// routes/api.php
//
// These endpoints use the normal login session, so the browser's cookie
// proves who is asking (no tokens needed).

use App\Http\Controllers\Api\DashboardApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard/stats', [DashboardApiController::class, 'stats']);
    Route::get('/dashboard/charts', [DashboardApiController::class, 'charts']);
    Route::get('/products/low-stock', [DashboardApiController::class, 'lowStock']);
    Route::get('/transactions/recent', [DashboardApiController::class, 'recentTransactions']);
    Route::get('/products/{product}/stock', [DashboardApiController::class, 'stock']);
});
