<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InventoryTransactionController extends Controller
{
    // ---------------------------------------------------------------
    // LAB 5 (kept): quick Stock In / Stock Out form on the product page
    // POST /products/{product}/transactions
    // ---------------------------------------------------------------
    public function storeForProduct(Request $request, Product $product)
    {
        // STEP 1: Validate the form
        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:255',
        ], [
            'type.required' => 'Please choose Stock In or Stock Out.',
            'type.in' => 'The type must be Stock In or Stock Out.',
            'quantity.required' => 'Please enter the quantity.',
            'quantity.integer' => 'The quantity must be a whole number.',
            'quantity.min' => 'The quantity must be at least 1.',
            'reference.max' => 'The reference may not be longer than 255 characters.',
        ]);

        // STEP 2: Do not allow stock out of more than what is available
        if ($validated['type'] === 'out' && $validated['quantity'] > $product->quantity) {
            return back()
                ->withErrors([
                    'quantity' => 'Not enough stock. Only ' . $product->quantity . ' unit(s) available.',
                ])
                ->withInput();
        }

        // STEP 3: Save the transaction. LAB 6: the observer now updates the
        // product quantity (and sends the low stock alert), all in one DB transaction.
        DB::transaction(function () use ($validated, $product) {
            InventoryTransaction::create([
                'product_id' => $product->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reference' => $validated['reference'] ?? null,
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Stock updated successfully!');
    }

    // ---------------------------------------------------------------
    // LAB 6: transaction list, form, details and delete
    // ---------------------------------------------------------------

    // index() - list transactions with filters
    public function index(Request $request)
    {
        $transactions = InventoryTransaction::with(['product', 'user'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->recent()
            ->paginate(20)
            ->withQueryString();

        $products = Product::orderBy('name')->get(['id', 'name']);
        $types = ['in' => 'Stock In', 'out' => 'Stock Out', 'adjustment' => 'Adjustment'];

        return view('transactions.index', compact('transactions', 'products', 'types'));
    }

    // create() - show the Stock In / Stock Out form
    public function create(Request $request)
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'quantity']);
        $selected = $request->product_id;

        return view('transactions.create', compact('products', 'selected'));
    }

    // store() - record a stock in / stock out
    public function store(Request $request)
    {
        $data = $this->validateTransaction($request);

        DB::transaction(function () use ($data) {
            InventoryTransaction::create([
                'product_id' => $data['product_id'],
                'user_id' => auth()->id(),
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        if ($request->input('return_to') === 'product') {
            return redirect()->route('products.show', $data['product_id'])
                ->with('success', 'Transaction recorded.');
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction recorded.');
    }

    // show() - details of one transaction
    public function show(InventoryTransaction $transaction)
    {
        $transaction->load(['product', 'user']);

        return view('transactions.show', compact('transaction'));
    }

    // destroy() - admins only (route has can:admin).
    // Deleting reverses the stock change, unless that would make stock negative.
    public function destroy(InventoryTransaction $transaction)
    {
        DB::transaction(function () use ($transaction) {
            $product = Product::withoutGlobalScopes()->lockForUpdate()->findOrFail($transaction->product_id);

            if ($product->quantity - $transaction->signed_quantity < 0) {
                throw ValidationException::withMessages([
                    'transaction' => 'Cannot delete: reversing this would make stock negative.',
                ]);
            }

            $product->quantity = $product->quantity - $transaction->signed_quantity;
            $product->save();

            $transaction->delete();
        });

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction deleted and stock reversed.');
    }

    private function validateTransaction(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ], [
            'product_id.required' => 'Please choose a product.',
            'product_id.exists' => 'That product does not exist.',
            'type.in' => 'Type must be Stock In or Stock Out.',
            'quantity.min' => 'Quantity must be at least 1.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->type === 'out' && $request->filled('product_id')) {
                $product = Product::find($request->product_id);

                if ($product && (int) $request->quantity > $product->quantity) {
                    $validator->errors()->add('quantity', "Only {$product->quantity} in stock.");
                }
            }
        });

        return $validator->validate();
    }
}
