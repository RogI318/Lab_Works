<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// LAB 6 (idea 4): every transaction remembers the stock before and after it.
// Older rows stay empty (shown as "-"); no data is lost.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_transactions', 'quantity_before')) {
                $table->integer('quantity_before')->nullable();
            }
            if (! Schema::hasColumn('inventory_transactions', 'quantity_after')) {
                $table->integer('quantity_after')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn(['quantity_before', 'quantity_after']);
        });
    }
};
