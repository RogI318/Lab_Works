<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lab 7 Part 1 Step 2 (d): users.role — admin / manager / warehouse / staff.
 *
 * Deliberately ADDITIVE: `is_admin` stays the authority for every Lab 5/6
 * check (AdminOnly middleware, bell, restore, syncLowStockAlerts). This
 * migration backfills it once and the User model keeps the two in step
 * afterwards, so is_admin === (role === 'admin') holds from here on.
 */
return new class extends Migration
{
    public const ROLES = ['admin', 'manager', 'warehouse', 'staff'];

    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('staff')->after('is_admin');
            });
        }

        // Backfill: existing admins stay admin, everyone else becomes staff.
        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);
        DB::table('users')->where('is_admin', false)->update(['role' => 'staff']);

        // Guard the invariant in one place — nobody may be locked out of
        // user management (same rule as the Lab 6 is_admin migration).
        if (! DB::table('users')->where('role', 'admin')->exists()) {
            $first = DB::table('users')->orderBy('id')->first();

            if ($first) {
                DB::table('users')->where('id', $first->id)->update([
                    'role'     => 'admin',
                    'is_admin' => true,
                ]);
            }
        }

        if (! Schema::hasIndex('users', 'users_role_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_role_index');
                $table->dropColumn('role');
            });
        }
    }
};
