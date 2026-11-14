<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lab 7 Part 2 Step 1: the purchase order header.
 *
 * One row = one order raised against one supplier. Money is stored rather than
 * derived so a later price change can never rewrite history, and every state
 * transition keeps its own timestamp and actor, which is what the approval
 * history and the Part 6 supplier report read.
 *
 * Additive migration only: lab5_db is shared with Lab 5 and Lab 6.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_orders')) {
            return;
        }

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            // PO-YYYYMMDD-0001. Unique across the whole table, soft-deleted
            // rows included: an order number is an audit reference, so it is
            // never recycled even when the order itself is retired.
            $table->string('reference_number')->unique();

            // restrictOnDelete: an order is a commitment to a supplier and
            // stays reachable even after the supplier is retired (the
            // relation is queried with withTrashed()).
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();

            // draft | pending | approved | sent | partially_received
            // | received | rejected | cancelled
            $table->string('status')->default('draft');

            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();

            // One timestamp per transition, so "how long did approval take" and
            // "was it delivered on time" are answerable without the log table.
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('rejection_reason')->nullable();

            // Part 3: rows written by the reorder engine are flagged, so a
            // human can tell an auto-generated order from a manual one.
            $table->boolean('is_auto_generated')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('order_date');
            $table->index(['supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
