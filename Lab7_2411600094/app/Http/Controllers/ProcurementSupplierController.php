<?php

namespace App\Http\Controllers;

use App\Mail\SupplierUpdated;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ProcurementSearchService;
use App\Services\SupplierPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class ProcurementSupplierController extends Controller
{
    public function index(Request $request, ProcurementSearchService $search)
    {
        abort_if($request->input('status') === 'deleted' && ! $request->user()->isAdmin(), 403);

        $suppliers = $search->suppliers($request)
            ->withCount('purchaseOrders')
            ->paginate(10)
            ->withQueryString();

        return view('procurement.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('procurement.suppliers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());
        $data['is_active'] = $request->boolean('is_active');

        $supplier = Supplier::create($data);

        return redirect()->route('procurement.suppliers.show', $supplier)
            ->with('success', 'Supplier added successfully.');
    }

    public function show(Supplier $supplier, SupplierPerformanceService $performance)
    {
        $supplier->load(['suppliedProducts', 'stickyNotes.user']);
        $recentOrders = $supplier->purchaseOrders()
            ->withCount('items')
            ->latest('order_date')
            ->latest('id')
            ->take(8)
            ->get();
        $metrics = $performance->forSupplier($supplier);
        $products = Product::orderBy('name')->get(['id', 'sku', 'name']);

        return view('procurement.suppliers.show', compact('supplier', 'recentOrders', 'metrics', 'products'));
    }

    public function edit(Supplier $supplier)
    {
        return view('procurement.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate($this->rules($supplier), $this->messages());
        $data['is_active'] = $request->boolean('is_active');
        $supplier->update($data);
        $message = 'Supplier updated successfully.';

        if ($supplier->email) {
            try {
                Mail::to($supplier->email)->send(new SupplierUpdated($supplier));
                $message .= " An email was sent to {$supplier->email}.";
            } catch (Throwable $exception) {
                report($exception);
                $message .= ' The update notice could not be sent; check the mail settings.';
            }
        }

        return redirect()->route('procurement.suppliers.show', $supplier)->with('success', $message);
    }

    public function updateProducts(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'products' => 'nullable|array',
            'products.*.id' => 'required|integer|distinct|exists:products,id',
            'products.*.supplier_price' => 'nullable|numeric|min:0',
            'products.*.supplier_sku' => 'nullable|string|max:50',
            'products.*.is_preferred' => 'nullable|boolean',
        ]);

        $sync = [];
        foreach ($data['products'] ?? [] as $product) {
            $sync[$product['id']] = [
                'supplier_price' => $product['supplier_price'] ?? null,
                'supplier_sku' => $product['supplier_sku'] ?? null,
                'is_preferred' => (bool) ($product['is_preferred'] ?? false),
            ];
        }

        $supplier->suppliedProducts()->sync($sync);

        return redirect()->route('procurement.suppliers.show', $supplier)
            ->with('success', 'Supplier product links updated.');
    }

    public function destroy(Supplier $supplier)
    {
        $open = $supplier->openOrdersCount();
        if ($open > 0) {
            return redirect()->route('procurement.suppliers.index')
                ->with('error', "Cannot delete {$supplier->name}: it still has {$open} open purchase order(s).");
        }

        $supplier->update(['is_active' => false]);
        $supplier->delete();

        return redirect()->route('procurement.suppliers.index')
            ->with('success', "{$supplier->name} was deleted. Existing purchase orders retain the supplier record.");
    }

    public function restore(int $id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        $supplier->restore();

        return redirect()->route('procurement.suppliers.index')
            ->with('success', "{$supplier->name} was restored.");
    }

    private function rules(?Supplier $supplier = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($supplier?->id)],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'address' => 'nullable|string|max:500',
            'payment_terms' => 'required|string|max:50',
            'lead_time_days' => 'required|integer|min:0|max:365',
            'minimum_order_amount' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Please enter the company name.',
            'contact_person.required' => 'Please enter the contact person.',
            'email.required' => 'Please enter the email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already used by another supplier.',
            'phone.required' => 'Please enter the phone number.',
            'phone.regex' => 'Phone number may only have digits, spaces, +, - and parentheses (7 to 20 characters).',
            'payment_terms.required' => 'Please enter the payment terms (e.g. Net 30, COD).',
            'lead_time_days.required' => 'Please enter the lead time in days.',
            'minimum_order_amount.required' => 'Please enter the minimum order amount (0 if none).',
        ];
    }
}
