<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab 6 Part 1: extend the Lab 5 ledger without touching existing rows.
     */
    public function up(): void
    {
        // CHANGE COLUMN works on both MySQL and MariaDB, so the Lab 5
        // "reference" column becomes the docx's "reference_number" in place.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_transactions CHANGE `reference` `reference_number` VARCHAR(255) NULL");
        } else {
            // SQLite (used by the test suite) has no CHANGE COLUMN.
            Schema::table('inventory_transactions', function (Blueprint $table) {
                $table->renameColumn('reference', 'reference_number');
            });
        }

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('reference_number');

            // Signed now: stock in/out rows keep their positive magnitude
            // (direction lives in `type`), but adjustment rows store the
            // signed difference (negative for shrinkage, positive for finds).
            $table->integer('quantity')->change();

            $table->enum('type', ['in', 'out', 'adjustment'])->change();

            $table->index(['product_id', 'created_at'], 'txn_product_created_idx');
            $table->index(['type', 'created_at'], 'txn_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex('txn_product_created_idx');
            $table->dropIndex('txn_type_created_idx');
            $table->dropColumn('notes');
        });

        // Adjustment rows only exist because of this migration, so they are
        // removed before the enum is narrowed back to Lab 5's 'in'/'out'.
        DB::table('inventory_transactions')->where('type', 'adjustment')->delete();

        DB::statement("ALTER TABLE inventory_transactions MODIFY `quantity` INT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE inventory_transactions MODIFY `type` ENUM('in','out') NOT NULL");
        DB::statement("ALTER TABLE inventory_transactions CHANGE `reference_number` `reference` VARCHAR(255) NULL");
    }
};
