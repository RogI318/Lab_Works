<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $this->syncLowStockAlerts();

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

        // ---- Recent additions + latest movements ----
        $recentProducts  = Product::with('category')->latest()->take(5)->get();
        $recentMoves     = InventoryTransaction::with('product', 'user')->latest()->take(8)->get();

        // ---- Brand and category tables with their product counts ----
        $brands     = Brand::withCount('products')->withSum('products', 'quantity')->orderBy('id')->get();
        $categories = Category::withCount('products')->withSum('products', 'quantity')->orderBy('id')->get();

        return view('dashboard', compact(
            'totalProducts', 'lowStockCount', 'outOfStockCount', 'inventoryValue',
            'lowStockItems', 'outOfStockItems',
            'recentProducts', 'recentMoves', 'brands', 'categories'
        ));
    }

    /**
     * Keep the bell in step with the dashboard's Low Stock Alerts panel:
     * every product already at/below its reorder level gets an alert for the
     * admin opening the page, unless that admin still has an unread one for
     * it (so reloading never creates duplicates).
     */
    private function syncLowStockAlerts(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->is_admin) {
            return;
        }

        $alreadyAlerted = $user->unreadNotifications
            ->pluck('data.product_id')
            ->filter()
            ->all();

        Product::belowReorderLevel()
            ->whereNotIn('id', $alreadyAlerted)
            ->get()
            ->each(fn (Product $product) => $user->notify(new LowStockNotification($product)));
    }
}
