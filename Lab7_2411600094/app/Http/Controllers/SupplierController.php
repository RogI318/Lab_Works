<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->canManageStock(), 403);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $suppliers = Supplier::withCount('products')
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('contact_person', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('is_active', $status === 'active'))
            ->orderBy('name')->paginate(15)->withQueryString();
        return view('suppliers.index', compact('suppliers'));
    }

    public function show(Request $request, Supplier $supplier)
    {
        abort_unless($request->user()->canManageStock(), 403);
        $products = $supplier->products()->with(['category', 'brand'])->orderBy('name')->paginate(10);
        return view('suppliers.show', compact('supplier', 'products'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canManageStock(), 403);
        $validated = $request->validate($this->rules(), $this->messages());
        $validated['is_active'] = true;
        Supplier::create($validated);
        return back()->with('success', 'Supplier added successfully.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        abort_unless($request->user()->canManageStock(), 403);
        $supplier->update($request->validate($this->rules($supplier->id), $this->messages()));
        return back()->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        abort_unless($request->user()->canManageStock(), 403);
        if ($supplier->is_active) {
            return back()->with('error', 'Deactivate this supplier before deleting it.');
        }
        if ($supplier->products()->exists()) {
            return back()->with('error', 'Reassign or remove this supplier from its products before deleting it.');
        }
        $supplier->delete();
        return back()->with('success', 'Supplier deleted successfully.');
    }

    public function toggleStatus(Request $request, Supplier $supplier)
    {
        abort_unless($request->user()->canManageStock(), 403);
        $supplier->update(['is_active' => ! $supplier->is_active]);
        return back()->with('success', $supplier->name.' marked '.($supplier->is_active ? 'Active' : 'Inactive').'.');
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($ignoreId)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^(?:(?:\\+63|0)9[0-9]{2})[- ]?[0-9]{3}[- ]?[0-9]{4}$/'],
            'lead_time_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'is_active' => ['sometimes', 'boolean'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function messages(): array
    {
        return ['phone.regex' => 'Enter a Philippine mobile number, e.g. 09175550100, 0917-555-0100, or +639175550100.'];
    }
}
