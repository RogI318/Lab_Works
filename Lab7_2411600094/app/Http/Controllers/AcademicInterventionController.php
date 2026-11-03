<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\AcademicIntervention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AcademicInterventionController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', AcademicIntervention::class);

        $interventions = AcademicIntervention::with([
            'student',
            'creator',
            'approver'
        ])
        ->when($request->status, function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($request->priority, function ($query, $priority) {
            $query->where('priority', $priority);
        })
        ->latest()
        ->paginate(10)
        ->withQueryString();

        return view(
            'interventions.index',
            compact('interventions')
        );
    }

    public function create()
    {
        Gate::authorize('create', AcademicIntervention::class);

        $students = Student::orderBy('name')->get();

        return view('interventions.create', compact('students'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', AcademicIntervention::class);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'intervention_type' => 'required|string|max:100',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'description' => 'nullable|string',
            'action_plan' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $student = Student::findOrFail($validated['student_id']);

        $validated['created_by'] = auth()->id();
        $validated['pending_assignments'] =
            $student->pending_assignments;

        $validated['reference_number'] =
            AcademicIntervention::generateReferenceNumber();

        $validated['status'] = 'pending';

        AcademicIntervention::create($validated);

        return redirect()
            ->route('interventions.index')
            ->with('success', 'Academic intervention created successfully.');
    }

    public function show(AcademicIntervention $intervention)
    {
        Gate::authorize('view', $intervention);

        $intervention->load([
            'student',
            'creator',
            'approver'
        ]);

        return view(
            'interventions.show',
            compact('intervention')
        );
    }

    public function edit(AcademicIntervention $intervention)
    {
        Gate::authorize('update', $intervention);

        $students = Student::orderBy('name')->get();

        return view(
            'interventions.edit',
            compact('intervention', 'students')
        );
    }

    public function update(
        Request $request,
        AcademicIntervention $intervention
    ) {
        Gate::authorize('update', $intervention);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'intervention_type' => 'required|string|max:100',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'description' => 'nullable|string',
            'action_plan' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $student = Student::findOrFail($validated['student_id']);

        $validated['pending_assignments'] =
            $student->pending_assignments;

        $intervention->update($validated);

        return redirect()
            ->route('interventions.index')
            ->with('success', 'Intervention updated successfully.');
    }

    public function destroy(AcademicIntervention $intervention)
    {
        Gate::authorize('delete', $intervention);

        $intervention->delete();

        return redirect()
            ->route('interventions.index')
            ->with('success', 'Intervention moved to archive.');
    }

    public function approve(AcademicIntervention $intervention)
    {
        Gate::authorize('approve', $intervention);

        DB::transaction(function () use ($intervention) {
            $record = AcademicIntervention::whereKey($intervention->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $record->status === 'pending',
                409,
                'This intervention is no longer pending.'
            );

            $record->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        return back()->with(
            'success',
            'Academic intervention approved successfully.'
        );
    }

    public function reject(
        Request $request,
        AcademicIntervention $intervention
    ) {
        Gate::authorize('reject', $intervention);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($intervention, $validated) {
            $record = AcademicIntervention::whereKey($intervention->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $record->status === 'pending',
                409,
                'This intervention is no longer pending.'
            );

            $record->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
            ]);
        });

        return back()->with(
            'success',
            'Academic intervention rejected.'
        );
    }

    public function complete(AcademicIntervention $intervention)
    {
        Gate::authorize('complete', $intervention);

        $intervention->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with(
            'success',
            'Academic intervention marked as completed.'
        );
    }

    public function restore($id)
    {
        Gate::authorize('admin-only');

        $intervention = AcademicIntervention::withTrashed()
            ->findOrFail($id);

        Gate::authorize('restore', $intervention);

        $intervention->restore();

        return back()->with(
            'success',
            'Academic intervention restored successfully.'
        );
    }
}
