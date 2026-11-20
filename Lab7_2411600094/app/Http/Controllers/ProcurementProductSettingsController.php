<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProcurementProductSettingsController extends Controller
{
    public function index()
    {
        $products = Product::with('supplierRecord')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('procurement.product-settings.index', compact('products'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'package_size' => 'required|integer|min:1|max:1000000',
            'min_order_quantity' => 'required|integer|min:0|max:1000000',
            'cost_price' => 'nullable|numeric|min:0',
        ]);

        $product->update($data);

        return redirect()->route('procurement.product-settings.index')
            ->with('success', "Reorder settings for {$product->name} were saved.");
    }
}
