<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicIntervention;
use App\Models\Student;
use App\Support\AcademicStats;
use Illuminate\Http\JsonResponse;

class AcademicDashboardController extends Controller
{
    /**
     * Return academic dashboard statistics (cached for 60 seconds).
     */
    public function stats(): JsonResponse
    {
        return response()->json(AcademicStats::get());
    }

    /**
     * Return students with heavy assignment backlogs.
     */
    public function heavyBacklogStudents(): JsonResponse
    {
        $students = Student::heavyBacklog()->get();

        return response()->json([
            'success' => true,
            'data' => $students,
            'count' => $students->count(),
        ]);
    }

    /**
     * Return recent academic interventions.
     */
    public function recentInterventions(): JsonResponse
    {
        $interventions = AcademicIntervention::with([
            'student',
            'creator',
            'approver',
        ])
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $interventions,
            'count' => $interventions->count(),
        ]);
    }

    /**
     * Return intervention counts grouped by status.
     */
    public function interventionSummary(): JsonResponse
    {
        $summary = AcademicIntervention::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}