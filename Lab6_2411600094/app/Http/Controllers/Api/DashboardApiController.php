<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardStatsResource;
use App\Http\Resources\InventoryTransactionResource;
use App\Http\Resources\ProductResource;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Lab 6 Part 7 Step 1: the JSON the dashboard polls every 30 seconds. */
class DashboardApiController extends Controller
{
    public function charts()
    {
        $products = Product::with(['category','brand'])->get();
        return response()->json(['data' => [
            'products' => $products->map(fn ($p) => ['name'=>$p->name,'category'=>$p->category?->name,'quantity'=>$p->quantity,'reorder_level'=>$p->reorder_level,'status'=>$p->quantity<=0?'out-of-stock':($p->quantity<=$p->reorder_level?'low-stock':'in-stock')])->values(),
            'status' => ['inStock'=>$products->filter(fn($p)=>$p->quantity>$p->reorder_level)->count(),'lowStock'=>$products->filter(fn($p)=>$p->quantity>0&&$p->quantity<=$p->reorder_level)->count(),'outOfStock'=>$products->where('quantity','<=',0)->count()],
            'total_value' => (float) $products->sum(fn($p)=>$p->quantity*(float)$p->unit_price),
        ]]);
    }
    public function stats(Request $request): DashboardStatsResource
    {
        return new DashboardStatsResource([
            'total_products'     => Product::count(),
            'total_units'        => (int) Product::sum('quantity'),
            'low_stock_count'    => Product::lowStock()->count(),
            'out_of_stock_count' => Product::outOfStock()->count(),
            'inventory_value'    => (float) Product::sum(DB::raw('quantity * unit_price')),
            'movements_today'    => InventoryTransaction::whereDate('created_at', today())->count(),
            'unread_alerts'      => $request->user()->unreadNotifications()->count(),
            'generated_at'       => now()->toIso8601String(),
        ]);
    }

    /**
     * Low-stock list = the docx Part 6 set: every product at or below its
     * reorder level, empties first (so both dashboard alert panes update).
     */
    public function lowStock()
    {
        $products = Product::with('category')
            ->belowReorderLevel()
            ->orderBy('quantity')
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products);
    }

    public function recent()
    {
        $transactions = InventoryTransaction::with(['product', 'user'])
            ->recent()
            ->take(8)
            ->get();

        return InventoryTransactionResource::collection($transactions);
    }

    /**
     * From the other Lab 6 project: current quantity of one product, for any
     * signed-in user (the admin-only adjustment lookup stays separate).
     */
    public function currentStock(Product $product)
    {
        return response()->json([
            'id'            => $product->id,
            'name'          => $product->name,
            'quantity'      => $product->quantity,
            'reorder_level' => $product->reorder_level,
            'stock_status'  => $product->stock_status,
        ]);
    }
}
