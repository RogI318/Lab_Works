<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// LAB 6 (idea 1): soft delete. A deleted product goes to the Trash and can be restored.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'deleted_at')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'deleted_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
