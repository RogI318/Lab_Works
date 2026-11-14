<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lab 7 Part 2 Step 6: demo purchase orders covering the last six months, so
 * the list, the workflow buttons, the approval history and the Part 6 supplier
 * report all have something real to show.
 *
 * Two rules the data had to be written around:
 *
 * 1. NO STOCK MOVEMENT. The received orders do not create InventoryTransaction
 *    rows and do not touch products.quantity. Lab 6's ledger is already the
 *    source of truth for what is on the shelf; inventing a second set of
 *    movements for goods already recorded there would double-count them. The
 *    lines simply carry the received_quantity the paperwork says arrived.
 *
 * 2. IDEMPOTENT WITHOUT OVERWRITING WORK. Orders are keyed on their reference
 *    number and written with firstOrCreate: if an order exists, the seeder
 *    leaves it alone. Re-running after a walk-through that already approved or
 *    received something keeps that work instead of resetting it.
 *
 * The mix is deliberate — on-time, late and short deliveries (Part 6 needs all
 * three), one order in every workflow state, and one pending order raised by
 * the manager so the "you cannot approve your own order" rule is visible.
 */
class PurchaseOrderSeeder extends Seeder
{
    /** What the supplier charges us: the ratio SupplierSeeder used. */
    private const COST_RATIO = 0.70;

    /**
     * Each order:
     *   days    how many days before today the order was raised
     *   status  one of PurchaseOrder::STATUSES
     *   by      'admin' | 'manager' — who raised it
     *   lines   SKU => units ordered (all from one supplier)
     *   late    days after the promised date the goods landed (received)
     *   short   SKU => units that never arrived (received)
     *   partial SKU => units that arrived so far (partially_received)
     *   note    internal note
     *   reason  why it was rejected
     */
    private const ORDERS = [
        // ---------- finished: received ----------
        ['days' => 176, 'status' => 'received', 'by' => 'admin',
         'lines' => ['LMB-COC-1001' => 60, 'LMB-PLY-1002' => 12, 'HDW-GI-2002' => 25]],

        ['days' => 151, 'status' => 'received', 'by' => 'manager', 'late' => 4,
         'lines' => ['PLB-PVC-5001' => 80, 'PLB-EL-5002' => 200, 'PLB-VL-5003' => 15],
         'note'  => 'Supplier warned the truck was held up at the port.'],

        ['days' => 128, 'status' => 'received', 'by' => 'admin', 'short' => ['ELC-WR-4001' => 4],
         'lines' => ['ELC-WR-4001' => 20, 'ELC-EC-4002' => 30, 'ELC-TA-4004' => 60],
         'note'  => 'Four wire rolls short — claimed against the supplier.'],

        ['days' => 97, 'status' => 'received', 'by' => 'manager', 'late' => 9,
         'lines' => ['TLS-AG-3003' => 6, 'TLS-DR-3002' => 4, 'TLS-BL-3005' => 10],
         'note'  => 'Power tools arrived after the sale weekend started.'],

        ['days' => 64, 'status' => 'received', 'by' => 'admin',
         'lines' => ['PNT-LTX-6001' => 24, 'PNT-EN-6002' => 18, 'PNT-BR-6003' => 40]],

        ['days' => 33, 'status' => 'received', 'by' => 'admin', 'late' => 2,
         'short' => ['HDW-NL-2001' => 6],
         'lines' => ['HDW-NL-2001' => 50, 'TLS-HM-3001' => 20, 'TLS-TM-3004' => 15]],

        // ---------- in flight ----------
        ['days' => 26, 'status' => 'partially_received', 'by' => 'manager',
         'partial' => ['TLS-TM-3004' => 25, 'HDW-HG-2004' => 40],
         'lines'   => ['TLS-TM-3004' => 40, 'HDW-HG-2004' => 60, 'TLS-HM-3001' => 12],
         'note'    => 'Split delivery: the hammers are still on the supplier\'s side.'],

        ['days' => 19, 'status' => 'sent', 'by' => 'admin',
         'lines' => ['PLB-PVC-5001' => 60, 'PLB-HT-5004' => 30, 'PLB-VL-5003' => 10]],

        ['days' => 12, 'status' => 'approved', 'by' => 'manager',
         'lines' => ['ELC-LED-4003' => 60, 'ELC-EC-4002' => 25]],

        ['days' => 8, 'status' => 'pending', 'by' => 'admin',
         'lines' => ['LMB-CEI-1003' => 40]],

        ['days' => 5, 'status' => 'pending', 'by' => 'manager',
         'lines' => ['TLS-DR-3002' => 6, 'TLS-AG-3003' => 4],
         'note'  => 'Raised by the manager, so an administrator has to approve it.'],

        ['days' => 3, 'status' => 'pending', 'by' => 'admin',
         'lines' => ['PNT-LTX-6001' => 12, 'PNT-BR-6003' => 20]],

        ['days' => 6, 'status' => 'rejected', 'by' => 'admin',
         'lines'  => ['HDW-GI-2002' => 200],
         'reason' => 'Two hundred sheets is a quarter of our roofing demand; start with a 50-sheet trial order.'],

        ['days' => 4, 'status' => 'cancelled', 'by' => 'manager',
         'lines' => ['ELC-TA-4004' => 120],
         'note'  => 'Contractor moved the job to next quarter, so the order was called off.'],

        // ---------- drafts ----------
        ['days' => 1, 'status' => 'draft', 'by' => 'admin',
         'lines' => ['PLB-EL-5002' => 300, 'PLB-VL-5003' => 12]],

        ['days' => 0, 'status' => 'draft', 'by' => 'manager',
         'lines' => ['TLS-BL-3005' => 15, 'TLS-AG-3003' => 3]],
    ];

