<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ---- Stat cards (same four as Lab 4) ----
        $avgGpa             = round((float) Student::avg('gpa'), 2);
        $courseCount        = Course::count();
        $pendingAssignments = (int) Student::sum('pending_assignments');
        $avgAttendance      = (int) round((float) Student::avg('attendance'));

        $totalStudents  = Student::count();
        $atRiskCount    = Student::where('status', 'At Risk')->count();
        $probationCount = Student::where('status', 'Probation')->count();

        // ---- Alert center ----
        $flaggedStudents = Student::whereIn('status', ['At Risk', 'Probation'])
                                  ->orderBy('gpa')
                                  ->take(6)
                                  ->get();

        // ---- Chart 1: Quality Points (avg GPA) by Program ----
        $gpaByProgram = Student::select('program', DB::raw('AVG(gpa) as avg_gpa'))
                               ->groupBy('program')
                               ->orderBy('program')
                               ->get();

        // ---- Chart 2: Academic Standing Distribution ----
        $standingDist = [
            'Good Standing' => Student::where('status', 'Good Standing')->count(),
            'At Risk'       => $atRiskCount,
            'Probation'     => $probationCount,
        ];

        // ---- Chart 3: Top 5 Students by GPA ----
        $topStudents = Student::orderByDesc('gpa')->take(5)->get();

        // ---- Chart 4: Activity by Category ----
        $activityByCategory = Activity::select('category', DB::raw('COUNT(*) as total'))
                                      ->groupBy('category')
                                      ->orderBy('category')
                                      ->get();

        // ---- Recent Activity table ----
        $recentActivities = Activity::with('student')
                                    ->orderByDesc('occurred_at')
                                    ->take(10)
                                    ->get();

        return view('dashboard', compact(
            'avgGpa', 'courseCount', 'pendingAssignments', 'avgAttendance',
            'totalStudents', 'atRiskCount', 'probationCount',
            'flaggedStudents', 'gpaByProgram', 'standingDist',
            'topStudents', 'activityByCategory', 'recentActivities'
        ));
    }
}