<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Policies\PurchaseOrderPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;



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
        Gate::define('admin', fn($user) => $user->isAdmin());
        Gate::define('manage-users', fn($user) => $user->isAdmin());
        Gate::define('manage-stock', fn($user) => $user->canManageStock());
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
    }
}
