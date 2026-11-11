<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Some databases already have this column from an earlier lab, so only add it if missing.
        if (! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false)->after('password');
            });
        }

        // Make sure nobody is locked out after migrating: the demo admin becomes an
        // admin, and if no admin exists at all, the oldest user becomes one instead.
        DB::table('users')->where('email', 'admin@example.com')->update(['is_admin' => true]);

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