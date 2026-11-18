<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    // index() - table of all brands (10 per page)
    public function index()
    {
        $brands = Brand::orderBy('name')->paginate(10)->withQueryString();

        // How many products use each brand: ['Stanley' => 2, ...]
        $productCounts = Product::whereNotNull('brand')->selectRaw('brand, COUNT(*) as total')
            ->groupBy('brand')
            ->pluck('total', 'brand');

        return view('brands.index', compact('brands', 'productCounts'));
    }

    // create() - show the Add Brand form
    public function create()
    {
        return view('brands.create');
    }

    // store() - save a new brand
    public function store(Request $request)
    {
        $validated = $this->validateBrand($request);

        Brand::create($validated);

        return redirect()->route('brands.index')
            ->with('success', 'Brand added successfully.');
    }

    // show() - details of one brand and the products that use it
    public function show(Brand $brand)
    {
        $products = Product::where('brand', $brand->name)->orderBy('name')->get();

        return view('brands.show', compact('brand', 'products'));
    }

    // edit() - show the Edit Brand form
    public function edit(Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    // update() - save changes to a brand
    public function update(Request $request, Brand $brand)
    {
        $validated = $this->validateBrand($request, $brand->id);

        // If the name changed, the products that use it get the new name too.
        // Both changes happen together, or not at all.
        DB::transaction(function () use ($validated, $brand) {
            if ($brand->name !== $validated['name']) {
                Product::withoutGlobalScopes()->where('brand', $brand->name)->update(['brand' => $validated['name']]);
            }

            $brand->update($validated);
        });

        return redirect()->route('brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    // destroy() - delete a brand (blocked if a product still uses it)
    public function destroy(Brand $brand)
    {
        $inUse = Product::withoutGlobalScopes()->where('brand', $brand->name)->count();

        if ($inUse > 0) {
            return back()->withErrors([
                'brand' => "Cannot delete {$brand->name}: {$inUse} product(s) still use it.",
            ]);
        }

        $brand->delete();

        return redirect()->route('brands.index')
            ->with('success', 'Brand deleted successfully.');
    }

    // Validation rules, shared by store() and update()
    private function validateBrand(Request $request, ?int $ignoreId = null): array
    {
        $nameRule = 'required|string|max:100|unique:brands,name';
        if ($ignoreId) {
            $nameRule .= ",{$ignoreId}";
        }

        return $request->validate([
            'name' => $nameRule,
            'description' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Please enter the brand name.',
            'name.unique' => 'This brand already exists.',
        ]);
    }
}
