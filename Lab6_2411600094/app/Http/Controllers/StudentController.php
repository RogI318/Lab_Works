<?php

namespace App\Http\Controllers;

use App\Models\AssignmentTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = $this->filtered($request)
                         ->orderBy('student_id')
                         ->paginate(10)
                         ->withQueryString();

        $programs = Student::PROGRAMS;

        return view('students.index', compact('students', 'programs'));
    }

    public function create()
    {
        return view('students.create');
    }

    //STORE
    public function store(Request $request)
{
    $validated = $request->validate($this->rules(), $this->messages());

    // Pending assignments are now owned by the transaction ledger,
    // so the typed number becomes an "opening balance" transaction.
    $opening = (int) $validated['pending_assignments'];
    $validated['pending_assignments'] = 0;

    $student = DB::transaction(function () use ($validated, $opening, $request) {
        $student = Student::create($validated);

        if ($opening > 0) {
            AssignmentTransaction::create([
                'student_id'       => $student->id,
                'user_id'          => $request->user()->id,
                'type'             => 'assigned',
                'quantity'         => $opening,
                'reference_number' => 'OPENING-' . $student->student_id,
                'notes'            => 'Opening balance when the student record was created.',
            ]);
        }

        return $student;
    });

    $this->log($student, 'Enrollment confirmed for ' . $student->program, 'Enrollment', 'Approved');

    return redirect()
        ->route('students.index')
        ->with('success', 'Student added successfully.');
}
//SHOW
 public function show(Request $request, Student $student)
{
    $activities = $student->activities()->orderByDesc('occurred_at')->take(10)->get();

    // Running balance = cumulative sum, oldest -> newest
    $all = $student->transactions()->with('user')->orderBy('created_at')->orderBy('id')->get();
    $running = 0;
    foreach ($all as $t) {
        $running += $t->quantity;
        $t->running_balance = $running;
    }

    // Optional date range filter (display only), newest first
    $history = $all->filter(function ($t) use ($request) {
        $day = $t->created_at->toDateString();
        if ($request->filled('date_from') && $day < $request->input('date_from')) return false;
        if ($request->filled('date_to')   && $day > $request->input('date_to'))   return false;
        return true;
    })->reverse()->values();

    return view('students.show', compact('student', 'activities', 'history'));
}

    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    //UPDATE
   public function update(Request $request, Student $student)
{
    $validated = $request->validate($this->rules($student->id), $this->messages());

    // Pending assignments can only change through Assignment transactions
    unset($validated['pending_assignments']);

    $oldGpa    = (float) $student->gpa;
    $oldStatus = $student->status;

    $student->update($validated);

    if ((float) $student->gpa !== $oldGpa) {
        $this->log(
            $student,
            'GPA updated from ' . number_format($oldGpa, 2) . ' to ' . number_format((float) $student->gpa, 2),
            'Grades',
            'Completed'
        );
    }
    if ($student->status !== $oldStatus) {
        $this->log(
            $student,
            "Academic standing changed from {$oldStatus} to {$student->status}",
            'Advisory',
            'Pending'
        );
    }

    return redirect()
        ->route('students.index')
        ->with('success', 'Student updated successfully.');
}


    public function destroy(Student $student)
    {
        $student->delete();   // activities are deleted too (cascade)

        return redirect()
            ->route('students.index')
            ->with('success', 'Student deleted successfully.');
    }

    /** Export the (filtered) roster as CSV — same columns as Lab 4. */
    public function export(Request $request)
    {
        $students = $this->filtered($request)->orderBy('student_id')->get();

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student ID', 'Name', 'Program', 'Year', 'Units', 'GPA', 'Attendance', 'Pending Assignments', 'Status']);
            foreach ($students as $s) {
                fputcsv($out, [
                    $s->student_id, $s->name, $s->program, $s->year_level, $s->units,
                    number_format((float) $s->gpa, 2), $s->attendance . '%', $s->pending_assignments, $s->status,
                ]);
            }
            fclose($out);
        }, 'student_roster.csv', ['Content-Type' => 'text/csv']);
    }

    /* ---------------- helpers ---------------- */

    /** Search + program / standing / GPA range filters (Lab 4 filter panel). */
    private function filtered(Request $request)
    {
        $query = Student::query();

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                                       ->orWhere('student_id', 'like', "%{$search}%"));
        }
        if ($program = $request->input('program')) {
            $query->where('program', $program);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($request->filled('min_gpa')) {
            $query->where('gpa', '>=', (float) $request->input('min_gpa'));
        }
        if ($request->filled('max_gpa')) {
            $query->where('gpa', '<=', (float) $request->input('max_gpa'));
        }

        return $query;
    }

    private function log(Student $student, string $text, string $category, string $status): void
    {
        $student->activities()->create([
            'activity'    => $text,
            'category'    => $category,
            'status'      => $status,
            'occurred_at' => now(),
        ]);
    }

    private function rules($ignoreId = null): array
    {
        return [
            'student_id'          => 'required|string|max:50|unique:students,student_id' . ($ignoreId ? ",$ignoreId" : ''),
            'name'                => 'required|string|max:255',
            'program'             => ['required', Rule::in(Student::PROGRAMS)],
            'year_level'          => 'required|integer|min:1|max:5',
            'units'               => 'required|integer|min:0|max:30',
            'gpa'                 => 'required|numeric|min:0|max:4',
            'attendance'          => 'required|integer|min:0|max:100',
            'pending_assignments' => 'required|integer|min:0|max:20',
        ];
    }

    private function messages(): array
    {
        return [
            'student_id.required' => 'Please provide a student ID (e.g., 2026-00101).',
            'student_id.unique'   => 'This student ID already exists.',
            'name.required'       => "Please provide the student's full name.",
            'program.required'    => 'Please select a program.',
            'program.in'          => 'Please choose a valid program.',
            'year_level.min'      => 'Year level must be between 1 and 5.',
            'year_level.max'      => 'Year level must be between 1 and 5.',
            'units.max'           => 'Units cannot exceed 30.',
            'gpa.min'             => 'GPA cannot be negative.',
            'gpa.max'             => 'GPA cannot exceed 4.00.',
            'attendance.min'      => 'Attendance must be between 0 and 100.',
            'attendance.max'      => 'Attendance must be between 0 and 100.',
            'pending_assignments.max' => 'Pending assignments cannot exceed 20.',
        ];
    }
}