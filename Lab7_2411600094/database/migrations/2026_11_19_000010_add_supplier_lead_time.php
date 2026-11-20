<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('suppliers', 'lead_time_days')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->unsignedSmallInteger('lead_time_days')->default(0)->after('phone');
            });
        }

        // Directory demo defaults from the user's example; replace these with
        // verified supplier contacts when they become available.
        DB::table('suppliers')->whereNull('contact_person')->update(['contact_person' => 'Sales Representative']);
        DB::table('suppliers')->whereNull('phone')->update(['phone' => '0917-555-0100']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('suppliers', 'lead_time_days')) {
            Schema::table('suppliers', fn (Blueprint $table) => $table->dropColumn('lead_time_days'));
        }
    }
};
