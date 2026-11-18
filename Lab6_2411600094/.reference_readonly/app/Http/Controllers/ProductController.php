<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    // GET /products
    public function index(Request $request)
    {
        $query = Product::query();

        // PART 1: Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // PART 2: Search by name, brand or SKU
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // PART 3: Paginate 10 per page and keep the filters in the page links
        $products = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::orderBy('name')->pluck('name');

        // LAB 6: how many products are in the Trash (shown on the Trash button)
        $trashedCount = Product::onlyTrashed()->count();

        return view('products.index', compact('products', 'categories', 'trashedCount'));
    }

    // GET /products/create
    public function create()
    {
        // Dropdown choices come from the Categories and Brands pages
        $categories = Category::orderBy('name')->pluck('name');
        $brands = Brand::orderBy('name')->pluck('name');

        return view('products.create', compact('categories', 'brands'));
    }

    // POST /products
    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product added successfully.');
    }

    // GET /products/{product}
    public function show(Request $request, Product $product)
    {
        // Walk backwards from today's quantity to find the balance after each movement,
        // with an optional date range filter (LAB 6)
        $balance = $product->quantity;

        $transactions = $product->transactions()
            ->with('user')
            ->recent()
            ->get()
            ->map(function ($t) use (&$balance) {
                $t->balance_after = $balance;
                $balance -= $t->signed_quantity;

                return $t;
            })
            ->when($request->filled('from'), fn ($c) => $c->filter(fn ($t) => $t->created_at->toDateString() >= $request->from))
            ->when($request->filled('to'), fn ($c) => $c->filter(fn ($t) => $t->created_at->toDateString() <= $request->to))
            ->values();

        return view('products.show', compact('product', 'transactions'));
    }

    // GET /products/{product}/edit
    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->pluck('name');
        $brands = Brand::orderBy('name')->pluck('name');

        return view('products.edit', compact('product', 'categories', 'brands'));
    }

    // PUT/PATCH /products/{product}
    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProduct($request, $product);

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    // Mark an out-of-stock product as inactive (hides it from the whole system)
    public function deactivate(Product $product)
    {
        if ($product->quantity > 0) {
            return redirect()
                ->route('products.index')
                ->with('error', "Cannot mark \"{$product->name}\" as inactive: it still has {$product->quantity} item(s) in stock. Only out-of-stock products can be marked inactive.");
        }

        $product->update(['is_active' => false]);

        return redirect()
            ->route('products.index')
            ->with('success', "\"{$product->name}\" was marked as inactive and is now hidden.");
    }

    // DELETE /products/{product}
    public function destroy(Product $product)
    {
        // A product that still has stock cannot be deleted
        if ($product->quantity > 0) {
            return redirect()
                ->route('products.index')
                ->with('error', "Cannot delete \"{$product->name}\": it still has {$product->quantity} item(s) in stock. Only out-of-stock products can be deleted.");
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product moved to the Trash. You can restore it from Inventory > Trash.');
    }

    /**
     * Validation used by both store() and update().
     * When updating, the current product is ignored in the SKU unique check.
     */
    private function validateProduct(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name'          => 'required|string|max:255',
            'sku'           => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($product?->id),
            ],
            'brand'         => 'nullable|string|max:100|exists:brands,name',
            'description'   => 'nullable|string|max:1000',
            'category'      => 'required|string|max:100|exists:categories,name',
            'quantity'      => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'unit_price'    => 'required|numeric|min:0.01',
            'supplier'      => 'nullable|string|max:255',
        ], [
            'name.required'          => 'Please enter the product name.',
            'sku.required'           => 'Please enter a SKU.',
            'sku.unique'             => 'This SKU is already used by another product.',
            'category.required'      => 'Please choose a category.',
            'category.exists'        => 'Please choose a category from the list.',
            'brand.exists'           => 'Please choose a brand from the list.',
            'quantity.required'      => 'Please enter the quantity.',
            'quantity.min'           => 'Quantity cannot be negative.',
            'reorder_level.required' => 'Please enter the reorder level.',
            'reorder_level.min'      => 'Reorder level cannot be negative.',
            'unit_price.required'    => 'Please enter the unit price.',
            'unit_price.min'         => 'Unit price must be at least 0.01.',
        ]);
    }

    // LAB 6: Trash - products that were deleted (soft delete)
    public function trash()
    {
        $products = Product::onlyTrashed()->orderByDesc('deleted_at')->paginate(10);

        return view('products.trash', compact('products'));
    }

    // LAB 6: bring a product back from the Trash
    public function restore(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return redirect()->route('products.trash')
            ->with('success', "\"{$product->name}\" was restored to the inventory.");
    }
}
