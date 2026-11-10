<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::withCount('products')
                       ->withSum('products', 'quantity')
                       ->orderBy('id')
                       ->paginate(10)
                       ->withQueryString();

        return view('brands.index', compact('brands'));
    }

    public function store(Request $request)
    {
        $brand = Brand::create($request->validate($this->rules(), $this->messages()));

        // Submitted from the "+" button beside the Brand dropdown on a product form.
        if ($request->input('source') === 'product-form') {
            return redirect()
                ->route($request->input('product_id') ? 'products.edit' : 'products.create',
                        ['product' => $request->input('product_id')])
                ->with('success', "Brand \"{$brand->name}\" added — it is already selected below.")
                ->with('new_brand_id', $brand->id);
        }

        return redirect()
            ->route('brands.index')
            ->with('success', "Brand \"{$brand->name}\" added successfully.");
    }

    public function update(Request $request, Brand $brand)
    {
        $brand->update($request->validate($this->rules($brand->id), $this->messages()));

        return redirect()
            ->back()
            ->with('success', "Brand \"{$brand->name}\" updated successfully.");
    }

    public function destroy(Brand $brand)
    {
        if ($count = $brand->products()->count()) {
            return back()->with(
                'error',
                "\"{$brand->name}\" cannot be deleted — {$count} product(s) still use this brand. "
                . 'Reassign those products first.'
            );
        }

        $name = $brand->name;
        $brand->delete();

        return back()->with('success', "Brand \"{$name}\" deleted successfully.");
    }

    private function rules($ignoreId = null): array
    {
        return [
            'name'        => ['required', 'string', 'max:100', Rule::unique('brands', 'name')->ignore($ignoreId)],
            'description' => 'nullable|string|max:255',
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Please provide the brand name.',
            'name.max'      => 'Brand names are kept short — 100 characters or fewer.',
            'name.unique'   => 'That brand already exists.',
        ];
    }
}
