<?php

namespace App\Http\Controllers;

use App\Models\AssignmentTransaction;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);

        // 1. Assignment Movement Report
        $movements = $this->movementQuery($from, $to)->get();
        $assigned  = (int) $movements->where('type', 'assigned')->sum('quantity');
        $submitted = abs((int) $movements->where('type', 'submitted')->sum('quantity'));
        $adjusted  = (int) $movements->where('type', 'adjustment')->sum('quantity');
        $net       = (int) $movements->sum('quantity');

        // 2. Heavy Backlog Report
        $heavy = Student::heavyBacklog()->orderByDesc('pending_assignments')->get();

        // 3. Academic Performance Summary (like the valuation report)
        $summary = Student::select(
                'program',
                DB::raw('COUNT(*) as students'),
                DB::raw('AVG(gpa) as avg_gpa'),
                DB::raw('SUM(pending_assignments) as pending'),
                DB::raw('AVG(attendance) as avg_attendance')
            )->groupBy('program')->orderBy('program')->get();

        $overall = [
            'students'       => Student::count(),
            'avg_gpa'        => (float) Student::avg('gpa'),
            'pending'        => (int) Student::sum('pending_assignments'),
            'avg_attendance' => (float) Student::avg('attendance'),
        ];

        return view('reports.index', compact(
            'from', 'to', 'movements', 'assigned', 'submitted', 'adjusted', 'net',
            'heavy', 'summary', 'overall'
        ));
    }

    /** CSV export of the movement report. */
    public function exportMovements(Request $request)
    {
        [$from, $to] = $this->range($request);
        $rows = $this->movementQuery($from, $to)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Student ID', 'Student', 'Type', 'Quantity', 'Reference', 'Reason', 'Recorded By']);
            foreach ($rows as $t) {
                fputcsv($out, [
                    $t->created_at->format('Y-m-d H:i'),
                    $t->student->student_id ?? '',
                    $t->student->name ?? '',
                    $t->type_label,
                    $t->quantity,
                    $t->reference_number,
                    $t->reason,
                    $t->user->name ?? '',
                ]);
            }
            fclose($out);
        }, "assignment_movements_{$from}_to_{$to}.csv", ['Content-Type' => 'text/csv']);
    }

    private function range(Request $request): array
    {
        return [
            $request->input('date_from', now()->subDays(30)->toDateString()),
            $request->input('date_to', now()->toDateString()),
        ];
    }

    private function movementQuery(string $from, string $to)
    {
        return AssignmentTransaction::with(['student', 'user'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->recent();
    }
}