<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('inventory_orders', 'supplier_id')) {
            Schema::table('inventory_orders', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->after('contact_name')->constrained()->nullOnDelete();
            });
        }

        if (DB::table('suppliers')->exists()) {
            DB::table('inventory_orders')->where('direction', 'purchase')->whereNull('supplier_id')->orderBy('id')->get()->each(function ($order) {
                $supplierId = DB::table('suppliers')->where('name', $order->contact_name)->value('id');
                if ($supplierId) DB::table('inventory_orders')->where('id', $order->id)->update(['supplier_id' => $supplierId]);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_orders', 'supplier_id')) {
            Schema::table('inventory_orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('supplier_id'));
        }
    }
};
