<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Lab 6 Part 3: the ledger UI behind the observer. Stock is never touched
 * here directly — creating/deleting a row lets the observer re-value it.
 */
class InventoryTransactionController extends Controller
{
    /** Step 2 — index: 20 per page, eager loaded, filterable. */
    public function index(Request $request)
    {
        $transactions = InventoryTransaction::query()
            ->with(['product', 'user'])
            ->when($request->product_id, fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            // The filter dropdown only needs the names; the Part 8 adjustment
            // modal (included in the same page) also reads quantity/reorder level.
            'products'     => Product::orderBy('name')
                ->get(['id', 'name', 'sku', 'quantity', 'reorder_level']),
            'reasons'      => InventoryTransaction::REASONS,
        ]);
    }

    /** Step 2 — create form: pass products and the transaction types. */
    public function create()
    {
        return view('transactions.create', [
            // stock_status / stock_status_color are appended so the JavaScript
            // stock hint on the form can read them straight from the page.
            'products' => Product::orderBy('name')
                ->get(['id', 'name', 'sku', 'quantity', 'reorder_level'])
                ->append('stock_status', 'stock_status_color'),
            // Manual ledger entries are in/out only; 'adjustment' rows come
            // from the dedicated Part 8 stock-adjustment feature.
            'types'    => [
                InventoryTransaction::TYPE_IN  => 'Stock In',
                InventoryTransaction::TYPE_OUT => 'Stock Out',
            ],
        ]);
    }

    /** Step 2 + Step 3 — validate, then let the observer move the stock. */
    public function store(Request $request)
    {
        $validated = $this->makeValidator($request)->validate();

        $product = Product::findOrFail($validated['product_id']);

        try {
            // DB::transaction ties the ledger row and the stock update
            // together: if the observer throws, the INSERT is rolled back.
            $transaction = DB::transaction(fn () => InventoryTransaction::create([
                'product_id'       => $validated['product_id'],
                'type'             => $validated['type'],
                'quantity'         => $validated['quantity'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'user_id'          => $request->user()->id,
            ]));
        } catch (InsufficientStockException $e) {
            // Reached only on a race the pre-validation could not see
            // (stock drained between form load and submit).
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', "{$transaction->typeLabel()} of {$transaction->quantity} unit(s) "
                . "recorded for \"{$product->name}\".");
    }

    /** Step 2 — show: the movement, its product, and who made it. */
    public function show(InventoryTransaction $transaction)
    {
        $transaction->load(['product.category', 'product.brand', 'user']);

        return view('transactions.show', compact('transaction'));
    }

    /**
     * Step 2 — destroy (admin only). Deleting reverses the stock impact:
     * a ledger entry is a fact about the movement, so removing it must put
     * the quantity back the way it was before the movement (observer's
     * deleted() does the reversal inside one DB transaction).
     */
    public function destroy(Request $request, InventoryTransaction $transaction)
    {
        abort_unless($request->user()->is_admin, 403, 'Only admins may delete transactions.');

        DB::transaction(fn () => $transaction->delete());

        return redirect()
            ->route('transactions.index')
            ->with('success', "Transaction #{$transaction->id} ({$transaction->typeLabel()}) "
                . 'was reversed and deleted.');
    }

    /* ---------- Step 3: validation ---------- */

    /**
     * Analysis answer: the sufficient-stock check needs BOTH product_id and
     * quantity, so it goes in a Validator::after() hook (a per-field Rule
     * class would have to re-read the other field from the request anyway).
     * The observer's creating() guard stays as the race-proof last line.
     */
    private function makeValidator(Request $request): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'product_id'       => ['required', Rule::exists('products', 'id')],
            'type'             => ['required', Rule::in(array_keys($this->typeOptions()))],
            'quantity'         => ['required', 'integer', 'min:1', 'max:1000000'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ], [
            'product_id.exists' => 'The selected product no longer exists.',
            'type.in'           => 'Choose either Stock In or Stock Out.',
            'quantity.min'      => 'Quantity must be at least 1.',
        ])->after(function ($validator) use ($request) {
            if ($request->input('type') !== InventoryTransaction::TYPE_OUT) {
                return;
            }

            $product = Product::find($request->input('product_id'));
            $wanted  = (int) $request->input('quantity');

            if ($product && $wanted > $product->quantity) {
                $validator->errors()->add(
                    'quantity',
                    "\"{$product->name}\" only has {$product->quantity} unit(s) in stock — "
                    . "you cannot take out {$wanted}."
                );
            }
        });
    }

    private function typeOptions(): array
    {
        return [
            InventoryTransaction::TYPE_IN  => 'Stock In',
            InventoryTransaction::TYPE_OUT => 'Stock Out',
        ];
    }
}
