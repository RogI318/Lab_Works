<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('brand_id')->constrained()->nullOnDelete();
        });

        // Preserve existing free-text supplier data while migrating to records.
        $names = DB::table('products')->whereNotNull('supplier')->where('supplier', '<>', '')
            ->distinct()->pluck('supplier');
        foreach ($names as $name) {
            $id = DB::table('suppliers')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('products')->where('supplier', $name)->update(['supplier_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropConstrainedForeignId('supplier_id'));
        Schema::dropIfExists('suppliers');
    }
};
