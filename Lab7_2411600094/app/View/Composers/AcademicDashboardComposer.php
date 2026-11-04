<?php

namespace App\View\Composers;

use App\Models\Student;
use App\Models\AcademicIntervention;
use Illuminate\View\View;

class AcademicDashboardComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'heavyBacklogCount' => Student::heavyBacklog()->count(),

            'pendingInterventionCount' => AcademicIntervention::pending()->count(),

            'approvedInterventionCount' => AcademicIntervention::approved()->count(),

            'totalInterventionCount' => AcademicIntervention::count(),
        ]);
    }
}
