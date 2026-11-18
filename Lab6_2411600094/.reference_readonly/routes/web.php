<?php
// routes/web.php
// Lab 5 routes + Lab 6 routes (permissions, transactions, adjustments, reports, bell, trash)

use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ->missing(...) : a number that does not exist (like /products/999)
    // goes back to the list instead of showing an error page

    // ---- ADMIN ONLY: User Management ----
    Route::middleware('can:manage-users')->group(function () {
        // LAB 6: audit trail
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::resource('users', UserController::class)
            ->missing(fn () => redirect()->route('users.index'));
    });

    // ---- Admin AND staff: management features ----
    // These must stay BEFORE the "everyone" routes, so /products/create is not read as /products/{product}
    Route::middleware('can:manage-stock')->group(function () {
        // Trash (soft deleted products) - must stay before products/{product}
        Route::get('products/trash', [ProductController::class, 'trash'])->name('products.trash');
        Route::post('products/{id}/restore', [ProductController::class, 'restore'])
            ->where('id', '[0-9]+')->name('products.restore');

        Route::patch('products/{product}/deactivate', [ProductController::class, 'deactivate'])
            ->name('products.deactivate');

        Route::resource('products', ProductController::class)->except(['index', 'show'])
            ->missing(fn () => redirect()->route('products.index'));
        Route::resource('brands', BrandController::class)->except(['index', 'show'])
            ->missing(fn () => redirect()->route('brands.index'));
        Route::resource('categories', CategoryController::class)->except(['index', 'show'])
            ->missing(fn () => redirect()->route('categories.index'));

        // Stock In / Stock Out (Lab 5 form on the product page + Lab 6 transaction form)
        Route::post('products/{product}/transactions', [InventoryTransactionController::class, 'storeForProduct'])
            ->name('products.transactions.store');
        Route::resource('transactions', InventoryTransactionController::class)->only(['create', 'store']);
        Route::delete('transactions/{transaction}', [InventoryTransactionController::class, 'destroy'])
            ->name('transactions.destroy')->middleware('can:admin')
            ->missing(fn () => redirect()->route('transactions.index'));

        // Stock adjustments
        Route::get('adjustments/create', [AdjustmentController::class, 'create'])->name('adjustments.create');
        Route::post('adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store');
    });

    // ---- Everyone who is logged in: view pages ----
    Route::resource('products', ProductController::class)->only(['index', 'show'])
        ->missing(fn () => redirect()->route('products.index'));
    Route::resource('brands', BrandController::class)->only(['index', 'show'])
        ->missing(fn () => redirect()->route('brands.index'));
    Route::resource('categories', CategoryController::class)->only(['index', 'show'])
        ->missing(fn () => redirect()->route('categories.index'));
    Route::resource('transactions', InventoryTransactionController::class)->only(['index', 'show'])
        ->missing(fn () => redirect()->route('transactions.index'));

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Bell notifications
    Route::get('notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Catch-all for each section. It must stay AFTER all the routes above.
    // Anything that is not a real page, like /products/* or /products/abc,
    // just goes back to that section's list and changes nothing.
    foreach (['products', 'brands', 'categories', 'users', 'transactions', 'audit-logs'] as $section) {
        Route::get($section . '/{any}', fn () => redirect()->route($section . '.index'))
            ->where('any', '.*');
    }
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Any other unknown URL: go up to the nearest real parent page, else dashboard or login
Route::fallback(function (Request $request) {
    $segments = explode('/', trim($request->path(), '/'));

    while (count($segments) > 1) {
        array_pop($segments);
        $parent = '/'.implode('/', $segments);

        try {
            $route = Route::getRoutes()->match(Request::create($parent, 'GET'));
        } catch (\Throwable $e) {
            continue;
        }

        if (! $route->isFallback) {
            return redirect($parent);
        }
    }

    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});
