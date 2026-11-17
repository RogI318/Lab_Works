<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// IDs in the URL must be whole numbers only.
// (Without this, /products/1abc would open product 1.)
Route::pattern('product', '[0-9]+');
Route::pattern('brand', '[0-9]+');
Route::pattern('category', '[0-9]+');
Route::pattern('user', '[0-9]+');

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// 'verified': a new account must confirm its email (link sent after registering) before using the system.
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ->missing(...): a number that does not exist (like /products/999)
    // goes back to the list instead of showing an error page.

    // Products: no delete. A product becomes Inactive when it is out of stock and
    // Active again when it is restocked (worked out from its quantity).
    Route::resource('products', ProductController::class)
        ->except(['destroy'])
        ->missing(fn () => redirect()->route('products.index'));

    // Brands and Categories: CRUD runs through modals, so only index/store/update/destroy.
    Route::resource('brands', BrandController::class)
        ->except(['create', 'edit', 'show'])
        ->missing(fn () => redirect()->route('brands.index'));

    Route::resource('categories', CategoryController::class)
        ->except(['create', 'edit', 'show'])
        ->missing(fn () => redirect()->route('categories.index'));

    // User management: administrators only (the 'admin' middleware does the check).
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)
            ->except(['create', 'edit', 'show'])
            ->missing(fn () => redirect()->route('users.index'));

        // Anything that is not a real users page (/users/*, /users/abc) goes back to the list.
        Route::get('users/{any}', fn () => redirect()->route('users.index'))->where('any', '.*');
    });

    // Catch-all for each section. It must stay AFTER the resources above.
    // Anything that is not a real page, like /products/* or /brands/abc,
    // goes back to that section's list and changes nothing.
    foreach (['products', 'brands', 'categories'] as $section) {
        Route::get($section . '/{any}', fn () => redirect()->route($section . '.index'))
            ->where('any', '.*');
    }

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Login, logout, password reset... (Breeze). Do not remove this line.
require __DIR__.'/auth.php';

// Any other URL that does not exist (/dashboard/*, /public, /vendor, ...)
// goes to the dashboard if logged in, or to login if not.
Route::fallback(function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});