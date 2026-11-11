<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab 6 prerequisite + Lab 5 (latest) user management: the admin flag
     * gates who may manage accounts, delete movements and receive low-stock
     * alerts.
     */
    public function up(): void
    {
        // The column may already exist from the other lab on this database,
        // so only add it when missing.
        if (! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false)->after('email');
            });
        }

        DB::table('users')->where('email', 'reyzlyn588@gmail.com')->update(['is_admin' => true]);

        // Nobody may be locked out of user management: if no admin exists,
        // the oldest account becomes one.
        if (! DB::table('users')->where('is_admin', true)->exists()) {
            $first = DB::table('users')->orderBy('id')->first();
            if ($first) {
                DB::table('users')->where('id', $first->id)->update(['is_admin' => true]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }
    }
};
