<?php

namespace App\Http\Controllers;

use App\Models\AssignmentTransaction;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class AssignmentTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = AssignmentTransaction::with(['student', 'user'])->recent();

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $transactions = $query->paginate(20)->withQueryString();
        $students     = Student::orderBy('name')->get(['id', 'student_id', 'name']);
        $types        = AssignmentTransaction::TYPES;

        return view('assignments.index', compact('transactions', 'students', 'types'));
    }

    public function create()
    {
        $students = Student::orderBy('name')->get(['id', 'student_id', 'name', 'pending_assignments']);
        // "adjustment" has its own form
        $types = collect(AssignmentTransaction::TYPES)->except('adjustment')->all();

        return view('assignments.create', compact('students', 'types'));
    }

    public function store(Request $request)
    {
        $validator = validator($request->all(), [
            'student_id'       => 'required|exists:students,id',
            'type'             => 'required|in:assigned,submitted',
            'quantity'         => 'required|integer|min:1|max:' . Student::MAX_PENDING,
            'reference_number' => 'nullable|string|max:50',
            'notes'            => 'nullable|string|max:500',
        ], [
            'student_id.required' => 'Please select a student.',
            'student_id.exists'   => 'That student does not exist.',
            'type.required'       => 'Please select a transaction type.',
            'quantity.min'        => 'Quantity must be at least 1.',
        ]);

        // Custom check: not enough pending to submit / too many to assign
        $validator->after(function (Validator $v) use ($request) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $student = Student::find($request->input('student_id'));
            $qty     = (int) $request->input('quantity');

            if ($request->input('type') === 'submitted' && $qty > $student->pending_assignments) {
                $v->errors()->add('quantity',
                    "{$student->name} only has {$student->pending_assignments} pending assignment(s); cannot submit {$qty}.");
            }
            if ($request->input('type') === 'assigned'
                && $student->pending_assignments + $qty > Student::MAX_PENDING) {
                $v->errors()->add('quantity',
                    'Pending assignments cannot exceed ' . Student::MAX_PENDING . ' per student.');
            }
        });

        $data = $validator->validate();

        // All-or-nothing: the observer updates the student inside this transaction
        DB::transaction(function () use ($data, $request) {
            AssignmentTransaction::create([
                'student_id'       => $data['student_id'],
                'user_id'          => $request->user()->id,
                'type'             => $data['type'],
                'quantity'         => $data['type'] === 'submitted' ? -$data['quantity'] : $data['quantity'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Transaction recorded. The student record was updated automatically.');
    }

    public function show(AssignmentTransaction $assignment)
    {
        $assignment->load(['student', 'user']);

        return view('assignments.show', ['transaction' => $assignment]);
    }

    /**
     * Admin only. Deleting REVERSES the effect on the student's pending count,
     * but only if the result stays >= 0.
     */
    public function destroy(Request $request, AssignmentTransaction $assignment)
    {
        abort_unless($request->user()->is_admin, 403, 'Only administrators can delete transactions.');

        try {
            DB::transaction(function () use ($assignment) {
                $student = Student::lockForUpdate()->findOrFail($assignment->student_id);
                $newCount = $student->pending_assignments - $assignment->quantity;

                if ($newCount < 0) {
                    throw new \RuntimeException(
                        'Cannot delete: reversing this transaction would make the pending count negative.'
                    );
                }

                $student->pending_assignments = $newCount;
                $student->save();
                $assignment->delete();
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Transaction deleted and the student record was reversed.');
    }

    /* ---------------- Backlog adjustment (like stock adjustment) ---------------- */

    public function adjust()
    {
        $students = Student::orderBy('name')->get(['id', 'student_id', 'name', 'pending_assignments']);
        $reasons  = AssignmentTransaction::ADJUSTMENT_REASONS;

        return view('assignments.adjust', compact('students', 'reasons'));
    }

    public function storeAdjustment(Request $request)
    {
        $data = $request->validate([
            'student_id'     => 'required|exists:students,id',
            'physical_count' => 'required|integer|min:0|max:' . Student::MAX_PENDING,
            'reason'         => ['required', Rule::in(AssignmentTransaction::ADJUSTMENT_REASONS)],
            'notes'          => 'nullable|string|max:500',
        ], [
            'physical_count.required' => 'Please enter the actual pending count.',
            'reason.required'         => 'Please select a reason for the adjustment.',
        ]);

        $difference = DB::transaction(function () use ($data, $request) {
            $student = Student::lockForUpdate()->findOrFail($data['student_id']);
            $diff    = (int) $data['physical_count'] - $student->pending_assignments;

            if ($diff === 0) {
                return null;
            }

            AssignmentTransaction::create([
                'student_id'       => $student->id,
                'user_id'          => $request->user()->id,
                'type'             => 'adjustment',
                'quantity'         => $diff,
                'reference_number' => 'ADJ-' . now()->format('Ymd-His'),
                'reason'           => $data['reason'],
                'notes'            => $data['notes'] ?? null,
            ]);

            return $diff;
        });

        if ($difference === null) {
            return back()->withInput()
                ->with('error', 'The count you entered already matches the system count. No adjustment needed.');
        }

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Adjustment recorded (' . ($difference > 0 ? '+' : '') . $difference . ').');
    }
}