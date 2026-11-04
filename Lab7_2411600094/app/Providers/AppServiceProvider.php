<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\View\Composers\AcademicDashboardComposer;
use Illuminate\Support\Facades\View;

use App\Models\AcademicIntervention;
use App\Policies\AcademicInterventionPolicy;


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

        //General Administrator Permission
        Gate::define('admin-only', function ($user) {
            return (bool) $user->is_admin;
        });

        View::composer('dashboard', AcademicDashboardComposer::class);
    }
}
