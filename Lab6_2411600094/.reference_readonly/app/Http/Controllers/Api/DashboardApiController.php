<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\TransactionResource;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Support\ChartData;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends Controller
{
    // GET /api/dashboard/stats  (same definitions as the Lab 5 stat cards)
    public function stats()
    {
        return response()->json(['data' => [
            'total_products' => Product::count(),
            'low_stock' => Product::where('quantity', '>', 0)->whereColumn('quantity', '<=', 'reorder_level')->count(),
            'out_of_stock' => Product::where('quantity', 0)->count(),
            'inventory_value' => (float) Product::sum(DB::raw('quantity * unit_price')),
            'updated_at' => now()->toDateTimeString(),
        ]]);
    }

    // GET /api/dashboard/charts - data for the dashboard charts
    public function charts()
    {
        return response()->json(['data' => ChartData::get()]);
    }

    // GET /api/products/low-stock
    public function lowStock()
    {
        return ProductResource::collection(
            Product::lowStock()->orderBy('quantity')->take(10)->get()
        );
    }

    // GET /api/transactions/recent
    public function recentTransactions()
    {
        return TransactionResource::collection(
            InventoryTransaction::with(['product', 'user'])->recent()->take(10)->get()
        );
    }

    // GET /api/products/{product}/stock
    public function stock(Product $product)
    {
        return response()->json(['data' => ['quantity' => $product->quantity]]);
    }
}