    /** @var array<string, int> how many orders each order date already used */
    private array $sequence = [];

    public function run(): void
    {
        // The workflow needs real actors, so approvals and receipts can be
        // attributed to somebody other than the buyer.
        $this->call(ProcurementUserSeeder::class);

        $actors = [
            'admin'     => User::where('is_admin', true)->orderBy('id')->firstOrFail(),
            'manager'   => User::where('role', User::ROLE_MANAGER)->orderBy('id')->firstOrFail(),
            'warehouse' => User::where('role', User::ROLE_WAREHOUSE)->orderBy('id')->firstOrFail(),
        ];

        $suppliers = Supplier::all()->keyBy('name');
        $products  = Product::with('suppliers', 'primarySupplier')->get()->keyBy('sku');

        $created = $skipped = 0;

        foreach (self::ORDERS as $spec) {
            $supplierName = $this->supplierFor($spec['lines'], $products);

            if ($supplierName === null || ! isset($suppliers[$supplierName])) {
                $this->command?->warn(sprintf(
                    'Skipped a demo order: its lines do not share one supplier (%s).',
                    implode(', ', array_keys($spec['lines']))
                ));

                continue;
            }

            $order = $this->seedOrder($spec, $suppliers[$supplierName], $products, $actors);

            $order->wasRecentlyCreated ? $created++ : $skipped++;
        }

        $this->command?->info(sprintf(
            'Purchase orders: %d demo order(s) written, %d already existed and were left untouched.',
            $created,
            $skipped
        ));
    }

