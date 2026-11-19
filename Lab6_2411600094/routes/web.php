<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AccountChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\InventoryOrderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// IDs in the URL must be whole numbers only.
// (Without this, /products/1abc would open product 1.)
Route::pattern('product', '[0-9]+');
Route::pattern('brand', '[0-9]+');
Route::pattern('category', '[0-9]+');
Route::pattern('user', '[0-9]+');
Route::pattern('transaction', '[0-9]+');

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/account/confirm/{user}/{token}', [AccountChangeController::class, 'confirm'])
    ->middleware('signed')->name('account.confirm-changes');

// 'verified': a new account must confirm its email (link sent after registering) before using the system.
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard — real inventory statistics from MySQL
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('purchase-orders', [InventoryOrderController::class, 'index'])->defaults('direction', 'purchase')->name('purchase-orders.index');
    Route::get('sales-orders', [InventoryOrderController::class, 'index'])->defaults('direction', 'sale')->name('sales-orders.index');
    Route::get('purchase-orders/latest', [InventoryOrderController::class, 'latest'])->defaults('direction', 'purchase')->name('purchase-orders.latest');
    Route::get('sales-orders/latest', [InventoryOrderController::class, 'latest'])->defaults('direction', 'sale')->name('sales-orders.latest');
    Route::get('orders/create', [InventoryOrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [InventoryOrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}/receipt', [InventoryOrderController::class, 'receipt'])->whereNumber('order')->name('orders.receipt');
    Route::post('orders/{order}/approve', [InventoryOrderController::class, 'approve'])->whereNumber('order')->name('orders.approve')->middleware('can:manage-users');
    Route::get('orders/{order}', [InventoryOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    Route::get('orders/{order}/status', [InventoryOrderController::class, 'status'])->whereNumber('order')->name('orders.status');
    Route::post('orders/{order}/complete', [InventoryOrderController::class, 'complete'])->whereNumber('order')->name('orders.complete');
    Route::post('orders/{order}/cancel', [InventoryOrderController::class, 'cancel'])->whereNumber('order')->name('orders.cancel');

    Route::get('products/trash', [ProductController::class, 'trash'])->name('products.trash');
    Route::post('products/{id}/restore', [ProductController::class, 'restore'])->whereNumber('id')->name('products.restore');
    Route::patch('products/{product}/deactivate', [ProductController::class, 'deactivate'])->name('products.deactivate');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy')->missing(fn() => redirect()->route('products.index'));
    Route::get('notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('can:manage-users')->name('audit-logs.index');
    Route::get('audit-logs/latest', [AuditLogController::class, 'latest'])->middleware('can:manage-users')->name('audit-logs.latest');

    // ->missing(...): a number that does not exist (like /products/999)
    // goes back to the list instead of showing an error page.

    // Products: no delete. A product becomes Inactive when it is out of stock and
    // Active again when it is restocked (worked out from its quantity).
    Route::resource('products', ProductController::class)
        ->except(['destroy'])
        ->missing(fn() => redirect()->route('products.index'));

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

    // Brands and Categories: CRUD runs through modals, so only index/store/update/destroy.
    Route::resource('brands', BrandController::class)
        ->except(['create', 'edit', 'show'])
        ->missing(fn() => redirect()->route('brands.index'));

    Route::resource('categories', CategoryController::class)
        ->except(['create', 'edit', 'show'])
        ->missing(fn() => redirect()->route('categories.index'));

    Route::patch('suppliers/{supplier}/status', [SupplierController::class, 'toggleStatus'])->name('suppliers.status');
    Route::post('suppliers/quick', [InventoryOrderController::class, 'quickStoreSupplier'])->name('suppliers.quick-store');
    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit']);

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // User management: administrators only (the 'admin' middleware does the check).
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)
            ->except(['create', 'edit', 'show'])
            ->missing(fn() => redirect()->route('users.index'));

        // Anything that is not a real users page (/users/*, /users/abc) goes back to the list.
        Route::get('users/{any}', fn() => redirect()->route('users.index'))->where('any', '.*');
    });

    // Catch-all for each section. It must stay AFTER the resources above.
    // Anything that is not a real page, like /products/* or /brands/abc,
    // goes back to that section's list and changes nothing.
    foreach (['products', 'brands', 'categories', 'transactions', 'suppliers'] as $section) {
        Route::get($section . '/{any}', fn() => redirect()->route($section . '.index'))
            ->where('any', '.*');
    }

    // Unknown nested pages return to their own section, not a framework 404.
    foreach ([
        'reports' => 'reports.index',
        'purchase-orders' => 'purchase-orders.index',
        'sales-orders' => 'sales-orders.index',
        'adjustments' => 'transactions.index',
        'audit-logs' => 'audit-logs.index',
    ] as $section => $destination) {
        Route::get($section . '/{any}', fn() => redirect()->route($destination))->where('any', '.*');
    }

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Login, logout, password reset... (Breeze). Do not remove this line.
require __DIR__ . '/auth.php';

// Catch malformed/unknown URLs too, and send visitors to a useful page
// instead of showing the framework or web-server 404 screen.
$unknownUrlDestination = function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
};

Route::any('/{unknownPath?}', $unknownUrlDestination)
    ->where('unknownPath', '.*')
    ->name('unknown.redirect');

Route::fallback($unknownUrlDestination);
