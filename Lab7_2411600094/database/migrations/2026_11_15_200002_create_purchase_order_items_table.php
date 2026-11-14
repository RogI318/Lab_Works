<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lab 7 Part 2 Step 2: the purchase order lines.
 *
 * Critical Thinking (docx): why store unit_price at order time instead of
 * reading products.unit_price when we need it?
 *   A purchase order is a price commitment, not a live lookup. The number on
 *   the order is what the supplier invoiced on that day; products.unit_price is
 *   what RogieMart charges today and moves whenever a cost change or promotion
 *   is entered. Reading the current column would silently rewrite every past
 *   order — the subtotal, the 12 % tax and the Part 6 spend and price-variance
 *   reports would all change after the fact, and a partially received order
 *   would owe a different amount than the one that was approved. Freezing the
 *   price per line keeps history reproducible and lets the reports compare the
 *   price we ordered at against the price we sell at now.
 *
 * Additive migration only: lab5_db is shared with Lab 5 and Lab 6.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_order_items')) {
            return;
        }

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            // An order's lines belong to it: deleting the order deletes them.
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();

            // restrictOnDelete: a line is evidence of a purchase. A product
            // that has ever been ordered stays in the catalogue (soft delete
            // still works, permanent erasure is blocked by this key).
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->unsignedInteger('quantity');

            // Price frozen at the moment the order was raised — see the
            // Critical Thinking note above this migration.
            $table->decimal('unit_price', 10, 2);

            // Grows as the warehouse receives against the order; the
            // difference to `quantity` is what is still outstanding.
            $table->unsignedInteger('received_quantity')->default(0);

            $table->timestamps();

            // One line per product per order: the form rejects duplicates, and
            // the database makes that true even under a double submit.
            $table->unique(['purchase_order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
