<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\SupplierPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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

        /*
         * Lab 7 Part 1 Step 5 — authorization, the two halves:
         *
         * GATES: single yes/no questions that no particular row owns ("may this
         * user open the procurement module at all?"). They back the @can /
         * @cannot checks in the sidebar and the buttons.
         *
         * POLICIES: the same question about one Eloquent model, with the record
         * in hand (SupplierPolicy below), used by Gate::authorize() in the
         * controllers.
         */
        Gate::define('view-procurement', fn (User $user) => $user->hasRole(
            User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_WAREHOUSE
        ));

        Gate::define('manage-suppliers', fn (User $user) => $user->hasRole(
            User::ROLE_ADMIN, User::ROLE_MANAGER
        ));

        Gate::define('view-supplier-reports', fn (User $user) => $user->hasRole(
            User::ROLE_ADMIN, User::ROLE_MANAGER
        ));

        // Auto-discovery would find these on their own; naming them keeps the
        // mapping obvious when the sheet asks "where is the policy registered?".
        Gate::policy(Supplier::class, SupplierPolicy::class);

        // Lab 7 Part 2 Step 5: the purchase order rules — see the table at the
        // top of PurchaseOrderPolicy for who may do what in which state.
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
    }
}
