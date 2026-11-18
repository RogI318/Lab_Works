<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn(Blueprint $table) => $table->string('role', 20)->default('staff'));
        }
        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);
        if (!Schema::hasColumn('products', 'is_active')) {
            Schema::table('products', fn(Blueprint $table) => $table->boolean('is_active')->default(true));
        }
        if (!Schema::hasColumn('users', 'pending_email'))
            Schema::table('users', fn(Blueprint $t) => $t->string('pending_email')->nullable());
        if (!Schema::hasColumn('users', 'pending_password'))
            Schema::table('users', fn(Blueprint $t) => $t->string('pending_password')->nullable());
        if (!Schema::hasColumn('users', 'pending_changes_at'))
            Schema::table('users', fn(Blueprint $t) => $t->timestamp('pending_changes_at')->nullable());
        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_transactions', 'quantity_before'))
                $table->integer('quantity_before')->nullable();
            if (!Schema::hasColumn('inventory_transactions', 'quantity_after'))
                $table->integer('quantity_after')->nullable();
        });
        foreach (DB::table('products')->get(['id', 'quantity']) as $product) {
            $rows = DB::table('inventory_transactions')->where('product_id', $product->id)->orderBy('created_at')->orderBy('id')->get();
            $balance = (int) $product->quantity - $rows->sum(function ($row) {
                return match ($row->type) {
                    'in', 'return' => abs((int) $row->quantity),
                    'out', 'damage' => -abs((int) $row->quantity),
                    default => (int) $row->quantity,
                };
            });
            foreach ($rows as $row) {
                $before = $balance;
                $balance += match ($row->type) {
                    'in', 'return' => abs((int) $row->quantity),
                    'out', 'damage' => -abs((int) $row->quantity),
                    default => (int) $row->quantity,
                };
                DB::table('inventory_transactions')->where('id', $row->id)->update(['quantity_before' => $before, 'quantity_after' => $balance]);
            }
        }
    }
    public function down(): void
    {
    }
};
