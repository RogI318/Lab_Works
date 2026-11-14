<?php

namespace App\View\Composers;

use App\Support\AcademicStats;
use Illuminate\View\View;

class AcademicDashboardComposer
{
    public function compose(View $view): void
    {
        $stats = AcademicStats::get();

        $view->with([
            'heavyBacklogCount' => $stats['heavy_backlog_count'],
            'pendingInterventionCount' => $stats['pending_intervention_count'],
            'approvedInterventionCount' => $stats['approved_intervention_count'],
            'totalInterventionCount' => $stats['total_intervention_count'],
        ]);
    }
}