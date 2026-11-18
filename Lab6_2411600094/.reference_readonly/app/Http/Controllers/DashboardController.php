<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ==========================================
        // Time-Based Greeting
        // ==========================================

        $hour = now()->hour;

        if ($hour < 12) {
            $greeting = 'Good Morning';
        } elseif ($hour < 18) {
            $greeting = 'Good Afternoon';
        } else {
            $greeting = 'Good Evening';
        }

        // ==========================================
        // Dashboard Statistics
        // ==========================================

        $totalProducts = Product::count();

        // Low stock = above 0 but at or below the reorder level
        $lowStockCount = Product::where('quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->count();

        $outOfStockCount = Product::where('quantity', 0)->count();

        // Inventory value = SUM(quantity x unit_price)
        $inventoryValue = (float) (
            Product::selectRaw('SUM(quantity * unit_price) as total')->value('total') ?? 0
        );

        $stats = [
            'total_products'     => $totalProducts,
            'low_stock_count'    => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'inventory_value'    => $inventoryValue,
        ];

        // ==========================================
        // Recent Products and Low-Stock Alerts
        // ==========================================

        $recentProducts = Product::latest()->take(5)->get();

        // Includes out-of-stock items (quantity 0 is also at or below the level)
        $lowStockAlerts = Product::whereColumn('quantity', '<=', 'reorder_level')
            ->orderBy('quantity')
            ->get();

        // ==========================================
        // Stock by Brand and Stock by Category
        // ==========================================

        $brandSummary = Product::selectRaw("COALESCE(brand, 'Unbranded') as brand_name")
            ->selectRaw('COUNT(*) as product_count')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(quantity * unit_price) as stock_value')
            ->groupBy('brand')
            ->orderByDesc('stock_value')
            ->get();

        $categorySummary = Product::select('category')
            ->selectRaw('COUNT(*) as product_count')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(quantity * unit_price) as stock_value')
            ->groupBy('category')
            ->orderByDesc('stock_value')
            ->get();

        // ==========================================
        // Chart Data (same numbers as the stat cards)
        // ==========================================

        $statusStats = [
            'inStock'    => max($totalProducts - $lowStockCount - $outOfStockCount, 0),
            'lowStock'   => $lowStockCount,
            'outOfStock' => $outOfStockCount,
        ];

        // Top 5 products by stock value (quantity x unit price)
        $topProducts = Product::selectRaw('name, (quantity * unit_price) as value')
            ->orderByDesc('value')
            ->take(5)
            ->get()
            ->map(fn ($p) => ['name' => $p->name, 'value' => (float) $p->value])
            ->values();

        // ==========================================
        // Send Data to Dashboard
        // ==========================================

        // LAB 6: latest stock movements (refreshed live by public/js/dashboard-poll.js)
        $recentTransactions = InventoryTransaction::with(['product', 'user'])->recent()->take(10)->get();

        return view('dashboard', compact(
            'recentTransactions',
            'greeting',
            'stats',
            'recentProducts',
            'lowStockAlerts',
            'brandSummary',
            'categorySummary',
            'statusStats',
            'topProducts'
        ));
    }
}
