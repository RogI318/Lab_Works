<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Lab 7 Part 3: automated reorder detection.
 *
 * The engine answers one question for every product: "at the rate it actually
 * leaves the shelf, and at the speed this supplier actually delivers, will the
 * buffer still be there when the next order lands?" If the answer is no, it
 * writes a draft purchase order — it never approves or sends one, because a
 * machine may propose a purchase but only a person may commit money to it.
 *
 * Two rules shape everything below:
 *   1. Demand comes from the ledger, not from a guess. Average daily usage is
 *      measured over config('procurement.lookback_days') of real movements.
 *   2. A run must be safe to repeat. Stock already on an open order counts as
 *      stock on its way, which is both the correct arithmetic and the reason
 *      running the command twice in a row raises nothing twice.
 *
 * The scans are batch queries: one grouped query for usage, one for stock on
 * order, one for the products. Adding a product does not add a query.
 */
class ReorderService
{
    /** @var array<int, int>|null units that left the store per product in the lookback window */
    private ?array $unitsOut = null;

    /** @var array<int, int>|null units already promised on an active purchase order */
    private ?array $onOrder = null;

    /** @var array<int, array{sku: string, name: string, reason: string}> why a product was passed over */
    private array $skipped = [];

    /* ==================================================================
       Step 2 — the formulas
       ================================================================== */

    /**
     * Reorder Point = (Average Daily Usage x Lead Time) + Safety Stock.
     *
     * Pure arithmetic on purpose: no model, no database, nothing to set up, so
     * tests/Unit/ReorderPointFormulaTest.php can pin the docx example (10 x 7 +
     * 20 = 90) against the real production formula.
     *
     * Partial units are always rounded up — you cannot wait for half a sheet of
     * plywood, and rounding down would promise a buffer that is not there.
     */
    public static function computeReorderPoint(float $avgDailyUsage, int $leadTimeDays, int $safetyStock): int
    {
        return (int) ceil(max(0.0, $avgDailyUsage) * max(0, $leadTimeDays)) + max(0, $safetyStock);
    }

    /**
     * This product's reorder point, from its own history and its own supplier.
     *
     * Never below products.reorder_level, so the Lab 6 low-stock alerts and the
     * Part 3 engine can never disagree about whether an item is short.
     */
    public function calculateReorderPoint(Product $product): int
    {
        $usage = $this->averageDailyUsage($product);

        return max(
            $product->reorder_level,
            self::computeReorderPoint($usage, $this->leadTimeFor($product), $this->safetyStockFor($product, $usage))
        );
    }

    /**
     * How many units to order: enough to reach the target level, in whole
     * packages, never under the supplier's minimum.
     *
     * A simplified EOQ. The textbook formula sqrt((2 x D x S) / H) trades
     * ordering cost against holding cost, but a hardware shop has no idea what
     * either is per line: no cost is recorded for raising a PO and stock
     * carrying cost is never booked. So the ordering cost term collapses into a
     * policy question the sheet does answer — cover demand across one review
     * period — and the arithmetic becomes
     *   target  = reorder point + (daily usage x review_period_days)
     *   quantity= target - (on hand + already on order), rounded up to a package
     * which is the same "order enough to last until the next look" that EOQ
     * approximates, without inventing numbers nobody measured.
     */
    public function calculateOptimalOrderQuantity(Product $product): int
    {
        $supplier = $product->primarySupplier;

        if ($supplier === null) {
            return 0;
        }

        $usage  = $this->averageDailyUsage($product);
        $target = $this->calculateReorderPoint($product)
                + (int) ceil($usage * (int) config('procurement.review_period_days'));

        $gap = $target - ($product->quantity + $this->onOrderFor($product));

        if ($gap <= 0) {
            return 0;
        }

        // Both terms come from this supplier's price agreement for this item.
        $package = max(1, (int) ($this->agreementFor($product, $supplier)?->package_size ?? 1));
        $minimum = max(1, (int) ($this->agreementFor($product, $supplier)?->minimum_order_quantity ?? 1));

        // Minimum first, then up to a whole package, so the result satisfies
        // both terms at once (ordering 50 of a 10-pack beats 45 of a minimum 50).
        return (int) (ceil(max($gap, $minimum) / $package) * $package);
    }

    /* ==================================================================
       Step 2 — the scan
       ================================================================== */

