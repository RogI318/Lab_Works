<?php
// LAB 6, PART 8: Stock adjustments
// Created by: php artisan make:controller AdjustmentController
// app/Http/Controllers/AdjustmentController.php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdjustmentController extends Controller
{
    private array $reasons = ['Physical count', 'Damaged goods', 'Theft or loss', 'System error'];

    // create() - show the adjustment form
    public function create(Request $request)
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'sku']);
        $reasons = $this->reasons;
        $selected = $request->product_id;

        return view('adjustments.create', compact('products', 'reasons', 'selected'));
    }

    // store() - save an adjustment as a transaction with the difference
    public function store(Request $request)
    {
        $data = $this->validateAdjustment($request);

        $diff = DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            // physical count minus system count
            $diff = $data['physical_count'] - $product->quantity;

            if ($diff !== 0) {
                // The observer updates the quantity
                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'adjustment',
                    'quantity' => $diff,
                    'reason' => $data['reason'],
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            return $diff;
        });

        if ($diff === 0) {
            return back()->with('success', 'Counts already match. No adjustment needed.');
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Adjustment recorded (' . ($diff > 0 ? '+' : '') . $diff . ').');
    }

    // Validation rules for the adjustment form
    private function validateAdjustment(Request $request): array
    {
        return $request->validate([
            'product_id' => 'required|exists:products,id',
            'physical_count' => 'required|integer|min:0',
            'reason' => 'required|in:' . implode(',', $this->reasons),
            'notes' => 'nullable|string|max:500',
        ], [
            'product_id.required' => 'Please choose a product.',
            'physical_count.required' => 'Please enter the physical count.',
            'physical_count.min' => 'Physical count cannot be negative.',
            'reason.in' => 'Please choose a valid reason.',
        ]);
    }
}
