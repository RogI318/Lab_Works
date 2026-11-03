<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentTransactionResource;
use App\Http\Resources\StudentResource;
use App\Models\AssignmentTransaction;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealtimeController extends Controller
{
    /** GET /api/dashboard/stats */
    public function stats(Request $request): JsonResponse
    {
        return response()->json([
            'avg_gpa'               => round((float) Student::avg('gpa'), 2),
            'courses'               => Course::count(),
            'pending_assignments'   => (int) Student::sum('pending_assignments'),
            'avg_attendance'        => (int) round((float) Student::avg('attendance')),
            'total_students'        => Student::count(),
            'at_risk'               => Student::where('status', 'At Risk')->count(),
            'probation'             => Student::where('status', 'Probation')->count(),
            'heavy_backlog'         => Student::heavyBacklog()->count(),
            'clear_backlog'         => Student::clearBacklog()->count(),
            'transactions_today'    => AssignmentTransaction::whereDate('created_at', today())->count(),
            'unread_notifications'  => $request->user()->unreadNotifications()->count(),
            'last_updated'          => now()->format('M d, Y h:i:s A'),
        ]);
    }

    /** GET /api/students/high-backlog */
    public function highBacklog()
    {
        $students = Student::heavyBacklog()
            ->orderByDesc('pending_assignments')
            ->take(10)
            ->get();

        return StudentResource::collection($students);
    }

    /** GET /api/transactions/recent */
    public function recentTransactions()
    {
        $transactions = AssignmentTransaction::with(['student', 'user'])
            ->recent()
            ->take(8)
            ->get();

        return AssignmentTransactionResource::collection($transactions);
    }

    /** GET /api/students/{student}/backlog  (used by the forms) */
    public function studentBacklog(Student $student): JsonResponse
    {
        return response()->json([
            'id'                  => $student->id,
            'student_id'          => $student->student_id,
            'name'                => $student->name,
            'pending_assignments' => $student->pending_assignments,
            'limit'               => Student::BACKLOG_LIMIT,
            'max_pending'         => Student::MAX_PENDING,
            'status'              => $student->backlog_status,
        ]);
    }
}