    /**
     * Every product that should be on tomorrow's order, newest policy aside.
     *
     * Passing over a product is always explained — $this->skipped collects the
     * reason so the command and the Part 5 report can show why the engine was
     * quiet, which is the difference between a rule and a mystery.
     */
    public function getProductsNeedingReorder(): Collection
    {
        $this->skipped = [];

        return Product::query()
            // supplier + category + every price agreement: three joins, not 3N.
            ->with(['primarySupplier', 'category', 'suppliers'])
            ->orderBy('id')
            ->get()
            ->filter(function (Product $product): bool {
                $supplier = $product->primarySupplier;

                if ($supplier === null) {
                    $this->skip($product, 'no primary supplier on file');

                    return false;
                }

                if (! $supplier->is_active) {
                    $this->skip($product, sprintf('"%s" is marked inactive', $supplier->name));

                    return false;
                }

                if ($product->quantity > $this->calculateReorderPoint($product)) {
                    return false;   // healthy: not a skip, nothing to say
                }

                if ($this->calculateOptimalOrderQuantity($product) === 0) {
                    $this->skip($product, sprintf(
                        '%d unit(s) already on an open purchase order',
                        $this->onOrderFor($product)
                    ));

                    return false;
                }

                return true;
            })
            ->values();
    }

    /** @return array<int, array{sku: string, name: string, reason: string}> why a product was passed over */
    public function skipped(): array
    {
        return $this->skipped;
    }

    /**
     * The rows behind Part 4's low-stock e-mail: every product sitting at or
     * below its reorder point, whether or not the engine can act on it.
     *
     * Deliberately wider than getProductsNeedingReorder(). That method answers
     * "what goes on a draft today"; an administrator reading their inbox wants
     * the whole picture, so an item already covered by an open order still
     * appears — with `suggested` at zero and `on_order` showing why.
     *
     * No new queries: the usage and on-order scans are memoised, so calling
     * this straight after a sweep reuses the numbers that produced it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function shortItems(): array
    {
        return Product::query()
            ->with(['primarySupplier', 'suppliers'])
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => [
                'id'        => $product->id,
                'sku'       => $product->sku,
                'name'      => $product->name,
                'on_hand'   => $product->quantity,
                'point'     => $this->calculateReorderPoint($product),
                'suggested' => $this->calculateOptimalOrderQuantity($product),
                'on_order'  => $this->onOrderFor($product),
                'supplier'  => $product->primarySupplier?->name ?? '(none on file)',
            ])
            ->filter(fn (array $row): bool => $row['on_hand'] <= $row['point'])
            ->values()
            ->all();
    }

    /* ==================================================================
       Step 3 — grouping and drafting the orders
       ================================================================== */

    /**
     * One group per supplier, because one vendor gets one delivery note, not
     * one per SKU. Keyed by supplier id.
     *
     * @param  Collection<int, Product>  $products
     * @return Collection<int, array{supplier: Supplier, products: Collection<int, Product>}>
     */
    public function groupProductsBySupplier(Collection $products): Collection
    {
        return $products
            ->filter(fn (Product $product) => $product->primarySupplier !== null)
            ->groupBy(fn (Product $product) => $product->primarySupplier->id)
            ->map(fn (Collection $group): array => [
                'supplier' => $group->first()->primarySupplier,
                'products' => $group->values(),
            ]);
    }

    /**
     * Raise one draft order per supplier.
     *
     * Each supplier is its own transaction: one vendor's insert failing must
     * not roll back the orders already raised for the others.
     *
     * @return array{orders: array<int, array<string, mixed>>, products: int, skipped: array<int, array{sku: string, name: string, reason: string}>}
     */
    public function generatePurchaseOrders(bool $dryRun = false): array
    {
        $groups = $this->groupProductsBySupplier($this->getProductsNeedingReorder());

        if ($groups->isEmpty()) {
            return ['orders' => [], 'products' => 0, 'skipped' => $this->skipped];
        }

        // The engine has no login of its own, so the paper trail names the
        // administrator it works for — the same person who would click Save.
        $buyer = User::where('is_admin', true)->orderBy('id')->first();

        if ($buyer === null) {
            throw new RuntimeException('The reorder engine found no administrator to raise orders in.');
        }

        $orders = [];

        foreach ($groups as $group) {
            $plan = $this->plan($group['supplier'], $group['products']);

            $orders[] = $dryRun ? $this->preview($plan) : $this->write($plan, $buyer);
        }

        // Part 5 caches the dashboard's reorder widgets under these keys; a
        // draft that exists must never be hidden behind a stale count.
        ProcurementMetricsService::flushWidgets();

        return [
            'orders'   => $orders,
            'products' => $groups->sum(fn (array $group) => $group['products']->count()),
            'skipped'  => $this->skipped,
        ];
    }

