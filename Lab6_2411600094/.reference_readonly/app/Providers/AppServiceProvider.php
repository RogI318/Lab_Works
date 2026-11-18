<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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

        // Gate "admin": used for deleting transactions and the admin-only parts
        // (based on the Lab 5 "role" column)
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Named permissions, used by the routes (middleware 'can:...')
        // manage-stock = admin AND staff (inventory, brands, categories, stock in/out, adjustments)
        Gate::define('manage-stock', fn (User $user) => $user->canManageStock());

        // manage-users = ADMIN ONLY (User Management)
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());

        // LAB 6: audit trail for sign-ins
        Event::listen(Login::class, fn (Login $e) => AuditLog::record('login', $e->user, null, null, $e->user->email, $e->user->id, $e->user->name));
        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user) {
                AuditLog::record('logout', $e->user, null, null, $e->user->email, $e->user->id, $e->user->name);
            }
        });
        Event::listen(Failed::class, fn (Failed $e) => AuditLog::record('login_failed', null, null, null, (string) ($e->credentials['email'] ?? 'unknown'), null, 'Not signed in'));
    }
}
