<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_supplier')) {
            Schema::create('product_supplier', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->decimal('supplier_price', 10, 2)->nullable();
                $table->string('supplier_sku', 50)->nullable();
                $table->boolean('is_preferred')->default(false);
                $table->timestamps();
                $table->unique(['product_id', 'supplier_id']);
            });

            DB::table('products')
                ->whereNotNull('supplier_id')
                ->orderBy('id')
                ->get(['id', 'supplier_id', 'cost_price'])
                ->each(function ($product): void {
                    DB::table('product_supplier')->insertOrIgnore([
                        'product_id' => $product->id,
                        'supplier_id' => $product->supplier_id,
                        'supplier_price' => $product->cost_price,
                        'is_preferred' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }

        if (! Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->string('reference_number')->unique();
                $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
                $table->string('status', 20)->default('draft');
                $table->date('order_date');
                $table->date('expected_delivery_date')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->text('terms')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->boolean('is_auto_generated')->default(false);
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['status', 'order_date']);
            });
        }

        if (! Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 10, 2);
                $table->unsignedInteger('received_quantity')->default(0);
                $table->timestamps();
                $table->unique(['purchase_order_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('purchase_order_logs')) {
            Schema::create('purchase_order_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 30);
                $table->string('from_status', 20)->nullable();
                $table->string('to_status', 20)->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notes')) {
            Schema::create('notes', function (Blueprint $table) {
                $table->id();
                $table->morphs('noteable');
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->text('body');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sales_orders')) {
            Schema::create('sales_orders', function (Blueprint $table) {
                $table->id();
                $table->string('reference_number')->unique();
                $table->string('customer_name');
                $table->string('customer_email')->nullable();
                $table->string('customer_phone', 30)->nullable();
                $table->string('status', 20)->default('draft');
                $table->date('order_date');
                $table->decimal('total', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('sales_order_items')) {
            Schema::create('sales_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 10, 2);
                $table->timestamps();
                $table->unique(['sales_order_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('purchase_order_logs');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('product_supplier');
    }
};
