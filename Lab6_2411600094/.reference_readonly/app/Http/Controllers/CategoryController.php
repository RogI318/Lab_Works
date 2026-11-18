<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    // index() - table of all categories (10 per page)
    public function index()
    {
        $categories = Category::orderBy('name')->paginate(10)->withQueryString();

        // How many products use each category: ['Tools' => 2, ...]
        $productCounts = Product::selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return view('categories.index', compact('categories', 'productCounts'));
    }

    // create() - show the Add Category form
    public function create()
    {
        return view('categories.create');
    }

    // store() - save a new category
    public function store(Request $request)
    {
        $validated = $this->validateCategory($request);

        Category::create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Category added successfully.');
    }

    // show() - details of one category and the products that use it
    public function show(Category $category)
    {
        $products = Product::where('category', $category->name)->orderBy('name')->get();

        return view('categories.show', compact('category', 'products'));
    }

    // edit() - show the Edit Category form
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    // update() - save changes to a category
    public function update(Request $request, Category $category)
    {
        $validated = $this->validateCategory($request, $category->id);

        // If the name changed, the products that use it get the new name too.
        // Both changes happen together, or not at all.
        DB::transaction(function () use ($validated, $category) {
            if ($category->name !== $validated['name']) {
                Product::withoutGlobalScopes()->where('category', $category->name)->update(['category' => $validated['name']]);
            }

            $category->update($validated);
        });

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    // destroy() - delete a category (blocked if a product still uses it)
    public function destroy(Category $category)
    {
        $inUse = Product::withoutGlobalScopes()->where('category', $category->name)->count();

        if ($inUse > 0) {
            return back()->withErrors([
                'category' => "Cannot delete {$category->name}: {$inUse} product(s) still use it.",
            ]);
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    // Validation rules, shared by store() and update()
    private function validateCategory(Request $request, ?int $ignoreId = null): array
    {
        $nameRule = 'required|string|max:100|unique:categories,name';
        if ($ignoreId) {
            $nameRule .= ",{$ignoreId}";
        }

        return $request->validate([
            'name' => $nameRule,
            'description' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Please enter the category name.',
            'name.unique' => 'This category already exists.',
        ]);
    }
}
