<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AcademicDashboardController;

Route::middleware(['auth:sanctum', 'admin-only'])->group(function () {

    Route::get('/dashboard/stats', [AcademicDashboardController::class, 'stats']);

    Route::get('/students/heavy-backlog', [AcademicDashboardController::class, 'heavyBacklogStudents']);

    Route::get('/interventions/recent', [AcademicDashboardController::class, 'recentInterventions']);

    Route::get('/interventions/summary', [AcademicDashboardController::class, 'interventionSummary']);

});