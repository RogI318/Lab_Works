<?php

namespace App\Http\Controllers;

use App\Mail\AcademicInterventionApproved;
use App\Mail\AcademicInterventionCreated;
use App\Models\AcademicIntervention;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class AcademicInterventionController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', AcademicIntervention::class);

        $interventions = $this->filtered($request)
            ->with(['student', 'creator', 'approver'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('interventions.index', compact('interventions'));
    }

    /**
     * CSV export — uses the same search/filters as the index page.
     */
    public function export(Request $request)
    {
        Gate::authorize('viewAny', AcademicIntervention::class);

        $interventions = $this->filtered($request)
            ->with(['student', 'creator', 'approver'])
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($interventions) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Reference', 'Student ID', 'Student', 'Type', 'Priority',
                'Status', 'Pending Assignments', 'Created By', 'Approved By',
                'Created At',
            ]);

            foreach ($interventions as $i) {
                fputcsv($out, [
                    $i->reference_number,
                    $i->student->student_id ?? '',
                    $i->student->name ?? 'Student unavailable',
                    $i->intervention_type,
                    $i->priority,
                    ucfirst(str_replace('_', ' ', $i->status)),
                    $i->pending_assignments,
                    $i->creator->name ?? '',
                    $i->approver->name ?? '',
                    $i->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, 'academic_interventions_' . now()->format('Ymd_His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
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
        $validated['pending_assignments'] = $student->pending_assignments;
        $validated['reference_number'] = AcademicIntervention::generateReferenceNumber();
        $validated['status'] = 'pending';

        $intervention = AcademicIntervention::create($validated);
        $intervention->load('student');

        // Notify administrators (this code used to sit AFTER the return, so it never ran)
        $admins = User::where('is_admin', true)
            ->whereNotNull('email')
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)
                ->send(new AcademicInterventionCreated($intervention));
        }

        return redirect()
            ->route('interventions.index')
            ->with('success', 'Academic intervention created successfully.');
    }

    public function show(AcademicIntervention $intervention)
    {
        Gate::authorize('view', $intervention);

        $intervention->load(['student', 'creator', 'approver']);

        return view('interventions.show', compact('intervention'));
    }

    public function edit(AcademicIntervention $intervention)
    {
        Gate::authorize('update', $intervention);

        $students = Student::orderBy('name')->get();

        return view('interventions.edit', compact('intervention', 'students'));
    }

    public function update(Request $request, AcademicIntervention $intervention)
    {
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

        $validated['pending_assignments'] = $student->pending_assignments;

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

        // Notify administrators (this code used to sit AFTER the return, so it never ran)
        $intervention->refresh()->load('student');

        $admins = User::where('is_admin', true)
            ->whereNotNull('email')
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)
                ->send(new AcademicInterventionApproved($intervention));
        }

        return back()->with('success', 'Academic intervention approved successfully.');
    }

    public function reject(Request $request, AcademicIntervention $intervention)
    {
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

        return back()->with('success', 'Academic intervention rejected.');
    }

    public function complete(AcademicIntervention $intervention)
    {
        Gate::authorize('complete', $intervention);

        $intervention->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Academic intervention marked as completed.');
    }

    public function restore($id)
    {
        Gate::authorize('admin-only');

        $intervention = AcademicIntervention::withTrashed()->findOrFail($id);

        Gate::authorize('restore', $intervention);

        $intervention->restore();

        return back()->with('success', 'Academic intervention restored successfully.');
    }

    /* ---------------- helpers ---------------- */

    /**
     * Shared by index() and export():
     * keyword search, status, priority, date range, and archived (soft-deleted) records.
     */
    private function filtered(Request $request)
    {
        $query = AcademicIntervention::query();

        // Archived view (Soft Deletes)
        if ($request->input('archived') === 'only') {
            $query->onlyTrashed();
        }

        // Keyword search: reference number, type, or student name / student ID
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('intervention_type', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($s) use ($search) {
                        $s->where('name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }
}