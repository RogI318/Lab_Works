<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Services\ReorderService;

class ProcurementDashboardController extends Controller
{
    public function index(ReorderService $reorderService)
    {
        $metrics = $this->metrics($reorderService);
        $recentOrders = PurchaseOrder::with('supplier')
            ->latest('order_date')
            ->latest('id')
            ->take(5)
            ->get();

        return view('procurement.dashboard', compact('metrics', 'recentOrders'));
    }

    public function data(ReorderService $reorderService)
    {
        return response()->json($this->metrics($reorderService));
    }

    private function metrics(ReorderService $reorderService): array
    {
        return [
            'open_purchase_orders' => PurchaseOrder::open()->count(),
            'awaiting_approval' => PurchaseOrder::pending()->count(),
            'awaiting_receipt' => PurchaseOrder::where('status', 'sent')->count(),
            'confirmed_sales_orders' => SalesOrder::where('status', 'confirmed')->count(),
            'products_needing_reorder' => $reorderService->getProductsNeedingReorder()->count(),
        ];
    }
}
