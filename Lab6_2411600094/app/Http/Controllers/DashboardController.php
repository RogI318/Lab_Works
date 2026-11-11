<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ---- Stat cards (Part 7, Step 2 of the lab manual) ----
        $totalProducts   = Product::count();
        $lowStockCount   = Product::lowStock()->count();
        $outOfStockCount = Product::outOfStock()->count();
        $inventoryValue  = (float) Product::sum(DB::raw('quantity * unit_price'));
        $totalUnits      = (int) Product::sum('quantity');

        // ---- Alert center ----
        $lowStockItems = Product::with('category')
                                ->lowStock()
                                ->orderBy('quantity')
                                ->take(6)
                                ->get();

        $outOfStockItems = Product::with('category')->outOfStock()->orderBy('name')->take(6)->get();

        // ---- Chart 1: units on hand per category ----
        $unitsByCategory = Category::withSum('products', 'quantity')
                                   ->orderBy('name')
                                   ->get();

        // ---- Chart 2: stock status distribution ----
        $statusDist = [
            Product::STATUS_IN_STOCK  => $totalProducts - $lowStockCount - $outOfStockCount,
            Product::STATUS_LOW_STOCK => $lowStockCount,
            Product::STATUS_OUT_STOCK => $outOfStockCount,
        ];

        // ---- Chart 3: inventory value per category ----
        $valueByCategory = Product::select('categories.name as category')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('SUM(products.quantity * products.unit_price) as stock_value')
            ->groupBy('categories.name')
            ->orderByDesc('stock_value')
            ->take(6)
            ->get();

        // ---- Chart 4: stock movement over the last 7 days ----
        $movements = InventoryTransaction::selectRaw('DATE(created_at) as day')
            ->selectRaw("SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as stock_in")
            ->selectRaw("SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as stock_out")
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $movementDays = collect(range(6, 0))->map(function ($offset) use ($movements) {
            $date  = now()->subDays($offset)->toDateString();
            $entry = $movements->get($date);

            return [
                'label'     => now()->subDays($offset)->format('M j'),
                'stock_in'  => (int) ($entry->stock_in ?? 0),
                'stock_out' => (int) ($entry->stock_out ?? 0),
            ];
        });

        // ---- Recent additions + latest movements ----
        $recentProducts  = Product::with('category')->latest()->take(5)->get();
        $recentMoves     = InventoryTransaction::with('product', 'user')->latest()->take(8)->get();

        // ---- Brand and category tables with their product counts ----
        $brands     = Brand::withCount('products')->withSum('products', 'quantity')->orderBy('id')->get();
        $categories = Category::withCount('products')->withSum('products', 'quantity')->orderBy('id')->get();

        return view('dashboard', compact(
            'totalProducts', 'lowStockCount', 'outOfStockCount', 'inventoryValue', 'totalUnits',
            'lowStockItems', 'outOfStockItems', 'unitsByCategory', 'statusDist', 'valueByCategory',
            'movementDays', 'recentProducts', 'recentMoves', 'brands', 'categories'
        ));
    }
}