    /** Header, lines and history for one demo order. */
    private function seedOrder(array $spec, Supplier $supplier, Collection $products, array $actors): PurchaseOrder
    {
        $status   = $spec['status'];
        $orderDate = now()->subDays($spec['days'])->setTime(9, 30);

        // Every date in the lifecycle, derived from the supplier's own lead
        // time, so "expected" is a promise the vendor could actually keep.
        $expected = $orderDate->copy()->addDays($supplier->lead_time_days)->setTime(9, 0);
        $approved = $orderDate->copy()->addDay()->setTime(11, 10);
        $sent     = $approved->copy()->addHours(20);
        $received = $expected->copy()->addDays($spec['late'] ?? 0)->setTime(15, 45);

        $creator  = $actors[$spec['by']];

        // The approver is always somebody other than the buyer — the seeded
        // data never breaks the rule the policy enforces.
        $approver = $spec['by'] === 'admin' ? $actors['manager'] : $actors['admin'];
        $receiver = $actors['warehouse'];

        $finished = in_array($status, [
            PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SENT,
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_RECEIVED,
        ], true);

        $order = PurchaseOrder::withTrashed()->firstOrCreate(
            ['reference_number' => $this->referenceNumber($orderDate)],
            [
                'supplier_id'            => $supplier->id,
                'status'                 => $status,
                'order_date'             => $orderDate->toDateString(),
                'expected_delivery_date' => $expected->toDateString(),
                'notes'                  => $spec['note'] ?? null,
                'terms'                  => "{$supplier->payment_terms}; delivery to the RogieMart warehouse, Quezon City.",
                'rejection_reason'       => $spec['reason'] ?? null,
                'is_auto_generated'      => false,
                'created_by'             => $creator->id,
                'approved_by'            => $finished ? $approver->id : null,
                'approved_at'            => $this->stampFor($status, 'approved_at', $approved),
                'rejected_by'            => $status === PurchaseOrder::STATUS_REJECTED ? $approver->id : null,
                'rejected_at'            => $status === PurchaseOrder::STATUS_REJECTED ? $approved : null,
                'sent_at'                => $this->stampFor($status, 'sent_at', $sent),
                'received_at'            => $this->stampFor($status, 'received_at', $received),
            ]
        );

        if (! $order->wasRecentlyCreated) {
            return $order;
        }

        $touched = $this->lastTouch($status, $orderDate, $approved, $sent, $received);

        foreach ($spec['lines'] as $sku => $quantity) {
            $product = $products[$sku];

            $order->items()->create([
                'product_id'        => $product->id,
                'quantity'          => $quantity,
                // The price agreed with this supplier, frozen on the line.
                'unit_price'        => $this->costFor($product, $supplier),
                'received_quantity' => $this->receivedFor($status, $quantity, $spec, $sku),
            ]);
        }

        $order->load('items')->calculateTotal()->save();

        // created_at / updated_at are not fillable, so the demo dates are
        // written straight to the table after the model has done its work.
        $this->backdate('purchase_orders', $order->id, $orderDate, $touched);

        $this->seedHistory($order, $spec, $creator, $approver, $receiver,
            $orderDate, $approved, $sent, $received, $expected);

        return $order;
    }

    /**
     * The trail the show page renders as "Approval History". The timestamps
     * match the header columns exactly, so the log and the record never
     * disagree about who did what when.
     */
    private function seedHistory(
        PurchaseOrder $order,
        array $spec,
        User $creator,
        User $approver,
        User $receiver,
        Carbon $orderDate,
        Carbon $approved,
        Carbon $sent,
        Carbon $received,
        Carbon $expected
    ): void {
        $this->log($order, 'created', $creator, $orderDate, sprintf(
            'Purchase order %s for %s was created with %d line(s), total PHP %s.',
            $order->reference_number,
            $order->supplier->name,
            count($spec['lines']),
            number_format((float) $order->total, 2)
        ), ['total' => (float) $order->total]);

        if ($spec['status'] === PurchaseOrder::STATUS_DRAFT) {
            return;
        }

        $this->log($order, 'submitted', $creator, $orderDate->copy()->addMinutes(40), sprintf(
            '%s was submitted for approval.',
            $order->reference_number
        ));

        $trail = match ($spec['status']) {
            PurchaseOrder::STATUS_APPROVED => [
                ['approved', $approver, $approved, sprintf('%s approved the order.', $approver->name)],
            ],
            PurchaseOrder::STATUS_SENT => [
                ['approved', $approver, $approved, sprintf('%s approved the order.', $approver->name)],
                ['sent', $approver, $sent, sprintf('The order was sent to %s (%s).',
                    $order->supplier->name, $order->supplier->email)],
            ],
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED => [
                ['approved', $approver, $approved, sprintf('%s approved the order.', $approver->name)],
                ['sent', $approver, $sent, sprintf('The order was sent to %s (%s).',
                    $order->supplier->name, $order->supplier->email)],
                ['received', $receiver, $received, sprintf(
                    '%s recorded a partial delivery; %d unit(s) are still owed.',
                    $receiver->name, $order->outstandingUnits())],
            ],
            PurchaseOrder::STATUS_RECEIVED => [
                ['approved', $approver, $approved, sprintf('%s approved the order.', $approver->name)],
                ['sent', $approver, $sent, sprintf('The order was sent to %s (%s).',
                    $order->supplier->name, $order->supplier->email)],
                ['received', $receiver, $received, ($spec['late'] ?? 0) > 0
                    ? sprintf('%s received the delivery %d day(s) after the promised date of %s.',
                        $receiver->name, $spec['late'], $expected->toFormattedDateString())
                    : sprintf('%s received the full delivery on the promised date of %s.',
                        $receiver->name, $expected->toFormattedDateString())],
            ],
            PurchaseOrder::STATUS_REJECTED => [
                ['rejected', $approver, $approved, sprintf('%s rejected the order: %s',
                    $approver->name, $spec['reason'])],
            ],
            PurchaseOrder::STATUS_CANCELLED => [
                ['cancelled', $approver, $approved, sprintf('%s cancelled the order: %s',
                    $approver->name, $spec['note'])],
            ],
            default => [],
        };

        foreach ($trail as [$action, $actor, $at, $description]) {
            $this->log($order, $action, $actor, $at, sprintf('%s — %s', $order->reference_number, $description));
        }
    }

