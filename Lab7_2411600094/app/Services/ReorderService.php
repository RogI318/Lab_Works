<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReorderService
{
    private ?Collection $usage = null;

    protected function usageTotals(): Collection
    {
        return $this->usage ??= InventoryTransaction::query()
            ->where('type', InventoryTransaction::TYPE_OUT)
            ->where('created_at', '>=', now()->subDays($this->usageDays()))
            ->selectRaw('product_id, SUM(quantity) as total_out')
            ->groupBy('product_id')
            ->pluck('total_out', 'product_id');
    }

    protected function usageDays(): int
    {
        return max(1, (int) config('procurement.usage_days', 30));
    }

    public function averageDailyUsage(Product $product): float
    {
        return round((int) ($this->usageTotals()->get($product->id) ?? 0) / $this->usageDays(), 4);
    }

    public function leadTimeDays(Product $product): int
    {
        $leadTime = $product->supplierRecord?->lead_time_days;

        return $leadTime === null ? (int) config('procurement.default_lead_time', 7) : (int) $leadTime;
    }

    public function calculateReorderPoint(Product $product): int
    {
        return (int) ceil(
            $this->averageDailyUsage($product) * $this->leadTimeDays($product)
            + (int) $product->reorder_level
        );
    }

    public function calculateOptimalOrderQuantity(Product $product): int
    {
        $safety = (int) $product->reorder_level;
        $coverDays = max(0, (int) config('procurement.cover_days', 30));
        $target = (int) ceil($this->averageDailyUsage($product) * ($this->leadTimeDays($product) + $coverDays)) + $safety;
        $needed = max(max($target, $safety * 2) - (int) $product->quantity, (int) $product->min_order_quantity);
        $packageSize = max(1, (int) $product->package_size);

        return (int) (ceil($needed / $packageSize) * $packageSize);
    }

    public function getProductsNeedingReorder(): Collection
    {
        return Product::with('supplierRecord')
            ->orderBy('name')
            ->get()
            ->filter(fn (Product $product) => (int) $product->quantity <= $this->calculateReorderPoint($product))
            ->values();
    }

    public function groupProductsBySupplier(Collection $products): Collection
    {
        return $products->filter(fn (Product $product) => $product->supplier_id)->groupBy('supplier_id');
    }

    public function hasOpenOrder(Product $product): bool
    {
        return PurchaseOrderItem::where('product_id', $product->id)
            ->whereHas('purchaseOrder', fn ($query) => $query->open())
            ->exists();
    }

    /**
     * @return array{orders: Collection, skipped: Collection, needing: int}
     */
    public function generatePurchaseOrders(?User $createdBy = null): array
    {
        $createdBy ??= User::admins()->orderBy('id')->first();

        if (! $createdBy) {
            throw new RuntimeException('An admin account is required to create automatic purchase orders.');
        }

        $needing = $this->getProductsNeedingReorder();
        $skipped = collect();
        $eligible = collect();

        foreach ($needing as $product) {
            $supplier = $product->supplierRecord;

            if (! $supplier || ! $supplier->is_active) {
                $skipped->push(['sku' => $product->sku, 'name' => $product->name, 'reason' => 'No active supplier']);
                continue;
            }

            if ($this->hasOpenOrder($product)) {
                $skipped->push(['sku' => $product->sku, 'name' => $product->name, 'reason' => 'Open purchase order already exists']);
                continue;
            }

            $eligible->push($product);
        }

        $orders = collect();

        foreach ($this->groupProductsBySupplier($eligible) as $products) {
            $supplier = $products->first()->supplierRecord;
            if ($supplier) {
                $orders->push($this->createDraftOrder($supplier, $products, $createdBy));
            }
        }

        return ['orders' => $orders, 'skipped' => $skipped, 'needing' => $needing->count()];
    }

    protected function createDraftOrder(Supplier $supplier, Collection $products, User $createdBy): PurchaseOrder
    {
        return DB::transaction(function () use ($supplier, $products, $createdBy) {
            $order = PurchaseOrder::create([
                'reference_number' => PurchaseOrder::generateReferenceNumber(),
                'supplier_id' => $supplier->id,
                'status' => 'draft',
                'order_date' => today(),
                'expected_delivery_date' => today()->addDays((int) $supplier->lead_time_days),
                'notes' => 'Automatically generated from current stock and recent usage.',
                'terms' => config('procurement.default_terms'),
                'is_auto_generated' => true,
                'created_by' => $createdBy->id,
            ]);

            foreach ($products as $product) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $this->calculateOptimalOrderQuantity($product),
                    'unit_price' => $product->costFor($supplier->id),
                ]);
            }

            $order->calculateTotal();

            if ((float) $order->subtotal < (float) $supplier->minimum_order_amount) {
                $order->update([
                    'notes' => $order->notes . ' Below the supplier minimum order amount of PHP '
                        . number_format((float) $supplier->minimum_order_amount, 2) . '.',
                ]);
            }

            return $order->load(['supplier', 'items.product']);
        });
    }
}
