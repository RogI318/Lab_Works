<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lab 7 Part 1 Step 2 (a): products.supplier_id — each product's PRIMARY
 * supplier. Additive and nullable, so every Lab 5/6 row and screen keeps
 * working; the legacy free-text `products.supplier` column is left in place
 * (Reports still group by it) but is now maintained from this relationship.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function (Blueprint $table) {
                // Deleting a supplier must not delete the product; the product
                // simply goes back to "no supplier on file".
                $table->foreignId('supplier_id')
                      ->nullable()
                      ->after('brand_id')
                      ->constrained('suppliers')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('supplier_id');
            });
        }
    }
};
