<?php

namespace App\Http\Controllers;

use App\Models\AcademicIntervention;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class AcademicProgressController extends Controller
{
    public function __invoke()
    {
        $limit = (int) Student::BACKLOG_LIMIT;

        // ---- Student overview ----
        $totalStudents = Student::count();
        $avgGpa = (float) Student::avg('gpa');
        $avgAttendance = (float) Student::avg('attendance');
        $heavyBacklogCount = Student::heavyBacklog()->count();

        // ---- Intervention workflow ----
        $statusOrder = ['pending', 'approved', 'in_progress', 'completed', 'rejected'];

        $counts = AcademicIntervention::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusData = [];
        foreach ($statusOrder as $status) {
            $statusData[$status] = (int) ($counts[$status] ?? 0);
        }

        $totalInterventions = array_sum($statusData);
        $approvedPath = $statusData['approved'] + $statusData['in_progress'] + $statusData['completed'];
        $decided = $approvedPath + $statusData['rejected'];

        $approvalRate = $decided > 0 ? round($approvedPath / $decided * 100, 1) : 0;
        $completionRate = $approvedPath > 0 ? round($statusData['completed'] / $approvedPath * 100, 1) : 0;

        $avgApprovalHours = AcademicIntervention::whereNotNull('approved_at')
            ->get(['created_at', 'approved_at'])
            ->avg(fn ($i) => abs($i->created_at->diffInMinutes($i->approved_at)) / 60);

        $avgApprovalHours = $avgApprovalHours !== null ? round($avgApprovalHours, 1) : null;

        // ---- Academic standing ----
        $standingData = Student::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // ---- Program summary ----
        $programs = Student::select(
            'program',
            DB::raw('COUNT(*) as students'),
            DB::raw('AVG(gpa) as avg_gpa'),
            DB::raw('AVG(attendance) as avg_attendance'),
            DB::raw('SUM(pending_assignments) as pending'),
            DB::raw("SUM(CASE WHEN pending_assignments >= {$limit} THEN 1 ELSE 0 END) as heavy")
        )
            ->groupBy('program')
            ->orderBy('program')
            ->get();

        // ---- Highest backlogs ----
        $topBacklog = Student::orderByDesc('pending_assignments')
            ->orderBy('name')
            ->take(5)
            ->get();

        return view('reports.academic-progress', compact(
            'totalStudents',
            'avgGpa',
            'avgAttendance',
            'heavyBacklogCount',
            'statusData',
            'totalInterventions',
            'approvalRate',
            'completionRate',
            'avgApprovalHours',
            'standingData',
            'programs',
            'topBacklog'
        ));
    }
}