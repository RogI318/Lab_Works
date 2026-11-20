<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'payment_terms')) {
                $table->string('payment_terms')->nullable();
            }

            if (! Schema::hasColumn('suppliers', 'minimum_order_amount')) {
                $table->decimal('minimum_order_amount', 12, 2)->default(0);
            }

            if (! Schema::hasColumn('suppliers', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'package_size')) {
                $table->unsignedInteger('package_size')->default(1);
            }

            if (! Schema::hasColumn('products', 'min_order_quantity')) {
                $table->unsignedInteger('min_order_quantity')->default(1);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'min_order_quantity')) {
                $table->dropColumn('min_order_quantity');
            }
            if (Schema::hasColumn('products', 'package_size')) {
                $table->dropColumn('package_size');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('suppliers', 'minimum_order_amount')) {
                $table->dropColumn('minimum_order_amount');
            }
            if (Schema::hasColumn('suppliers', 'payment_terms')) {
                $table->dropColumn('payment_terms');
            }
        });
    }
};
