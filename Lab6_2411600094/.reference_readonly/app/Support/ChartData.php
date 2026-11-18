<?php
// Data for the dashboard charts (used by DashboardController and the /api/dashboard/charts route)
// app/Support/ChartData.php

namespace App\Support;

use App\Models\Product;

class ChartData
{
    public static function get(): array
    {
        $all = Product::all(['name', 'category', 'brand', 'quantity', 'reorder_level', 'unit_price']);

        return [
            // one entry per product, with a status the charts can group by
            'products' => $all->map(fn ($p) => [
                'name' => $p->name,
                'category' => $p->category,
                'quantity' => $p->quantity,
                'reorder_level' => $p->reorder_level,
                'status' => $p->quantity <= 0
                    ? 'out-of-stock'
                    : ($p->quantity <= $p->reorder_level ? 'low-stock' : 'in-stock'),
            ])->values()->all(),

            // how many products each category has
            'categories' => $all->groupBy('category')->map(fn ($items, $category) => [
                'category' => $category,
                'itemCount' => $items->count(),
            ])->values()->all(),

            // ---- Lab 5 style charts ----
            // stock value (quantity x unit price) per category, biggest first
            'category_values' => $all->groupBy('category')->map(fn ($items, $category) => [
                'category' => $category,
                'stock_value' => (float) $items->sum(fn ($p) => $p->quantity * $p->unit_price),
            ])->sortByDesc('stock_value')->values()->all(),

            // In Stock / Low Stock / Out of Stock counts
            'status' => [
                'inStock' => $all->filter(fn ($p) => $p->quantity > $p->reorder_level)->count(),
                'lowStock' => $all->filter(fn ($p) => $p->quantity > 0 && $p->quantity <= $p->reorder_level)->count(),
                'outOfStock' => $all->filter(fn ($p) => $p->quantity <= 0)->count(),
            ],

            // top 5 products by stock value
            'top_products' => $all->map(fn ($p) => [
                'name' => $p->name,
                'stock_value' => (float) ($p->quantity * $p->unit_price),
            ])->sortByDesc('stock_value')->take(5)->values()->all(),

            // stock value per brand, biggest first
            'brand_values' => $all->groupBy(fn ($p) => $p->brand ?? 'Unbranded')->map(fn ($items, $brand) => [
                'brand' => $brand,
                'stock_value' => (float) $items->sum(fn ($p) => $p->quantity * $p->unit_price),
            ])->sortByDesc('stock_value')->values()->all(),

            'total_value' => (float) $all->sum(fn ($p) => $p->quantity * $p->unit_price),
        ];
    }
}
