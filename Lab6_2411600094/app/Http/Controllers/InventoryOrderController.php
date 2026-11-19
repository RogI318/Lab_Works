<?php
namespace App\Http\Controllers;
use App\Models\InventoryOrder;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\InventoryOrderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
class InventoryOrderController extends Controller
{
    public function index(Request $r, string $direction)
    {
        abort_unless($r->user()->canManageStock(), 403);
        abort_unless(in_array($direction, ['purchase', 'sale'], true), 404);
        $filters = $r->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', Rule::in(['pending', 'completed', 'cancelled', 'approved'])], 'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from']]);
        $orders = InventoryOrder::where('direction', $direction)->with('user')->when($filters['search'] ?? null, fn($q, $s) => $q->where(fn($w) => $w->where('order_number', 'like', "%{$s}%")->orWhere('contact_name', 'like', "%{$s}%")))->when(($filters['status'] ?? null) === 'approved', fn($q) => $q->where('status', 'pending')->whereNotNull('approved_at'))->when(($filters['status'] ?? null) && $filters['status'] !== 'approved', fn($q) => $q->where('status', $filters['status']))->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))->latest()->paginate(15)->withQueryString();
        return view('orders.index', compact('orders', 'direction', 'filters'));
    }
    public function latest(Request $r, string $direction)
    {
        abort_unless($r->user()->canManageStock(), 403);
        abort_unless(in_array($direction, ['purchase', 'sale'], true), 404);
        return response()->json(InventoryOrder::where('direction', $direction)->latest()->take(15)->get(['id', 'order_number', 'contact_name', 'status', 'approved_at', 'created_at'])->map(fn($o) => ['id' => $o->id, 'number' => $o->order_number, 'contact' => $o->contact_name, 'status' => $o->status, 'approved' => (bool) $o->approved_at, 'created' => $o->created_at?->format('M j, Y'), 'url' => route('orders.show', $o)]));
    }
    public function create(Request $r)
    {
        abort_unless($r->user()->canManageStock(), 403);
        $direction = $r->validate(['direction' => ['required', Rule::in(['purchase', 'sale'])]])['direction'];
        $products = Product::orderBy('name')->get();
        $suppliers = $direction === 'purchase' ? Supplier::where('is_active', true)->orderBy('name')->get() : collect();
        return view('orders.create', compact('direction', 'products', 'suppliers'));
    }
    public function store(Request $r)
    {
        abort_unless($r->user()->canManageStock(), 403);
        $direction = $r->input('direction');
        $v = $r->validate(['direction' => ['required', Rule::in(['purchase', 'sale'])], 'contact_name' => [$direction === 'purchase' ? 'nullable' : 'required', 'string', 'max:255'], 'supplier_id' => [$direction === 'purchase' ? 'required' : 'nullable', 'integer', 'exists:suppliers,id'], 'notes' => 'nullable|string|max:2000', 'items' => 'required|array|min:1|max:50', 'items.*.product_id' => 'required|exists:products,id|distinct', 'items.*.quantity' => 'required|integer|min:1|max:1000000', 'items.*.unit_price' => 'nullable|numeric|min:0']);
        if ($direction === 'sale') {
            foreach ($v['items'] as $index => $line) {
                $stockProduct = Product::findOrFail($line['product_id']);
                if ((int) $line['quantity'] > $stockProduct->quantity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items.' . $index . '.quantity' => sprintf('Only %d unit(s) of %s are available.', $stockProduct->quantity, $stockProduct->name),
                    ]);
                }
            }
        }
        if ($direction === 'purchase') {
            $supplier = Supplier::where('is_active', true)->findOrFail($v['supplier_id']);
            $v['contact_name'] = $supplier->name;
        } else {
            $supplier = null;
        }
        $o = DB::transaction(function () use ($v, $r, $supplier) {
            $o = InventoryOrder::create(['order_number' => 'TEMP-' . uniqid(), 'direction' => $v['direction'], 'contact_name' => $v['contact_name'], 'supplier_id' => $supplier?->id, 'notes' => $v['notes'] ?? null, 'user_id' => $r->user()->id]);
            $o->update(['order_number' => strtoupper($v['direction'] === 'purchase' ? 'PO' : 'SO') . '-' . now()->format('Y') . '-' . str_pad((string) $o->id, 6, '0', STR_PAD_LEFT)]);
            foreach ($v['items'] as $i) {
                $p = Product::findOrFail($i['product_id']);
                $o->items()->create(['product_id' => $p->id, 'quantity' => $i['quantity'], 'unit_price' => $p->unit_price]);
            }return $o;
        });
        if ($r->user()->isStaff())
            $this->notifyAdmins($o, 'created');
        return redirect()->route('orders.show', $o)->with('success', 'Order created and awaiting admin approval.');
    }
    public function quickStoreSupplier(Request $r)
    {
        abort_unless($r->user()->canManageStock(), 403);
        $data = $r->validate(['name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')], 'contact_person' => ['nullable', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:30', 'regex:/^(?:(?:\\+63|0)9[0-9]{2})[- ]?[0-9]{3}[- ]?[0-9]{4}$/']]);
        $supplier = Supplier::create($data + ['is_active' => true, 'lead_time_days' => 0]);
        return response()->json(['id' => $supplier->id, 'name' => $supplier->name]);
    }
    public function show(Request $r, InventoryOrder $order)
    {
        abort_unless($r->user()->canManageStock(), 403);
        $order->load(['items.product', 'user', 'approver']);
        $listRoute = $order->direction === 'purchase' ? 'purchase-orders.index' : 'sales-orders.index';
        return view('orders.show', compact('order', 'listRoute'));
    }
    public function receipt(Request $r, InventoryOrder $order)
    {
        abort_unless($r->user()->canManageStock(), 403);
        $order->load(['items.product', 'user', 'approver']);
        return view('orders.receipt', compact('order'));
    }
    public function status(Request $r, InventoryOrder $order)
    {
        abort_unless($r->user()->canManageStock(), 403);
        $order->loadMissing('approver');
        return response()->json(['status' => $order->status, 'approved' => (bool) $order->approved_at, 'approver' => $order->approver?->name, 'approved_at' => $order->approved_at?->format('M j, Y g:i A')]);
    }
    public function approve(Request $r, InventoryOrder $order)
    {
        abort_unless($r->user()->isAdmin(), 403);
        if ($order->status !== 'pending' || $order->approved_at)
            return back()->with('error', 'This order is not awaiting approval.');
        if ((int) $order->user_id === (int) $r->user()->id)
            return back()->with('error', 'You cannot approve your own order.');
        $order->update(['approved_by' => $r->user()->id, 'approved_at' => now()]);
        if ($order->user)
            Notification::send($order->user, new InventoryOrderNotification($order, 'approved'));
        return back()->with('success', 'Order approved.');
    }
    public function complete(Request $r, InventoryOrder $order)
    {
        abort_unless($r->user()->canManageStock(), 403);
        if ($order->status !== 'pending' || !$order->approved_at)
            return back()->with('error', 'An admin must approve this order before processing.');
        try {
            DB::transaction(function () use ($order, $r) {
                $order->load('items.product');
                foreach ($order->items as $i)
                    InventoryTransaction::create(['product_id' => $i->product_id, 'type' => $order->direction === 'purchase' ? InventoryTransaction::TYPE_IN : InventoryTransaction::TYPE_OUT, 'quantity' => $i->quantity, 'reference_number' => $order->order_number, 'notes' => ($order->direction === 'purchase' ? 'Received purchase order from ' : 'Fulfilled sales order for ') . $order->contact_name, 'user_id' => $r->user()->id]);
                $order->update(['status' => 'completed', 'completed_at' => now()]);
            });
        } catch (\App\Exceptions\InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }
        if ($r->user()->isStaff())
            $this->notifyAdmins($order, 'completed');
        return back()->with('success', 'Order processed and inventory ledger updated.');
    }
    public function cancel(Request $r, InventoryOrder $order)
    {
        abort_unless($r->user()->isAdmin(), 403);
        if ($order->status !== 'pending')
            return back()->with('error', 'Only pending orders can be cancelled.');
        $order->update(['status' => 'cancelled']);
        return back()->with('success', 'Order cancelled.');
    }
    private function notifyAdmins(InventoryOrder $order, string $event): void
    {
        try {
            Notification::send(User::admins()->get(), new InventoryOrderNotification($order, $event));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
