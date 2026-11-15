<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request, SearchService $search)
    {
        // Lab 6's deleted-products room stays an admin room: the gate lives
        // here, where the visitor is known, and the service only builds the
        // query it is asked for.
        if ($request->boolean('trashed')) {
            abort_unless($request->user()->is_admin, 403, 'Only admins may list deleted products.');
        }

        $products = $search->searchProducts($request->all())
                         ->paginate(10)
                         ->withQueryString();

        return view('products.index', [
            'products'   => $products,
            'categories' => Category::orderBy('name')->get(),
            'brands'     => Brand::orderBy('name')->get(),
            // Lab 7 Part 7: the inventory list can now be narrowed to one
            // supplier, so every supplier still on the books is offered.
            'suppliers'  => Supplier::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('products.create', $this->formOptions());
    }

    public function store(Request $request)
    {
        $this->normalizeSku($request);
        $validated = $request->validate($this->rules(), $this->messages());

        // The starting quantity is saved as a "Stock In" movement, so the movement
        // history and the quantity on hand always agree (the InventoryTransaction
        // model adds the quantity to the product by itself).
        $openingStock = (int) $validated['quantity'];

        $product = DB::transaction(function () use ($validated, $openingStock) {
            // The form's quantity is the opening stock, not a column write: the
            // ledger below is what puts units on the shelf.
            $data             = $this->withSupplierName($validated);
            $data['quantity'] = 0;

            $product = Product::create($data);

            if ($openingStock > 0) {
                $this->recordMovement($product, InventoryTransaction::TYPE_IN, $openingStock, 'Opening stock');
            }

            return $product;
        });

        return redirect()
            ->route('products.index')
            ->with('success', "Product \"{$product->name}\" added successfully.");
    }

    public function show(Request $request, Product $product)
    {
        $product->load('category', 'brand', 'primarySupplier', 'suppliers');

        // Part 9 Step 1: full ledger with a running balance, date-filterable.
        $transactions = $product->transactionHistory(
            $request->date_from,
            $request->date_to,
        );

        $opening = $transactions->isEmpty()
            ? $product->quantity
            : $transactions->first()->balance_after - $transactions->first()->signedQuantity();

        return view('products.show', [
            'product'      => $product,
            'transactions' => $transactions,
            'opening'      => $opening,
        ]);
    }

    public function edit(Product $product)
    {
        return view('products.edit', ['product' => $product] + $this->formOptions());
    }

    public function update(Request $request, Product $product)
    {
        $this->normalizeSku($request);
        $validated = $request->validate($this->rules($product->id), $this->messages());

        // A changed quantity is saved as a stock movement (not by overwriting the
        // number), so the ledger shown on the product page never drifts.
        $newQuantity = (int) $validated['quantity'];
        unset($validated['quantity']);

        DB::transaction(function () use ($product, $validated, $newQuantity) {
            $product->update($this->withSupplierName($validated));

            $difference = $newQuantity - (int) $product->quantity;

            if ($difference !== 0) {
                $this->recordMovement(
                    $product,
                    $difference > 0 ? InventoryTransaction::TYPE_IN : InventoryTransaction::TYPE_OUT,
                    abs($difference),
                    'Manual adjustment (edit form)'
                );
            }
        });

        return redirect()
            ->route('products.index')
            ->with('success', "Product \"{$product->name}\" updated successfully.");
    }

    public function destroy(Request $request, Product $product)
    {
        $name = $product->name;

        // A trashed product reaching this route means the admin asked for the
        // permanent purge — only now does the FK cascade remove the ledger.
        if ($product->trashed()) {
            abort_unless($request->user()->is_admin, 403, 'Only admins may erase a product and its history.');

            // Lab 7 Part 2: purchase_order_items.product_id is RESTRICT, because
            // an order line is evidence of a purchase. Erasing the product would
            // erase the order it was bought on, so the purge stops here and the
            // product stays (soft) deleted instead.
            if ($product->purchaseOrderItems()->exists()) {
                return redirect()
                    ->route('products.index', ['trashed' => 1])
                    ->with('error', sprintf(
                        '"%s" appears on %d purchase order line(s), so it cannot be erased. '
                        . 'It stays deleted and hidden from the catalogue.',
                        $name,
                        $product->purchaseOrderItems()->count()
                    ));
            }

            $product->forceDelete();

            return redirect()
                ->route('products.index', ['trashed' => 1])
                ->with('success', "Product \"{$name}\" was permanently erased, together with its movement history.");
        }

        // Soft delete: the product disappears from every listing, but its
        // transactions survive, so reports stay reproducible and an admin can
        // restore it.
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', "Product \"{$name}\" deleted — its transaction history is kept, so it can be restored.");
    }

    /** Admin only: bring a soft-deleted product back. */
    public function restore(Request $request, Product $product)
    {
        abort_unless($request->user()->is_admin, 403, 'Only admins may restore products.');
        abort_unless($product->trashed(), 404);

        $product->restore();

        return redirect()
            ->route('products.index', ['trashed' => 1])
            ->with('success', "Product \"{$product->name}\" was restored.");
    }

    /* ---------------- helpers ---------------- */

    /** One stock movement; the model updates the quantity on hand. */
    private function recordMovement(Product $product, string $type, int $quantity, string $reference): void
    {
        InventoryTransaction::create([
            'product_id' => $product->id,
            'type'       => $type,
            'quantity'   => $quantity,
            'reference_number' => $reference,
            'user_id'    => auth()->id(),
        ]);
    }

    /**
     * Lab 7 Part 1 Step 4: supplier_id is the real link now, but the legacy
     * free-text `supplier` column is refreshed from it rather than dropped —
     * Lab 6's Reports still GROUP BY that column and the product search still
     * matches on it, so the two must never tell different stories.
     */
    private function withSupplierName(array $validated): array
    {
        $validated['supplier'] = Supplier::find($validated['supplier_id'] ?? null)?->name;

        return $validated;
    }

    /** SKUs are stored in capitals, exactly as the form shows them. */
    private function normalizeSku(Request $request): void
    {
        if ($request->has('sku')) {
            $request->merge(['sku' => strtoupper(trim((string) $request->input('sku')))]);
        }
    }

    private function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'brands'     => Brand::orderBy('name')->get(),
            // Lab 7 Part 1: only a supplier we currently buy from may be
            // picked; an already-saved inactive one is re-added by the form.
            'suppliers'  => Supplier::active()->orderBy('name')->get(),
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
            'supplier_id'   => 'nullable|integer|exists:suppliers,id',
            'quantity'      => 'required|integer|min:0|max:1000000',
            'reorder_level' => 'required|integer|min:0|max:1000000',
            'unit_price'    => 'required|numeric|min:0|max:99999999.99',
        ];
    }

    private function messages(): array
    {
        return [
            'name.required'          => 'Please provide the product name.',
            'sku.required'           => 'Please provide a SKU (e.g., BND-PL-0101).',
            'sku.unique'             => 'That SKU is already used by another product. (Deleted products keep their SKU — restore or erase them first.)',
            'category_id.required'   => 'Please select a category for this product.',
            'category_id.exists'     => 'The selected category no longer exists.',
            'brand_id.required'      => 'Please select the brand of this product.',
            'brand_id.exists'        => 'The selected brand no longer exists.',
            'quantity.required'      => 'Please enter the quantity currently in stock.',
            'quantity.integer'       => 'Quantity must be a whole number.',
            'quantity.min'           => 'Quantity cannot be negative.',
            'reorder_level.required' => 'Please set a reorder level for low-stock alerts.',
            'reorder_level.integer'  => 'Reorder level must be a whole number.',
            'reorder_level.min'      => 'Reorder level cannot be negative.',
            'reorder_level.max'      => 'Reorder level is too large.',
            'unit_price.required'    => 'Please enter the unit price.',
            'unit_price.numeric'     => 'Unit price must be a number, e.g. 89.50.',
            'unit_price.min'         => 'Unit price cannot be negative.',
        ];
    }
}