<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockAdjustmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Part 6 Step 1 — every inventory page sits behind the auth middleware.
Route::middleware('auth')->group(function () {

    // Dashboard — real inventory statistics from MySQL
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products: full resource (index, create, store, show, edit, update, destroy)
    Route::resource('products', ProductController::class);

    // Lab 6 Part 3: inventory transaction ledger (no edit/update —
    // movements are corrected by deleting and re-recording, admin only).
    Route::resource('transactions', InventoryTransactionController::class)
        ->except(['edit', 'update']);

    // Lab 6 Part 8: stock adjustments. The form itself is the modal on the
    // transactions list, so only the AJAX lookup and the submit live here.
    Route::prefix('adjustments')->name('adjustments.')->group(function () {
        Route::get('/{product}/quantity', [StockAdjustmentController::class, 'quantity'])
            ->whereNumber('product')->name('quantity');
        Route::post('/', [StockAdjustmentController::class, 'store'])->name('store');
    });

    // Lab 6 Part 5/6: notification bell (open marks read, jump to product)
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])
        ->name('notifications.show');
    Route::put('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.readAll');

    // Brands and Categories: CRUD runs through modals, so only index/store/
    // update/destroy are needed.
    Route::resource('brands', BrandController::class)->except(['create', 'edit', 'show']);
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
