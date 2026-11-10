<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** Part 5 of the theme challenge: "Generate inventory reports". */
    public function index()
    {
        return view('reports.index', $this->reportData());
    }

    public function export()
    {
        $rows = Product::with('category', 'brand')->orderBy('name')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Inventory Valuation Report', now()->toDateTimeString()]);
            fputcsv($out, []);
            fputcsv($out, ['SKU', 'Item', 'Category', 'Brand', 'Supplier', 'Qty', 'Reorder Level', 'Unit Price', 'Total Value', 'Status']);

            $grand = 0.0;
            foreach ($rows as $p) {
                $grand += $p->stockValue();
                fputcsv($out, [
                    $p->sku, $p->name, $p->category->name, $p->brand?->name, $p->supplier,
                    $p->quantity, $p->reorder_level, number_format((float) $p->unit_price, 2),
                    number_format($p->stockValue(), 2), $p->stockStatus(),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['TOTAL INVENTORY VALUE', '', '', '', '', '', '', '', number_format($grand, 2), '']);
            fclose($out);
        }, 'inventory_valuation_' . now()->format('Y_m_d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function reportData(): array
    {
        $byCategory = Category::withCount('products')
            ->withSum('products', 'quantity')
            ->get()
            ->map(function (Category $category) {
                $value = (float) $category->products()
                    ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')
                    ->first()->total;

                return [
                    'name'     => $category->name,
                    'items'    => $category->products_count,
                    'units'    => (int) ($category->products_sum_quantity ?? 0),
                    'value'    => $value,
                    'low_stock' => $category->products()->lowStock()->count(),
                ];
            });

        $byBrand = Brand::withCount('products')->get()->map(function (Brand $brand) {
            return [
                'name'  => $brand->name,
                'items' => $brand->products_count,
                'value' => (float) $brand->products()
                    ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')
                    ->value('total'),
            ];
        });

        $suppliers = Product::selectRaw('supplier, COUNT(*) as items, SUM(quantity * unit_price) as total_value')
            ->whereNotNull('supplier')
            ->groupBy('supplier')
            ->orderByDesc('total_value')
            ->get();

        return [
            'totals' => [
                'products'        => Product::count(),
                'units'           => (int) Product::sum('quantity'),
                'value'           => (float) Product::sum(DB::raw('quantity * unit_price')),
                'low_stock'       => Product::lowStock()->count(),
                'out_of_stock'    => Product::outOfStock()->count(),
                'stock_in_units'  => (int) InventoryTransaction::where('type', InventoryTransaction::TYPE_IN)->sum('quantity'),
                'stock_out_units' => (int) InventoryTransaction::where('type', InventoryTransaction::TYPE_OUT)->sum('quantity'),
            ],
            'byCategory'  => $byCategory,
            'byBrand'     => $byBrand,
            'suppliers'   => $suppliers,
            'lowStock'    => Product::with('category')->lowStock()->orderBy('quantity')->get(),
            'outOfStock'  => Product::with('category')->outOfStock()->orderBy('name')->get(),
        ];
    }
}
