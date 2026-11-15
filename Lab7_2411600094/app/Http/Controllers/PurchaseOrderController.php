<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Lab 7 Part 2: the purchase order system.
 *
 * Every action in this controller is a state transition on one order, so each
 * method follows the same beats:
 *   1. authorize('view')       — may this person see the order at all?
 *   2. $order->canBe...()      — does the order's status make the action mean
 *                              anything right now? (friendly flash, not a 403)
 *   3. authorize($ability)     — is this person allowed to ask for this move?
 *   4. DB::transaction         — header + lines + ledger rows commit together
 *   5. the status changes      — and that is the last this class says about it:
 *                              PurchaseOrderObserver turns the change into the
 *                              history entry and the mail (Lab 7 Part 4), so an
 *                              engine, a queue or a future API route gets the
 *                              same treatment as the button.
 *
 * The status check sits between the two authorizations on purpose: the policy
 * also refuses a wrongly-timed action, so asking it first would turn every
 * honest double-click into a bare "403 Unauthorized" page.
 *
 * Money and stock are never trusted from the browser: totals are recomputed
 * from the stored lines, and receiving only writes InventoryTransaction rows
 * and lets the Lab 6 observer move the stock.
 */
class PurchaseOrderController extends Controller
{
    /* ==================================================================
       index
       ================================================================== */

    public function index(Request $request, SearchService $search)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        // Lab 7 Part 7: reference text, supplier, status, date range, amount
        // range and who raised it are all SearchService's doing now; the page
        // keeps its own page size and its newest-first default.
        $orders = $search->searchPurchaseOrders($request->all())
            ->paginate(10)
            ->withQueryString();

