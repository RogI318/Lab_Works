<?php

namespace App\Providers;

use App\Models\AcademicIntervention;
use App\Models\Student;
use App\Policies\AcademicInterventionPolicy;
use App\Support\AcademicStats;
use App\View\Composers\AcademicDashboardComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Register the AcademicIntervention policy
        Gate::policy(
            AcademicIntervention::class,
            AcademicInterventionPolicy::class
        );

        // General Administrator Permission
        Gate::define('admin-only', function ($user) {
            return (bool) $user->is_admin;
        });

        View::composer('dashboard', AcademicDashboardComposer::class);

        // Clear cached dashboard statistics whenever the data changes
        AcademicIntervention::saved(fn () => AcademicStats::forget());
        AcademicIntervention::deleted(fn () => AcademicStats::forget());
        AcademicIntervention::restored(fn () => AcademicStats::forget());
        Student::saved(fn () => AcademicStats::forget());
        Student::deleted(fn () => AcademicStats::forget());
    }
}