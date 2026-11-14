<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Part 6 Step 1 — every inventory page sits behind the auth middleware.
Route::middleware('auth')->group(function () {

    // Dashboard — real inventory statistics from MySQL
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products: full resource (index, create, store, show, edit, update, destroy)
    // destroy() also binds trashed models: deleting an already-deleted product
    // is the admin's "delete permanently" action.
    Route::resource('products', ProductController::class)->withTrashed(['destroy']);

    // Soft-deleted products: restore is administrator only.
    Route::middleware('admin')->group(function () {
        Route::put('/products/{product}/restore', [ProductController::class, 'restore'])
            ->withTrashed()->name('products.restore');
    });

    // ================= LAB 7 PART 1: SUPPLIER MANAGEMENT =================
    // Role checks live in SupplierPolicy (Gate::authorize in the controller)
    // rather than the 'admin' middleware, because manager and warehouse staff
    // must reach these pages too — only delete/restore is admin-only.
    Route::put('/suppliers/{supplier}/restore', [SupplierController::class, 'restore'])
        ->withTrashed()->name('suppliers.restore');
    Route::resource('suppliers', SupplierController::class);

    // ================= LAB 7 PART 2: PURCHASE ORDERS =================
    // Each workflow verb is its own route rather than a field on the edit
    // form: an approval is a decision with an author and a timestamp, and
    // keeping it a separate PUT is what lets PurchaseOrderPolicy guard it and
    // the activity log record it.
    Route::get('/purchase-orders/suppliers/{supplier}/products', [PurchaseOrderController::class, 'supplierProducts'])
        ->name('purchase-orders.supplier-products');

    Route::put('/purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::put('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::put('/purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::put('/purchase-orders/{purchaseOrder}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::put('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');

    Route::resource('purchase-orders', PurchaseOrderController::class)
        ->parameters(['purchase-orders' => 'purchaseOrder']);

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

    // Lab 5 (latest): user management — administrators only, since there is
    // no public registration any more.
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
    });

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
