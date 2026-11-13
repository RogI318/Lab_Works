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
        // ---- Stat cards (Part 7, Step 2 of the lab manual) ----
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

        return view('dashboard', compact(
            'totalProducts', 'lowStockCount', 'outOfStockCount', 'inventoryValue',
            'lowStockItems', 'outOfStockItems', 'recentProducts', 'brands', 'categories'
        ));
    }
}
