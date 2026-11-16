<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {

    // Dashboard 
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products: full resource (index, create, store, show, edit, update, destroy)
    Route::resource('products', ProductController::class);

    // Brands and Categories: CRUD runs through modals, so only index/store/
    // update/destroy are needed.
    Route::resource('brands', BrandController::class)->except(['create', 'edit', 'show']);
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);

    // User management — administrators only (there is no public registration any more).
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
    });

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';