        return view('purchase-orders.index', [
            'orders'    => $orders,
            'suppliers' => Supplier::withTrashed()->orderBy('name')->get(),
            'users'     => User::orderBy('name')->get(['id', 'name']),
            'statuses'  => PurchaseOrder::STATUSES,
            'counts'    => PurchaseOrder::query()
                            ->selectRaw('status, count(*) as aggregate')
                            ->groupBy('status')
                            ->pluck('aggregate', 'status')
                            ->toArray(),
            'total'     => PurchaseOrder::count(),
        ]);
    }

    /* ==================================================================
       create / store
       ================================================================== */

    public function create()
    {
        $this->authorize('create', PurchaseOrder::class);

        return view('purchase-orders.create', $this->formOptions() + [
            'order' => new PurchaseOrder([
                'order_date'             => now()->toDateString(),
                'expected_delivery_date' => now()
                    ->addDays((int) config('procurement.default_lead_time_days'))
                    ->toDateString(),
                'status'                 => PurchaseOrder::STATUS_DRAFT,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $validated = $request->validate($this->rules(), $this->messages());

        // "Save and submit" is one button that skips the draft stage; the
        // approval itself still has to come from somebody else.
        $submitNow = $request->input('action') === 'submit';

        $order = DB::transaction(function () use ($validated, $request, $submitNow): PurchaseOrder {
            $order = PurchaseOrder::create([
                'reference_number'       => PurchaseOrder::generateReferenceNumber($validated['order_date']),
                'supplier_id'            => $validated['supplier_id'],
                // Even "save and submit" starts as a draft, and the move to
                // pending is a separate update: that is what lets the observer
                // see a real transition instead of guessing from a create.
                'status'                 => PurchaseOrder::STATUS_DRAFT,
                'order_date'             => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'notes'                  => $validated['notes'] ?? null,
                'terms'                  => $validated['terms'] ?? null,
                'created_by'             => $request->user()->id,
            ]);

            $this->syncItems($order, $validated['items']);

            $order->logActivity('created', sprintf(
                'Purchase order %s for %s was created with %d line(s).',
                $order->reference_number,
                $order->supplier->name,
                count($validated['items'])
            ), ['total' => (float) $order->total]);

            if ($submitNow) {
                $order->update(['status' => PurchaseOrder::STATUS_PENDING]);
            }

            return $order;
        });

        return redirect()
            ->route('purchase-orders.show', $order)
            ->with('success', sprintf(
                'Purchase order %s saved as %s. Total PHP %s.',
                $order->reference_number,
                $order->statusLabel(),
                number_format((float) $order->total, 2)
            ));
    }

    /* ==================================================================
       show
       ================================================================== */

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load([
            'supplier',
            'items.product',
            'creator',
            'approver',
            'rejector',
        ])->loadCount('items');

        return view('purchase-orders.show', [
            'order'      => $purchaseOrder,
            'activities' => $purchaseOrder->activities()->with('user')->get(),
            // The receive form only ever asks for what is still outstanding.
            'outstanding' => $purchaseOrder->items->sum('outstanding_quantity'),
        ]);
    }

    /* ==================================================================
       edit / update  (draft only)
       ================================================================== */

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        if (! $purchaseOrder->canBeEdited()) {
            return redirect()
                ->route('purchase-orders.show', $purchaseOrder)
                ->with('error', sprintf(
                    '%s is %s, so it can no longer be edited. Only a draft changes.',
                    $purchaseOrder->reference_number,
                    $purchaseOrder->statusLabel()
                ));
        }

        $this->authorize('update', $purchaseOrder);

        $purchaseOrder->load('items.product');

        return view('purchase-orders.edit', $this->formOptions($purchaseOrder) + [
            'order' => $purchaseOrder,
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        if (! $purchaseOrder->canBeEdited()) {
            return redirect()
                ->route('purchase-orders.show', $purchaseOrder)
                ->with('error', sprintf(
                    '%s is %s and can no longer be edited.',
                    $purchaseOrder->reference_number,
                    $purchaseOrder->statusLabel()
                ));
        }

        $this->authorize('update', $purchaseOrder);

        $validated = $request->validate($this->rules(), $this->messages());

        $submitNow = $request->input('action') === 'submit';

        DB::transaction(function () use ($purchaseOrder, $validated, $submitNow): void {
            $purchaseOrder->fill([
                'supplier_id'            => $validated['supplier_id'],
                'order_date'             => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'notes'                  => $validated['notes'] ?? null,
                'terms'                  => $validated['terms'] ?? null,
            ]);

            // The reference number is the order's identity, so it is rewritten
            // only when the order date actually moves to another day.
            if ($purchaseOrder->isDirty('order_date')) {
                $purchaseOrder->reference_number = PurchaseOrder::generateReferenceNumber($validated['order_date']);
            }

            $this->syncItems($purchaseOrder, $validated['items']);

            if ($submitNow) {
                $purchaseOrder->status = PurchaseOrder::STATUS_PENDING;
            }

            $purchaseOrder->save();

            $purchaseOrder->logActivity('updated', sprintf(
                '%s was edited: %d line(s), total PHP %s.',
                $purchaseOrder->reference_number,
                count($validated['items']),
                number_format((float) $purchaseOrder->total, 2)
            ), ['total' => (float) $purchaseOrder->total]);
        });

        return redirect()
            ->route('purchase-orders.show', $purchaseOrder)
            ->with('success', sprintf('%s updated.', $purchaseOrder->reference_number));
    }

    /* ==================================================================
       workflow transitions
       ================================================================== */

    /** draft -> pending */
    public function submit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        if (! $purchaseOrder->canBeSubmitted()) {
            return $this->stateConflict($purchaseOrder, 'submitted for approval', 'a draft');
        }

        $this->authorize('submit', $purchaseOrder);

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_PENDING]);

        return back()->with('success', sprintf(
            '%s is now pending approval. A manager or administrator who did not raise it can approve it.',
            $purchaseOrder->reference_number
        ));
    }

    /** pending -> approved */
    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        if (! $purchaseOrder->canBeApproved()) {
            return $this->stateConflict($purchaseOrder, 'approved', 'pending approval');
        }

        $this->authorize('approve', $purchaseOrder);

        $purchaseOrder->update([
            'status'      => PurchaseOrder::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        // PurchaseOrderObserver writes the history row and mails the creator.
        // The status gate above already guarantees this is a real approval, and
        // wasChanged('status') there guarantees a second click sends nothing.

        return back()->with('success', sprintf('%s approved.', $purchaseOrder->reference_number));
    }

    /** pending -> rejected, reason required */
    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        if (! $purchaseOrder->canBeRejected()) {
            return $this->stateConflict($purchaseOrder, 'rejected', 'pending approval');
        }

        $this->authorize('reject', $purchaseOrder);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'rejection_reason.required' => 'Say why the order is rejected — the buyer has to know what to fix.',
            'rejection_reason.min'      => 'Give at least a short reason (5 characters or more).',
        ]);

        $purchaseOrder->update([
            'status'           => PurchaseOrder::STATUS_REJECTED,
            'rejected_by'      => $request->user()->id,
            'rejected_at'      => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        // The observer records the rejection from rejection_reason, which is why
        // the reason is saved in the same update rather than only announced in
        // the flash message. Part 4's four mailables do not include a rejection
        // mail — the buyer sees it on the order's own history.

        return back()->with('warning', sprintf('%s rejected.', $purchaseOrder->reference_number));
    }

    /** approved -> sent */
    public function send(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        if (! $purchaseOrder->canBeSent()) {
            return $this->stateConflict($purchaseOrder, 'sent to the supplier', 'an approved order');
        }

        $this->authorize('send', $purchaseOrder);

        $purchaseOrder->update([
            'status'  => PurchaseOrder::STATUS_SENT,
            'sent_at' => now(),
        ]);

        // The observer mails the vendor the order document and copies the buyer.

        return back()->with('success', sprintf(
            '%s marked as sent to %s.',
            $purchaseOrder->reference_number,
            $purchaseOrder->supplier->name
        ));
    }

    /** draft / pending / approved -> cancelled, reason required */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        if (! $purchaseOrder->canBeCancelled()) {
            return $this->stateConflict($purchaseOrder, 'cancelled', 'a draft, pending or approved order');
        }

        $this->authorize('cancel', $purchaseOrder);

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'cancellation_reason.required' => 'A cancelled order must say why — it is the only record of the decision.',
            'cancellation_reason.min'      => 'Give at least a short reason (5 characters or more).',
        ]);

        // No cancellation_reason column exists, so the decision is kept where
        // it belongs: appended to the order's own notes and, in full, in the
        // activity log the show page renders as the approval history.
        $note = sprintf('[Cancelled %s by %s] %s',
            now()->format('M j, Y'),
            $request->user()->name,
            $validated['cancellation_reason']
        );

        // The reason travels to the observer as context: notes keep the human
        // sentence, the history row keeps the same wording it had in Part 2.
        $purchaseOrder
            ->withActivityContext(['reason' => $validated['cancellation_reason']])
            ->update([
                'status' => PurchaseOrder::STATUS_CANCELLED,
                'notes'  => trim(($purchaseOrder->notes ? $purchaseOrder->notes . "\n" : '') . $note),
            ]);

        return back()->with('warning', sprintf('%s cancelled.', $purchaseOrder->reference_number));
    }

    /* ==================================================================
       receive  (the only place stock moves)
       ================================================================== */

    /**
     * sent / partially_received -> partially_received / received.
     *
     * One transaction, header row locked for the whole of it, so two warehouse
     * staff cannot both receive the same last carton. Nothing here touches
     * products.quantity: each received line becomes an InventoryTransaction of
     * type 'in', and the Lab 6 observer is what actually moves the stock and
     * raises low-stock alerts — the ledger stays the single source of truth.
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        if (! $purchaseOrder->canBeReceived()) {
            return $this->stateConflict($purchaseOrder, 'received', 'a sent or partially received order');
        }

        $this->authorize('receive', $purchaseOrder);

        $validated = $request->validate([
            'received'   => ['required', 'array'],
            'received.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ], [
            'received.required' => 'Enter how many units of each line arrived.',
            'received.*.min'    => 'A received quantity cannot be negative.',
        ]);

        $received = $validated['received'];

        $summary = DB::transaction(function () use ($purchaseOrder, $received, $request): array {
            // Lock the header, then read the lines: a concurrent receive waits
            // here instead of double-counting the same carton.
            $locked = PurchaseOrder::whereKey($purchaseOrder->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $locked->load('items.product');

            $moved   = [];
            $lineLog = [];

            foreach ($locked->items as $item) {
                $qty = (int) ($received[$item->id] ?? 0);

                if ($qty > $item->outstanding_quantity) {
                    throw ValidationException::withMessages([
                        "received.{$item->id}" => sprintf(
                            '"%s": only %d of %d unit(s) are still outstanding on this line.',
                            $item->product->name,
                            $item->outstanding_quantity,
                            $item->quantity
                        ),
                    ]);
                }

                if ($qty === 0) {
                    continue;
                }

                $item->received_quantity += $qty;
                $item->save();

                // Stock is never written here: the ledger row is the request,
                // and the Lab 6 observer is what moves products.quantity.
                InventoryTransaction::create([
                    'product_id'       => $item->product_id,
                    'type'             => InventoryTransaction::TYPE_IN,
                    'quantity'         => $qty,
                    'reference_number' => $locked->reference_number,
                    'notes'            => "Received against {$locked->reference_number}",
                    'user_id'          => $request->user()->id,
                ]);

                $moved  [] = $item->product->name;
                $lineLog[] = sprintf('%s x%d', $item->product->name, $qty);
            }

            if ($moved === []) {
                throw ValidationException::withMessages([
                    'received' => 'Nothing was received — enter a quantity on at least one line.',
                ]);
            }

            // Complete when every line has its full quantity on the shelf.
            $stillOutstanding = $locked->items->sum('outstanding_quantity');

            $locked
                // What arrived and what is still owed cannot be read back out of
                // a status column, so it goes to the observer as context and the
                // observer owns the history row.
                ->withActivityContext([
                    'lines'       => $lineLog,
                    'outstanding' => $stillOutstanding,
                ])
                ->update([
                    'status'      => $stillOutstanding === 0
                                        ? PurchaseOrder::STATUS_RECEIVED
                                        : PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                    'received_at' => $stillOutstanding === 0 ? now() : null,
                ]);

            return ['count' => count($moved), 'outstanding' => $stillOutstanding];
        });

        // Refresh the instance the view will re-render from.
        $purchaseOrder->refresh()->load('items.product');

        return back()->with('success', $summary['outstanding'] === 0
            ? sprintf('%s fully received — %d line(s) added to stock.', $purchaseOrder->reference_number, $summary['count'])
            : sprintf('%s partially received: %d line(s) in, %d unit(s) still outstanding.',
                $purchaseOrder->reference_number, $summary['count'], $summary['outstanding'])
        );
    }

    /* ==================================================================
       destroy  (soft delete, draft only, admin only)
       ================================================================== */

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        if (! $purchaseOrder->canBeEdited()) {
            return $this->stateConflict($purchaseOrder, 'deleted', 'a draft');
        }

        $this->authorize('delete', $purchaseOrder);

        $reference = $purchaseOrder->reference_number;

        // Soft delete; the observer records it and drops the stale counts.
        $purchaseOrder->delete();

        return redirect()
            ->route('purchase-orders.index')
            ->with('success', "Purchase order {$reference} deleted — it stays in the database and an admin can restore it.");
    }

    /* ==================================================================
       AJAX: the products a supplier can actually sell us
       ================================================================== */

    /**
     * Backs the line-item picker: only products this vendor holds a price
     * agreement for, or lists as their primary supplier. `unit_cost` is the
     * agreed purchase price and pre-fills the line; `unit_price` is the
     * fallback for a primary supplier with no agreement on file.
     */
    public function supplierProducts(Supplier $supplier): JsonResponse
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $products = Product::query()
            ->where(function ($query) use ($supplier) {
                $query->where('supplier_id', $supplier->id)
                      ->orWhereHas('suppliers', fn ($q) => $q->where('suppliers.id', $supplier->id));
            })
            ->with(['suppliers' => fn ($query) => $query->where('suppliers.id', $supplier->id)])
            ->orderBy('name')
            ->get()
            ->map(function (Product $product): array {
                // The constrained eager load leaves at most one row: this
                // supplier's price agreement for the item, or none at all.
                $agreement = $product->suppliers->first()?->pivot;

                return [
                    'id'            => $product->id,
                    'name'          => $product->name,
                    'sku'           => $product->sku,
                    'stock'         => $product->quantity,
                    'reorder_level' => $product->reorder_level,
                    'unit_cost'     => $agreement?->unit_cost !== null ? (float) $agreement->unit_cost : null,
                    'unit_price'    => (float) $product->unit_price,
                    'min_order'     => (int) ($agreement?->minimum_order_quantity ?? 1),
                    'package_size'  => (int) ($agreement?->package_size ?? 1),
                ];
            });

        return response()->json(['data' => $products]);
    }

    /* ==================================================================
       helpers
       ================================================================== */

    /**
     * Replaces an order's lines with the submitted ones. Drafts only, so no
     * received_quantity can be lost.
     *
     * @param  array<int, array{product_id: int, quantity: int, unit_price: float|string}>  $items
     */
    private function syncItems(PurchaseOrder $order, array $items): void
    {
        $order->items()->delete();

        foreach ($items as $line) {
            $order->items()->create([
                'product_id' => $line['product_id'],
                'quantity'   => (int) $line['quantity'],
                'unit_price' => round((float) $line['unit_price'], 2),
            ]);
        }

        // Server-side arithmetic: the browser's total is a preview only.
        $order->load('items')->calculateTotal()->save();
    }

    /** Friendly refusal when the order is in the wrong state. */
    private function stateConflict(PurchaseOrder $order, string $verb, string $requiredState)
    {
        return back()->with('error', sprintf(
            '%s is %s, so it cannot be %s — that action needs %s.',
            $order->reference_number,
            $order->statusLabel(),
            $verb,
            $requiredState
        ));
    }

    /** Shared by create and edit. */
    private function formOptions(?PurchaseOrder $order = null): array
    {
        $suppliers = Supplier::active()->orderBy('name')->get();

        // An order already raised against a supplier that has since been
        // retired must still show that supplier, or the form would silently
        // move the order to a different vendor.
        if ($order && $order->supplier && ! $order->supplier->is_active) {
            $suppliers->prepend($order->supplier);
        }

        return [
            'suppliers' => $suppliers,
            'taxRate'   => (float) config('procurement.tax_rate'),
        ];
    }

    private function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id'),
                function (string $attribute, $value, callable $fail): void {
                    $supplier = Supplier::find($value);

                    if ($supplier && ! $supplier->is_active) {
                        $fail(sprintf('"%s" is marked inactive, so no new order can be raised against it.', $supplier->name));
                    }
                }],
            'order_date'             => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes'                  => ['nullable', 'string', 'max:2000'],
            'terms'                  => ['nullable', 'string', 'max:2000'],

            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'integer', 'distinct', Rule::exists('products', 'id')],
            'items.*.quantity'        => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    private function messages(): array
    {
        return [
            'supplier_id.required'             => 'Choose which supplier the order is raised against.',
            'supplier_id.exists'               => 'That supplier is not in the supplier file.',
            'order_date.required'              => 'Enter the date the order is dated.',
            'expected_delivery_date.after_or_equal'
                                              => 'The expected delivery date cannot be earlier than the order date.',
            'items.required'                   => 'A purchase order needs at least one product line.',
            'items.min'                        => 'A purchase order needs at least one product line.',
            'items.*.product_id.required'      => 'Choose a product for every line.',
            'items.*.product_id.distinct'      => 'The same product appears twice on one order — put it on a single line instead.',
            'items.*.product_id.exists'        => 'One of the lines points at a product that no longer exists.',
            'items.*.quantity.required'        => 'Enter how many units you are ordering.',
            'items.*.quantity.integer'         => 'Order quantities are whole units.',
            'items.*.quantity.min'             => 'Order at least one unit on every line.',
            'items.*.unit_price.required'      => 'Every line needs the price agreed with the supplier.',
            'items.*.unit_price.numeric'       => 'The unit price must be a number.',
            'items.*.unit_price.min'           => 'A unit price cannot be negative.',
        ];
    }
}
