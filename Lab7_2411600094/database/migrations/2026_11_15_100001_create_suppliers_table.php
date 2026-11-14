<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Lab 7 Part 1 Step 2 — ANALYSIS TASK: why every suppliers column exists
|--------------------------------------------------------------------------
| REQUIRED (a supplier cannot be paid or contacted without these):
|   name            — the trading name printed on every purchase order; also
|                     how staff recognise the company, so it is indexed.
|   contact_person  — POs must be addressed to a real buyer/receiver; calling
|                     a company gets you a switchboard, calling a person gets
|                     you the goods.
|   email           — where the generated purchase order is actually sent, so
|                     it is UNIQUE across the whole table (soft-deleted rows
|                     included: a restored supplier must not collide).
|   phone           — the only channel that works when a delivery is late.
|   address         — ship-to/bill-to address printed on the PO.
|
| OPTIONAL / DEFAULTED (safe values let the form stay short and the ordering
| logic still work for a supplier whose detail you have not captured yet):
|   payment_terms        default 'Net 30' — the hardware-trade norm; drives
|                        the due date on a PO. Restricted by the controller to
|                        COD, Net 15, Net 30, Net 45, Net 60.
|   lead_time_days       default 7 — how long the goods take to arrive. This
|                        is what Part 3's reorder logic uses to decide HOW FAR
|                        AHEAD to order, so it must always hold a number.
|   minimum_order_amount default 0 — the supplier's "no PO below this" floor.
|                        0 means "no floor" rather than an unknown value, so a
|                        new supplier never blocks an emergency reorder.
|   is_active            default true — a newly added supplier is usable at
|                        once; switching it to false retires it without
|                        deleting history (inactive suppliers disappear from
|                        the product dropdown but keep their orders).
|   notes                nullable — free text for everything the columns do
|                        not cover (holiday schedule, truck size, etc.).
|
| deleted_at (SoftDeletes) — a supplier is a business partner with purchase
| orders behind it. Deleting must never erase that history, so the row is only
| flagged; admins can restore it later. Mirrors products.deleted_at from Lab 6.
|--------------------------------------------------------------------------
*/

return new class extends Migration
{
    /** Lab 7 Part 1 Step 1 + Step 2: the supplier master file. */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            // Required identity + contact block
            $table->string('name');
            $table->string('contact_person');
            $table->string('email')->unique();
            $table->string('phone');
            $table->text('address');

            // Commercial terms (each has a safe default)
            $table->string('payment_terms')->default('Net 30');
            $table->unsignedSmallInteger('lead_time_days')->default(7);
            $table->decimal('minimum_order_amount', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Lookups the screens actually run: A-Z lists, "active only"
            // dropdowns and "who is slower than N days" reorder filtering.
            $table->index('name');
            $table->index('is_active');
            $table->index('lead_time_days');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
