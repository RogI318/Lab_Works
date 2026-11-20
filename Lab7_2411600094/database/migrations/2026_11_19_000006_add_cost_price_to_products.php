<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'cost_price'))
            Schema::table('products', fn(Blueprint $t) => $t->decimal('cost_price', 12, 2)->nullable());
    }
    public function down(): void
    {
        if (Schema::hasColumn('products', 'cost_price'))
            Schema::table('products', fn(Blueprint $t) => $t->dropColumn('cost_price'));
    }
};
