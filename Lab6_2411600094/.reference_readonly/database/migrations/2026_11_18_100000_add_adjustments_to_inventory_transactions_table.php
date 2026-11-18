<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// LAB 6 on top of LAB 5: stock adjustments need a third type, a signed quantity,
// a reason and notes. Existing Lab 5 rows are not changed.
// Safe to run on a database built by an earlier merge too (it only adds what is missing).
return new class extends Migration
{
    public function up(): void
    {
        // An earlier build used the name "reference_number"; Lab 5 calls it "reference".
        if (Schema::hasColumn('inventory_transactions', 'reference_number')
            && ! Schema::hasColumn('inventory_transactions', 'reference')) {
            Schema::table('inventory_transactions', function (Blueprint $table) {
                $table->renameColumn('reference_number', 'reference');
            });
        }

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->enum('type', ['in', 'out', 'adjustment'])->change();
            $table->integer('quantity')->change();
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_transactions', 'reference')) {
                $table->string('reference')->nullable();
            }
            if (! Schema::hasColumn('inventory_transactions', 'reason')) {
                $table->string('reason')->nullable();
            }
            if (! Schema::hasColumn('inventory_transactions', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn(['reason', 'notes']);
        });
    }
};
