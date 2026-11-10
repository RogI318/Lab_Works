<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
                              ->withSum('products', 'quantity')
                              ->orderBy('id')
                              ->paginate(10)
                              ->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $category = Category::create($request->validate($this->rules(), $this->messages()));

        // Submitted from the "+" button beside the Category dropdown on a product form.
        if ($request->input('source') === 'product-form') {
            return redirect()
                ->route($request->input('product_id') ? 'products.edit' : 'products.create',
                        ['product' => $request->input('product_id')])
                ->with('success', "Category \"{$category->name}\" added — it is already selected below.")
                ->with('new_category_id', $category->id);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', "Category \"{$category->name}\" added successfully.");
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->validate($this->rules($category->id), $this->messages()));

        return redirect()
            ->back()
            ->with('success', "Category \"{$category->name}\" updated successfully.");
    }

    public function destroy(Category $category)
    {
        if ($count = $category->products()->count()) {
            return back()->with(
                'error',
                "\"{$category->name}\" cannot be deleted — {$count} product(s) still use this category. "
                . 'Move those products to another category first.'
            );
        }

        $name = $category->name;
        $category->delete();

        return back()->with('success', "Category \"{$name}\" deleted successfully.");
    }

    private function rules($ignoreId = null): array
    {
        return [
            'name'        => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($ignoreId)],
            'description' => 'nullable|string|max:255',
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Please provide the category name.',
            'name.max'      => 'Category names are kept short — 100 characters or fewer.',
            'name.unique'   => 'That category already exists.',
        ];
    }
}
