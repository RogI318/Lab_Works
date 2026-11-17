<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ---- Stat cards ----
        $totalProducts   = Product::count();
        $lowStockCount   = Product::lowStock()->count();
        $outOfStockCount = Product::outOfStock()->count();
        $inventoryValue  = (float) Product::sum(DB::raw('quantity * unit_price'));

        // ---- Alert center ----
        $lowStockItems = Product::with('category')
                                ->lowStock()
                                ->orderBy('quantity')
                                ->take(6)
                                ->get();

        $outOfStockItems = Product::with('category')->outOfStock()->orderBy('name')->take(6)->get();

        // ---- Recent additions ----
        $recentProducts = Product::with('category')->latest()->take(5)->get();

        // ---- Brand and category tables with their product counts ----
        $brands     = Brand::withCount('products')->withSum('products', 'quantity')->orderBy('id')->get();
        $categories = Category::withCount('products')->withSum('products', 'quantity')->orderBy('id')->get();

        // =====================================================
        // CHART DATA (Part 7, Step 1: charts / visualizations)
        // =====================================================

        // Chart 1: stock status breakdown (doughnut)
        $inStockCount = max(0, $totalProducts - $lowStockCount - $outOfStockCount);
        $statusChart = [
            'labels' => ['In Stock', 'Low Stock', 'Out of Stock'],
            'data'   => [$inStockCount, $lowStockCount, $outOfStockCount],
        ];

        // Chart 2: number of products per category (bar)
        $categoryCountChart = [
            'labels' => $categories->pluck('name')->values(),
            'data'   => $categories->pluck('products_count')->values(),
        ];

        // Chart 3: inventory value per category, quantity x unit price (doughnut)
        $categoryValues = Category::with('products:id,category_id,quantity,unit_price')
            ->orderBy('id')
            ->get()
            ->map(fn ($category) => [
                'name'  => $category->name,
                'value' => round($category->products->sum(fn ($p) => $p->quantity * (float) $p->unit_price), 2),
            ])
            ->filter(fn ($row) => $row['value'] > 0)
            ->values();

        $categoryValueChart = [
            'labels' => $categoryValues->pluck('name'),
            'data'   => $categoryValues->pluck('value'),
        ];

        // Chart 4: the 8 lowest-stock products, quantity vs reorder level (grouped bar)
        $lowestProducts = Product::orderBy('quantity')->take(8)->get();
        $stockLevelChart = [
            'labels'   => $lowestProducts->pluck('name')->values(),
            'quantity' => $lowestProducts->pluck('quantity')->values(),
            'reorder'  => $lowestProducts->pluck('reorder_level')->values(),
        ];

        return view('dashboard', compact(
            'totalProducts', 'lowStockCount', 'outOfStockCount', 'inventoryValue',
            'lowStockItems', 'outOfStockItems', 'recentProducts', 'brands', 'categories',
            'statusChart', 'categoryCountChart', 'categoryValueChart', 'stockLevelChart'
        ));
    }
}