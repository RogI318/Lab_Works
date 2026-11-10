<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = $this->filtered($request)
                         ->with('category', 'brand')
                         ->orderBy('name')
                         ->paginate(10)
                         ->withQueryString();

        return view('products.index', [
            'products'   => $products,
            'categories' => Category::orderBy('name')->get(),
            'brands'     => Brand::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('products.create', $this->formOptions());
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $product = Product::create($validated);

        return redirect()
            ->route('products.index')
            ->with('success', "Product \"{$product->name}\" added successfully.");
    }

    public function show(Product $product)
    {
        $product->load('category', 'brand');

        $transactions = $product->transactions()
                                ->with('user')
                                ->orderByDesc('created_at')
                                ->take(15)
                                ->get();

        return view('products.show', compact('product', 'transactions'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', ['product' => $product] + $this->formOptions());
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate($this->rules($product->id), $this->messages());

        $product->update($validated);

        return redirect()
            ->route('products.index')
            ->with('success', "Product \"{$product->name}\" updated successfully.");
    }

    public function destroy(Product $product)
    {
        $name = $product->name;
        $product->delete();   // transactions are deleted with it (cascade)

        return redirect()
            ->route('products.index')
            ->with('success', "Product \"{$name}\" deleted successfully.");
    }

    /* ---------------- helpers ---------------- */

    /** Search + category / brand / stock-status filters. */
    private function filtered(Request $request)
    {
        $query = Product::query();

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                                       ->orWhere('sku', 'like', "%{$search}%")
                                       ->orWhere('supplier', 'like', "%{$search}%"));
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        match ($request->input('status')) {
            Product::STATUS_LOW_STOCK => $query->lowStock(),
            Product::STATUS_OUT_STOCK => $query->outOfStock(),
            Product::STATUS_IN_STOCK  => $query->whereColumn('quantity', '>', 'reorder_level'),
            default                   => null,
        };

        return $query;
    }

    private function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'brands'     => Brand::orderBy('name')->get(),
        ];
    }

    private function rules($ignoreId = null): array
    {
        return [
            'name'          => 'required|string|max:255',
            'sku'           => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($ignoreId)],
            'description'   => 'nullable|string|max:2000',
            'category_id'   => 'required|integer|exists:categories,id',
            'brand_id'      => 'required|integer|exists:brands,id',
            'quantity'      => 'required|integer|min:0|max:1000000',
            'reorder_level' => 'required|integer|min:0|max:1000000',
            'unit_price'    => 'required|numeric|min:0|max:99999999.99',
            'supplier'      => 'nullable|string|max:255',
        ];
    }

    private function messages(): array
    {
        return [
            'name.required'          => 'Please provide the product name.',
            'sku.required'           => 'Please provide a SKU (e.g., BND-PL-0101).',
            'sku.unique'             => 'That SKU is already used by another product.',
            'category_id.required'   => 'Please select a category for this product.',
            'category_id.exists'     => 'The selected category no longer exists.',
            'brand_id.required'      => 'Please select the brand of this product.',
            'brand_id.exists'        => 'The selected brand no longer exists.',
            'quantity.required'      => 'Please enter the quantity currently in stock.',
            'quantity.integer'       => 'Quantity must be a whole number.',
            'quantity.min'           => 'Quantity cannot be negative.',
            'reorder_level.required' => 'Please set a reorder level for low-stock alerts.',
            'reorder_level.min'      => 'Reorder level cannot be negative.',
            'unit_price.required'    => 'Please enter the unit price.',
            'unit_price.numeric'     => 'Unit price must be a number, e.g. 89.50.',
            'unit_price.min'         => 'Unit price cannot be negative.',
        ];
    }
}
