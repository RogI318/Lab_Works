<?php

namespace App\Http\Controllers;

use App\Models\AcademicIntervention;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InterventionPdfController extends Controller
{
    /**
     * PDF export — uses the same search/filters as the Interventions page.
     */
    public function __invoke(Request $request)
    {
        Gate::authorize('viewAny', AcademicIntervention::class);

        $query = AcademicIntervention::query();

        if ($request->input('archived') === 'only') {
            $query->onlyTrashed();
        }

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

        $interventions = $query
            ->with(['student', 'creator', 'approver'])
            ->latest()
            ->get();

        return Pdf::loadView('interventions.pdf', [
            'title' => 'Academic Interventions Report',
            'interventions' => $interventions,
        ])
            ->setPaper('a4', 'landscape')
            ->download('academic_interventions_' . now()->format('Ymd_His') . '.pdf');
    }
}