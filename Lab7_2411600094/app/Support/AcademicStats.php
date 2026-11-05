<?php

namespace App\Support;

use App\Models\AcademicIntervention;
use App\Models\Student;
use Illuminate\Support\Facades\Cache;

class AcademicStats
{
    public const CACHE_KEY = 'academic.dashboard.stats';

    /**
     * Dashboard statistics, cached for 60 seconds.
     */
    public static function get(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addSeconds(60), function () {
            return [
                'heavy_backlog_count' => Student::heavyBacklog()->count(),
                'pending_intervention_count' => AcademicIntervention::pending()->count(),
                'approved_intervention_count' => AcademicIntervention::approved()->count(),
                'total_intervention_count' => AcademicIntervention::count(),
                'last_updated' => now()->toDateTimeString(),
            ];
        });
    }

    /**
     * Clear the cached statistics so the next request recalculates them.
     */
    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}