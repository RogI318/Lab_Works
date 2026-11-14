<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lab 7 Part 1 Step 2 (b): product_supplier — the pricing agreements between
 * a product and every supplier that can provide it.
 *
 * products.supplier_id is the PRIMARY supplier (one per product, used by the
 * reorder logic). This pivot adds the alternatives and, because price, order
 * multiples and SKU differ from one vendor to the next, carries the terms per
 * pair: unit_cost is what we pay (vs. the selling price in products.unit_price),
 * minimum_order_quantity and package_size round a suggested quantity up to a
 * whole carton, and is_preferred marks the vendor the store actually uses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();

            // Removing the agreement is fine; removing either side ends it,
            // so these rows cascade with the product / supplier.
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();

            $table->string('supplier_sku')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->unsignedSmallInteger('minimum_order_quantity')->default(1);
            $table->unsignedSmallInteger('package_size')->default(1);
            $table->boolean('is_preferred')->default(false);

            $table->timestamps();

            // One agreement per product/supplier pair: the seeder and any
            // future attach() can rely on this instead of duplicating rows.
            $table->unique(['product_id', 'supplier_id']);
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
