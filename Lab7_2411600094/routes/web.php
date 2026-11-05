<?php

use App\Http\Controllers\Api\AcademicDashboardController;
use App\Http\Controllers\Api\RealtimeController;
use App\Http\Controllers\AcademicInterventionController;
use App\Http\Controllers\AssignmentTransactionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterventionPdfController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Middleware\EnsureUserIsAdmin;
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

    // ================= ADMIN ONLY (Lab 6 + Lab 7 features) =================
    Route::middleware(EnsureUserIsAdmin::class)->group(function () {

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

        // JSON endpoints polled by the dashboard (Lab 6)
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/dashboard/stats', [RealtimeController::class, 'stats'])->name('stats');
            Route::get('/students/high-backlog', [RealtimeController::class, 'highBacklog'])->name('high-backlog');
            Route::get('/students/{student}/backlog', [RealtimeController::class, 'studentBacklog'])->name('student-backlog');
            Route::get('/transactions/recent', [RealtimeController::class, 'recentTransactions'])->name('recent');

            // Lab 7 academic monitoring JSON endpoints
            Route::get('/academic-dashboard/stats', [AcademicDashboardController::class, 'stats'])->name('academic.stats');
            Route::get('/students/heavy-backlog', [AcademicDashboardController::class, 'heavyBacklogStudents'])->name('academic.heavy-backlog');
            Route::get('/interventions/recent', [AcademicDashboardController::class, 'recentInterventions'])->name('academic.interventions.recent');
            Route::get('/interventions/summary', [AcademicDashboardController::class, 'interventionSummary'])->name('academic.interventions.summary');
        });

        // Academic Intervention Management
        // export routes MUST come before the resource route
        Route::get('/interventions/export', [AcademicInterventionController::class, 'export'])
            ->name('interventions.export');

        Route::get('/interventions/export/pdf', InterventionPdfController::class)
            ->name('interventions.export.pdf');

        Route::resource('interventions', AcademicInterventionController::class);

        Route::post('/interventions/{intervention}/approve', [AcademicInterventionController::class, 'approve'])
            ->name('interventions.approve');

        Route::post('/interventions/{intervention}/reject', [AcademicInterventionController::class, 'reject'])
            ->name('interventions.reject');

        Route::post('/interventions/{intervention}/complete', [AcademicInterventionController::class, 'complete'])
            ->name('interventions.complete');

        Route::post('/interventions/{id}/restore', [AcademicInterventionController::class, 'restore'])
            ->name('interventions.restore');
    });

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';