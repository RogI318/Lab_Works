<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lab 6 Part 8 Step 2: the adjustment form records WHY the physical count
 * differs from the system count. The reason belongs on the ledger row itself
 * so Part 9 reports can group adjustments by cause.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->string('reason', 60)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
