<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lab 6 Part 1, "Important Considerations": deleting a product must not
 * erase its movements. Products now soft-delete, so the ledger and the
 * reports stay reproducible; inventory_transactions.product_id keeps its
 * cascade constraint only for the admin "delete permanently" purge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
