<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Lab 6 Part 8: cycle counts. The form lives in the Transactions page modal,
 * the user states what is physically on the shelf, and the difference becomes
 * an 'adjustment' ledger row that the observer applies — this controller never
 * writes products.quantity itself.
 */
class StockAdjustmentController extends Controller
{
    /** Step 2 — the read-only "current system quantity" field in the modal. */
    public function quantity(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAdjustment($request);

        return response()->json([
            'data' => [
                'id'            => $product->id,
                'name'          => $product->name,
                'sku'           => $product->sku,
                'quantity'      => $product->quantity,
                'reorder_level' => $product->reorder_level,
                'stock_status'  => $product->stock_status,
            ],
        ]);
    }

    /** Step 3 — difference -> adjustment transaction -> observer updates stock. */
    public function store(Request $request)
    {
        $this->authorizeAdjustment($request);

        $validator = Validator::make($request->all(), [
            'product_id'     => ['required', 'integer', Rule::exists('products', 'id')],
            'physical_count' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason'         => ['required', Rule::in(array_keys(InventoryTransaction::REASONS))],
            'notes'          => ['required_if:reason,other', 'string', 'max:1000'],
        ], [
            'physical_count.min' => 'A physical count cannot be negative.',
            'reason.required'    => 'Choose why the count differs.',
            'notes.required_if'  => 'Explain the adjustment in Notes when the reason is "Other".',
        ]);

        // Explicit target instead of back(): the modal's AJAX lookup for the
        // system quantity is the last request the browser made, so "back"
        // would land on the JSON endpoint rather than the ledger page.
        if ($validator->fails()) {
            return redirect()
                ->route('transactions.index', ['adjust' => $request->integer('product_id')])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $product    = Product::findOrFail($validated['product_id']);
        $difference = $validated['physical_count'] - $product->quantity;

        if ($difference === 0) {
            return redirect()
                ->route('transactions.index', ['adjust' => $product->id])
                ->with('info', "\"{$product->name}\" already shows {$product->quantity} unit(s) — nothing to adjust.");
        }

        $before = $product->quantity;

        try {
            $transaction = DB::transaction(fn () => InventoryTransaction::create([
                'product_id'       => $product->id,
                'type'             => InventoryTransaction::TYPE_ADJUSTMENT,
                // signed on purpose: the observer passes it straight through
                'quantity'         => $difference,
                'reference_number' => 'ADJ-' . now()->format('Ymd-His'),
                'reason'           => $validated['reason'],
                'notes'            => $validated['notes'] ?? null,
                'user_id'          => $request->user()->id,
            ]));
        } catch (InsufficientStockException $e) {
            // Stock moved between page load and submit; re-count and retry.
            return redirect()
                ->route('transactions.index', ['adjust' => $product->id])
                ->withInput()
                ->with('error', $e->getMessage());
        }

        // Stay on the ledger list so the new adjustment row is immediately
        // visible — the modal is part of that page now.
        return redirect()
            ->route('transactions.index')
            ->with('success', "Adjustment recorded for \"{$product->name}\": "
                . "{$before} → {$product->quantity} unit(s) "
                . '(' . ($difference > 0 ? '+' : '') . $difference . '), reference '
                . $transaction->reference_number . '.');
    }

    /** "Authorized users" = the admin flag added in Part 6. */
    private function authorizeAdjustment(Request $request): void
    {
        abort_unless($request->user()->is_admin, 403, 'Only admins may adjust stock.');
    }
}
