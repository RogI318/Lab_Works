<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_number')->unique();
            $t->enum('direction', ['purchase', 'sale']);
            $t->string('contact_name');
            $t->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $t->text('notes')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps(); });
        Schema::create('inventory_order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inventory_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 12, 2)->default(0);
            $t->timestamps(); }); }
    public function down(): void
    {
        Schema::dropIfExists('inventory_order_items');
        Schema::dropIfExists('inventory_orders'); }
};
