<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
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