    /** One activity_logs row with the date it is supposed to have happened on. */
    private function log(PurchaseOrder $order, string $action, User $actor, Carbon $at, string $description, array $properties = []): void
    {
        $entry = $order->activities()->create([
            'user_id'    => $actor->id,
            'action'     => $action,
            'description' => $description,
            'properties' => $properties ?: null,
        ]);

        $this->backdate('activity_logs', $entry->id, $at, $at);
    }

    /* ------------------------------------------------------------------
       helpers
       ------------------------------------------------------------------ */

    /** PO-YYYYMMDD-0001, the same shape PurchaseOrder::generateReferenceNumber() makes. */
    private function referenceNumber(Carbon $orderDate): string
    {
        $day = $orderDate->format('Ymd');

        // Counted from zero on every run, so a re-seed rebuilds the same
        // references and firstOrCreate recognises its own earlier work.
        $this->sequence[$day] ??= 0;

        return sprintf('PO-%s-%04d', $day, ++$this->sequence[$day]);
    }

    /** A demo order is raised against one vendor, so all its lines must agree. */
    private function supplierFor(array $lines, Collection $products): ?string
    {
        $names = collect(array_keys($lines))
            ->map(fn (string $sku) => $products[$sku]?->primarySupplier?->name)
            ->filter()
            ->unique();

        return $names->count() === 1 ? $names->first() : null;
    }

    /** Agreed purchase cost when the price agreement has one, catalogue cost otherwise. */
    private function costFor(Product $product, Supplier $supplier): float
    {
        $agreed = $product->suppliers->firstWhere('id', $supplier->id)?->pivot->unit_cost;

        return round((float) ($agreed ?? (float) $product->unit_price * self::COST_RATIO), 2);
    }

    /** Which lifecycle stamps a given status has already earned. */
    private function stampFor(string $status, string $field, Carbon $at): ?Carbon
    {
        $reached = [
            'approved_at' => [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SENT,
                              PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_RECEIVED],
            'sent_at'     => [PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                              PurchaseOrder::STATUS_RECEIVED],
            'received_at' => [PurchaseOrder::STATUS_RECEIVED],
        ][$field] ?? [];

        return in_array($status, $reached, true) ? $at : null;
    }

    /**
     * received_quantity per line: a received order takes its whole quantity
     * minus anything in `short`; a partially received order takes `partial`.
     * Nothing here writes stock — see the note at the top of the class.
     */
    private function receivedFor(string $status, int $quantity, array $spec, string $sku): int
    {
        if ($status === PurchaseOrder::STATUS_PARTIALLY_RECEIVED) {
            return $spec['partial'][$sku] ?? 0;
        }

        if ($status !== PurchaseOrder::STATUS_RECEIVED) {
            return 0;
        }

        return max(0, $quantity - ($spec['short'][$sku] ?? 0));
    }

    /** updated_at = the last event the order has lived through. */
    private function lastTouch(string $status, Carbon $orderDate, Carbon $approved, Carbon $sent, Carbon $received): Carbon
    {
        return match ($status) {
            PurchaseOrder::STATUS_RECEIVED,
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED => $received,
            PurchaseOrder::STATUS_SENT               => $sent,
            PurchaseOrder::STATUS_APPROVED,
            PurchaseOrder::STATUS_REJECTED,
            PurchaseOrder::STATUS_CANCELLED          => $approved,
            default                                  => $orderDate,
        };
    }

    private function backdate(string $table, int $id, Carbon $createdAt, Carbon $updatedAt): void
    {
        DB::table($table)->where('id', $id)->update([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);
    }
}
