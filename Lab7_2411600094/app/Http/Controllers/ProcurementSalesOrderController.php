<?php

namespace App\Http\Controllers;

use App\Mail\SalesOrderDelivered;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Notifications\SalesOrderCreatedNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Exceptions\InsufficientStockException;
use Throwable;

class ProcurementSalesOrderController extends Controller
{
    use AuthorizesRequests; // gives us $this->authorize()

    /** List: search by reference / customer, filter by status. Everyone logged in can look. */
    public function index(Request $request)
    {
        $orders = SalesOrder::query()
            ->with('createdBy')
            ->withCount('items')
            ->when($request->filled('search'), function ($query) use ($request) {
                $like = '%' . $request->input('search') . '%';
                $query->where(fn ($q) => $q->where('reference_number', 'like', $like)
                    ->orWhere('customer_name', 'like', $like));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('procurement.sales-orders.index', compact('orders'));
    }

    public function create()
    {
        $this->authorize('manage-stock');

        return view('procurement.sales-orders.create', ['productOptions' => $this->productOptions()]);
    }

    /** Save as draft, or save and confirm right away (button "action") */
    public function store(Request $request)
    {
        $this->authorize('manage-stock');

        $data = $this->validated($request);
        $status = $request->input('action') === 'confirm' ? 'confirmed' : 'draft';

        $order = DB::transaction(function () use ($data, $status) {
            $order = SalesOrder::create([
                'reference_number' => SalesOrder::generateReferenceNumber(),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'status' => $status,
                'order_date' => $data['order_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->saveItems($order, $data['items']);
            $order->calculateTotal();

            return $order;
        });

        // Tell the admins about the new Sales Order (a failed alert must never stop the order)
        try {
            Notification::send(
                User::admins()->get(),
                new SalesOrderCreatedNotification($order)
            );
        } catch (Throwable $e) {
            report($e);
        }

        return redirect()->route('procurement.sales-orders.show', $order)
            ->with('success', "Sales order {$order->reference_number} was created as {$status}.");
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['items.product', 'createdBy']);

        return view('procurement.sales-orders.show', ['order' => $salesOrder]);
    }

    /** Printable receipt: a clean page, the browser's print dialog opens by itself */
    public function print(SalesOrder $salesOrder)
    {
        $salesOrder->load(['items.product', 'createdBy']);

        return view('procurement.sales-orders.print', ['order' => $salesOrder]);
    }

    public function edit(SalesOrder $salesOrder)
    {
        $this->authorize('manage-stock');

        if (! $salesOrder->isDraft()) {
            return redirect()->route('procurement.sales-orders.show', $salesOrder)
                ->with('error', 'Only draft sales orders can be edited.');
        }

        $salesOrder->load('items.product');

        return view('procurement.sales-orders.edit', [
            'order' => $salesOrder,
            'productOptions' => $this->productOptions(),
        ]);
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        $this->authorize('manage-stock');

        if (! $salesOrder->isDraft()) {
            return redirect()->route('procurement.sales-orders.show', $salesOrder)
                ->with('error', 'Only draft sales orders can be edited.');
        }

        $data = $this->validated($request);

        DB::transaction(function () use ($salesOrder, $data) {
            $salesOrder->update([
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'order_date' => $data['order_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->saveItems($salesOrder, $data['items']);
            $salesOrder->calculateTotal();
        });

        return redirect()->route('procurement.sales-orders.show', $salesOrder)
            ->with('success', 'Sales order updated successfully.');
    }

    /** draft -> confirmed */
    public function confirm(SalesOrder $salesOrder)
    {
        $this->authorize('manage-stock');

        if (! $salesOrder->canBeConfirmed()) {
            return back()->with('error', 'Only draft sales orders can be confirmed.');
        }

        $salesOrder->update(['status' => 'confirmed']);

        return back()->with('success', "Sales order {$salesOrder->reference_number} was confirmed.");
    }

    /**
     * confirmed -> delivered. Creates a Stock Out transaction for every line
     * (the InventoryTransactionObserver lowers the stock and blocks it if there is not enough).
     */
    public function deliver(SalesOrder $salesOrder)
    {
        $this->authorize('manage-stock');

        if (! $salesOrder->canBeDelivered()) {
            return back()->with('error', 'Only confirmed sales orders can be delivered.');
        }

        $salesOrder->load('items.product');

        try {
            DB::transaction(function () use ($salesOrder) {
                foreach ($salesOrder->items as $item) {
                    InventoryTransaction::create([
                        'product_id' => $item->product_id,
                        'type' => InventoryTransaction::TYPE_OUT,
                        'quantity' => $item->quantity,
                        'reference_number' => $salesOrder->reference_number,
                        'reason' => 'Sales order delivery',
                        'notes' => "Delivered to {$salesOrder->customer_name}",
                        'user_id' => Auth::id(),
                    ]);
                }

                $salesOrder->update(['status' => 'delivered', 'delivered_at' => now()]);
            });
        } catch (InsufficientStockException $e) {
            // Not enough stock: everything is rolled back, nothing was deducted
            return back()->with('error', $e->getMessage());
        }

        $emailNote = '';

        if ($salesOrder->customer_email) {
            try {
                Mail::to($salesOrder->customer_email)->send(new SalesOrderDelivered($salesOrder));
                $emailNote = " A delivery email was sent to {$salesOrder->customer_email}.";
            } catch (Throwable $e) {
                report($e); // a failed email must never undo a delivery
                $emailNote = ' The order was delivered, but the email could not be sent.';
            }
        }

        return back()->with('success', "Sales order {$salesOrder->reference_number} was delivered and the stock was reduced.{$emailNote}");
    }

    /** draft or confirmed -> cancelled (nothing was deducted yet, so no stock to give back) */
    public function cancel(SalesOrder $salesOrder)
    {
        $this->authorize('manage-stock');

        if (! $salesOrder->canBeCancelled()) {
            return back()->with('error', 'This sales order can no longer be cancelled.');
        }

        $salesOrder->update(['status' => 'cancelled']);

        return back()->with('success', "Sales order {$salesOrder->reference_number} was cancelled.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'order_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|distinct|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'customer_name.required' => 'Please enter the customer name.',
            'customer_email.email' => 'Please enter a valid email address.',
            'customer_phone.regex' => 'Phone number may only have digits, spaces, +, - and parentheses (7 to 20 characters).',
            'order_date.required' => 'Please enter the order date.',
            'items.required' => 'Add at least one line item.',
            'items.min' => 'Add at least one line item.',
            'items.*.product_id.required' => 'Please choose a product for every line.',
            'items.*.product_id.distinct' => 'The same product is added twice. Combine them into one line.',
            'items.*.quantity.required' => 'Please enter the quantity for every line.',
            'items.*.quantity.integer' => 'Quantity must be a whole number.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.unit_price.required' => 'Please enter the unit price for every line.',
            'items.*.unit_price.numeric' => 'Unit price must be a number.',
        ]);
    }

    /** Replace all lines of the order with the submitted ones */
    private function saveItems(SalesOrder $order, array $items): void
    {
        $order->items()->delete();

        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'], // the price is saved on the line at the time of the order
            ]);
        }
    }

    /** Data for the product dropdown of the form (JavaScript fills the price and shows the stock) */
    private function productOptions(): array
    {
        return Product::orderBy('name')->get()->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => (float) $product->unit_price,
            'stock' => (int) $product->quantity,
        ])->all();
    }
}