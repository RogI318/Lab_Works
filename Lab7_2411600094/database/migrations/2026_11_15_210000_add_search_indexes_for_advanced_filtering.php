<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lab 7 Part 7 Step 2: the indexes the advanced search and filters lean on.
 *
 * Strictly ADDITIVE — no column is touched, no index is dropped, so every
 * Lab 5/6 query keeps the plan it has today. The existing indexes were checked
 * first (SHOW INDEX), and only the gaps were filled:
 *
 *   already there                              added here
 *   ---------------------------------------    ----------------------------------
 *   products: sku UNIQUE, brand_id,            products (deleted_at, name),
 *             category_id, supplier_id                  products (quantity)
 *   purchase_orders: reference UNIQUE,         purchase_orders (total),
 *             status, order_date,                        (supplier_id, order_date)
 *             (supplier_id, status),
 *             created_by
 *   inventory_transactions: (product_id,       inv (user_id, created_at),
 *             created_at), (type, created_at),          (created_at)
 *             user_id
 *   suppliers: name, is_active,                suppliers (is_active, deleted_at)
 *             lead_time_days, email UNIQUE
 *
 * Two filters deliberately get no index, because an index cannot help them:
 * every free-text box searches with a LEADING wildcard ("%term%"), which
 * MySQL can only answer with a scan, and the stock-status pair compares two
 * columns of the same row (quantity vs reorder_level) rather than one fixed
 * value. Those rows are limited by the tables' size, not by a missing index.
 */
return new class extends Migration
{
    /** @var array<string, array<int, array<int, string>>> table => list of column groups */
    private const INDEXES = [
        'products' => [
            ['deleted_at', 'name'],
            ['quantity'],
        ],
        'purchase_orders' => [
            ['total'],
            ['supplier_id', 'order_date'],
        ],
        'inventory_transactions' => [
            ['user_id', 'created_at'],
            ['created_at'],
        ],
        'suppliers' => [
            ['is_active', 'deleted_at'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $groups) {
            foreach ($groups as $columns) {
                $name = $this->indexName($table, $columns);

                if (! Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $groups) {
            foreach ($groups as $columns) {
                $name = $this->indexName($table, $columns);

                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }

    /** Laravel's own naming rule, spelled out so up() and down() agree. */
    private function indexName(string $table, array $columns): string
    {
        return strtolower($table . '_' . implode('_', $columns) . '_index');
    }
};