    /* ==================================================================
       Internals
       ================================================================== */

    /**
     * Work out one supplier's draft — quantities, prices and totals — without
     * touching the database. The dry run prints this; the real run writes it.
     *
     * @param  Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    private function plan(Supplier $supplier, Collection $products): array
    {
        $lines = $products->map(function (Product $product) use ($supplier): array {
            $quantity   = $this->calculateOptimalOrderQuantity($product);
            $unitPrice  = $this->unitCostFor($product, $supplier);

            return [
                'product_id' => $product->id,
                'sku'        => $product->sku,
                'name'       => $product->name,
                'on_hand'    => $product->quantity,
                'point'      => $this->calculateReorderPoint($product),
                'quantity'   => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
            ];
        })->filter(fn (array $line) => $line['quantity'] > 0)->values();

        $subtotal = round($lines->sum('line_total'), 2);
        $tax      = round($subtotal * (float) config('procurement.tax_rate'), 2);

        $notes = sprintf(
            'Auto-generated by reorder check on %s (%d product(s), %d day demand window).',
            now()->format('M j, Y'),
            $lines->count(),
            (int) config('procurement.lookback_days')
        );

        // Below the vendor's own floor the order is still real — the stock is
        // short — but nobody should send it without knowing it will be refused
        // or topped up, so the warning is written where the buyer will read it.
        $warning = $subtotal < (float) $supplier->minimum_order_amount
            ? sprintf(
                'WARNING: subtotal PHP %s is below this supplier\'s minimum order of PHP %s — combine with other lines or expect the vendor to decline.',
                number_format($subtotal, 2),
                number_format((float) $supplier->minimum_order_amount, 2)
            )
            : null;

        return [
            'supplier'   => $supplier,
            'lines'      => $lines,
            'units'      => (int) $lines->sum('quantity'),
            'subtotal'   => $subtotal,
            'tax'        => $tax,
            'total'      => round($subtotal + $tax, 2),
            'notes'      => $warning === null ? $notes : $notes . "\n" . $warning,
            'warning'    => $warning,
            'order_date' => now()->toDateString(),
        ];
    }

    /**
     * Persist one planned order. Mirrors PurchaseOrderController::store(): the
     * reference number is taken inside the transaction under the same row lock
     * a human typing at the same minute would hit.
     */
    private function write(array $plan, User $buyer): array
    {
        $order = DB::transaction(function () use ($plan, $buyer): PurchaseOrder {
            $order = PurchaseOrder::create([
                'reference_number'       => PurchaseOrder::generateReferenceNumber($plan['order_date']),
                'supplier_id'            => $plan['supplier']->id,
                'status'                 => PurchaseOrder::STATUS_DRAFT,
                'order_date'             => $plan['order_date'],
                'expected_delivery_date' => now()
                    ->addDays($this->leadTimeDays($plan['supplier']))
                    ->toDateString(),
                'notes'                  => $plan['notes'],
                'terms'                  => $plan['supplier']->payment_terms,
                'is_auto_generated'      => true,
                'created_by'             => $buyer->id,
            ]);

            foreach ($plan['lines'] as $line) {
                $order->items()->create([
                    'product_id' => $line['product_id'],
                    'quantity'   => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                ]);
            }

            // Server-side arithmetic again, from the stored rows: the plan is a
            // preview, the model is the record.
            $order->load('items')->calculateTotal()->save();

            return $order;
        });

        $order->logActivity('created', sprintf(
            '%s was raised automatically by the reorder check: %d line(s), %d unit(s), PHP %s incl. VAT.',
            $order->reference_number,
            $order->items->count(),
            $plan['units'],
            number_format((float) $order->total, 2)
        ), [
            'auto_generated' => true,
            'lookback_days'  => (int) config('procurement.lookback_days'),
            'below_minimum'  => $plan['warning'] !== null,
        ], $buyer->id);

        return $plan + ['reference' => $order->reference_number, 'order' => $order];
    }

    /** @return array<string, mixed> the dry-run shape: same keys, no reference */
    private function preview(array $plan): array
    {
        return $plan + ['reference' => null, 'order' => null];
    }

    /** What this supplier charges for this item, or the shelf price if no agreement exists. */
    private function unitCostFor(Product $product, Supplier $supplier): float
    {
        $cost = $this->agreementFor($product, $supplier)?->unit_cost;

        return round((float) ($cost ?? $product->unit_price), 2);
    }

