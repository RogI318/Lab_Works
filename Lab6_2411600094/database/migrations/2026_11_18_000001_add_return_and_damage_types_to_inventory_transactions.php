<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Two extra movement types taken from the other Lab 6 project:
     * 'return' (stock comes back in) and 'damage' (stock goes out).
     * Existing rows are untouched; only the allowed values grow.
     */
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->enum('type', ['in', 'out', 'adjustment', 'return', 'damage'])->change();
        });
    }

    public function down(): void
    {
        // A return moves stock exactly like Stock In and a damage like Stock Out,
        // so they are folded back into those two before the list is narrowed.
        DB::table('inventory_transactions')->where('type', 'return')->update(['type' => 'in']);
        DB::table('inventory_transactions')->where('type', 'damage')->update(['type' => 'out']);

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->enum('type', ['in', 'out', 'adjustment'])->change();
        });
    }
};
