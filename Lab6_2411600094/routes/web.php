<?php

use App\Http\Controllers\Api\RealtimeController;
use App\Http\Controllers\AssignmentTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Students — export must come BEFORE the resource route
    Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');
    Route::resource('students', StudentController::class);

    // Courses
    Route::resource('courses', CourseController::class)->except('show');

        // ---- Lab 6: Assignment tracking ----
    // adjust routes MUST come before the resource route
    Route::get('/assignments/adjust', [AssignmentTransactionController::class, 'adjust'])->name('assignments.adjust');
    Route::post('/assignments/adjust', [AssignmentTransactionController::class, 'storeAdjustment'])->name('assignments.adjust.store');
    Route::resource('assignments', AssignmentTransactionController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/movements/export', [ReportController::class, 'exportMovements'])->name('reports.export');

    // Notifications (bell)
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // JSON endpoints polled by the dashboard (session-protected)
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/dashboard/stats', [RealtimeController::class, 'stats'])->name('stats');
        Route::get('/students/high-backlog', [RealtimeController::class, 'highBacklog'])->name('high-backlog');
        Route::get('/students/{student}/backlog', [RealtimeController::class, 'studentBacklog'])->name('student-backlog');
        Route::get('/transactions/recent', [RealtimeController::class, 'recentTransactions'])->name('recent');
    });
    
    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';