    /** The price agreement row, or null when this vendor has never quoted the item. */
    private function agreementFor(Product $product, Supplier $supplier): mixed
    {
        return $product->suppliers->firstWhere('id', $supplier->id)?->pivot;
    }

    private function leadTimeFor(Product $product): int
    {
        return $product->primarySupplier === null
            ? $this->leadTimeDays(null)
            : $this->leadTimeDays($product->primarySupplier);
    }

    /** A supplier's lead time, or the configured default when it has none. */
    private function leadTimeDays(?Supplier $supplier): int
    {
        return (int) ($supplier?->lead_time_days ?: config('procurement.default_lead_time_days'));
    }

    /**
     * Days of cover kept on top of lead-time demand.
     *
     * The chosen rule is max(reorder_level, usage x default_safety_stock_days):
     * Lab 6's reorder_level is the buffer a human already decided this item
     * needs, and a days-based buffer computed from 30 quiet days of history can
     * easily be smaller than that. Taking the larger of the two means the
     * engine can only ever ask for more protection than the manual threshold,
     * never less — which keeps Part 3's alerts from looking like a regression
     * against the Lab 6 numbers on the same screen.
     */
    private function safetyStockFor(Product $product, float $usage): int
    {
        return max(
            $product->reorder_level,
            (int) ceil($usage * (int) config('procurement.default_safety_stock_days'))
        );
    }

    private function averageDailyUsage(Product $product): float
    {
        return $this->unitsOutMap()[$product->id] ?? 0;
    }

    private function onOrderFor(Product $product): int
    {
        return $this->onOrderMap()[$product->id] ?? 0;
    }

    /**
     * Units sold or lost per product over the lookback window, in ONE grouped
     * query — the whole point of the engine being cheap enough to run daily.
     *
     * Demand is stock going out: 'out' rows, plus the shrinkage half of
     * adjustments. A positive adjustment is a mis-count being put right, not
     * demand, so it is left out.
     *
     * @return array<int, float> product_id => average units per day
     */
    private function unitsOutMap(): array
    {
        if ($this->unitsOut === null) {
            $this->unitsOut = InventoryTransaction::query()
                ->where('created_at', '>=', now()->subDays($this->lookbackDays()))
                ->selectRaw(
                    "product_id,
                     SUM(CASE WHEN type = 'out' THEN ABS(quantity) ELSE 0 END)
                   + SUM(CASE WHEN type = 'adjustment' AND quantity < 0 THEN ABS(quantity) ELSE 0 END) AS units_out"
                )
                ->groupBy('product_id')
                ->get()
                ->mapWithKeys(fn ($row) => [(int) $row->product_id => (int) $row->units_out])
                ->all();
        }

        // Stored as whole units so the map reads back as the ledger said it;
        // the per-day figure is derived here, once per call, never per product.
        $days = $this->lookbackDays();

        return array_map(fn (int $units) => $units / $days, $this->unitsOut);
    }

    /**
     * Units already ordered and not yet received, per product, in one query.
     *
     * Soft-deleted orders are excluded with the join condition: an order nobody
     * can find is not a promise. Draft orders DO count, and that is what makes
     * the whole command idempotent — the second run sees the first run's draft
     * as stock on its way and asks for nothing more.
     *
     * @return array<int, int> product_id => outstanding units
     */
    private function onOrderMap(): array
    {
        if ($this->onOrder !== null) {
            return $this->onOrder;
        }

        $this->onOrder = DB::table('purchase_order_items as poi')
            ->join('purchase_orders as po', 'po.id', '=', 'poi.purchase_order_id')
            ->whereNull('po.deleted_at')
            ->whereIn('po.status', PurchaseOrder::ACTIVE_STATUSES)
            ->selectRaw('poi.product_id, SUM(poi.quantity - poi.received_quantity) AS outstanding')
            ->groupBy('poi.product_id')
            ->get()
            ->keyBy('product_id')
            ->map(fn ($row) => (int) $row->outstanding)
            ->all();

        return $this->onOrder;
    }

    private function lookbackDays(): int
    {
        return max(1, (int) config('procurement.lookback_days'));
    }

    private function skip(Product $product, string $reason): void
    {
        $this->skipped[$product->id] = [
            'sku'    => $product->sku,
            'name'   => $product->name,
            'reason' => $reason,
        ];
    }
}
