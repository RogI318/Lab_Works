{{-- Shared by products/create.blade.php and products/edit.blade.php (Lab 5, Part 5 Step 3).
     Params: $product (model or null), $categories, $brands, $draftKey (create page only) --}}
@php
    // A Brand/Category pop-up posts a "name" field too, so the product form should
    // only display errors when it was the form actually submitted.
    $productErrors = $errors->any() && !old('modal_key');
    $selectedBrand = old('brand_id') ?: (session('new_brand_id') ?? $product->brand_id ?? '');
    $selectedCat   = old('category_id') ?: (session('new_category_id') ?? $product->category_id ?? '');
@endphp

<form method="POST" id="productForm"
      @if(!empty($draftKey)) data-draft-key="{{ $draftKey }}" @endif
      action="{{ isset($product) ? route('products.update', $product) : route('products.store') }}">
    @csrf
    @isset($product)@method('PUT')@endisset

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name"
               class="form-control @if($productErrors) @error('name') is-invalid @enderror @endif"
               value="{{ old('name') !== null && $productErrors ? old('name') : ($product->name ?? '') }}"
               placeholder="e.g. Coco Lumber 2x2x10">
        @if($productErrors)@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>

    <div class="col-md-3">
        <label for="sku" class="form-label fw-semibold">SKU <span class="text-danger">*</span></label>
        <input type="text" name="sku" id="sku"
               class="form-control text-uppercase @if($productErrors) @error('sku') is-invalid @enderror @endif"
               value="{{ old('sku') !== null && $productErrors ? old('sku') : ($product->sku ?? '') }}"
               placeholder="e.g. LMB-COC-1001">
        @if($productErrors)@error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>

    <div class="col-md-3">
        <label for="supplier" class="form-label fw-semibold">Supplier</label>
        <input type="text" name="supplier" id="supplier"
               class="form-control @if($productErrors) @error('supplier') is-invalid @enderror @endif"
               value="{{ old('supplier') !== null && $productErrors ? old('supplier') : ($product->supplier ?? '') }}"
               placeholder="e.g. Metro Lumber Supply">
        @if($productErrors)@error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>

    {{-- Category: dropdown + quick-create pop-up --}}
    <div class="col-md-6">
        <label for="category_id" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
        <div class="input-group">
            <select name="category_id" id="category_id"
                    class="form-select @if($productErrors) @error('category_id') is-invalid @enderror @endif">
                <option value="">-- select a category --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) $selectedCat === $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <button type="button" class="btn btn-outline-primary" title="Add a new category"
                    data-bs-toggle="modal" data-bs-target="#category-add">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
        @if($productErrors)@error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
    </div>

    {{-- Brand: dropdown + quick-create pop-up --}}
    <div class="col-md-6">
        <label for="brand_id" class="form-label fw-semibold">Brand <span class="text-danger">*</span></label>
        <div class="input-group">
            <select name="brand_id" id="brand_id"
                    class="form-select @if($productErrors) @error('brand_id') is-invalid @enderror @endif">
                <option value="">-- select a brand --</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((int) $selectedBrand === $brand->id)>
                        {{ $brand->name }}
                    </option>
                @endforeach
            </select>
            <button type="button" class="btn btn-outline-primary" title="Add a new brand"
                    data-bs-toggle="modal" data-bs-target="#brand-add">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
        @if($productErrors)@error('brand_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
    </div>

    <div class="col-md-4">
        <label for="quantity" class="form-label fw-semibold">
            Quantity in Stock <span class="text-danger">*</span>
        </label>
        <input type="number" name="quantity" id="quantity" min="0" step="1"
               class="form-control @if($productErrors) @error('quantity') is-invalid @enderror @endif"
               value="{{ old('quantity') !== null && $productErrors ? old('quantity') : ($product->quantity ?? 0) }}">
        @if($productErrors)@error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        <small class="text-muted">
            {{ isset($product) ? 'Changing this records a stock adjustment.' : 'Saved as the opening stock.' }}
        </small>
    </div>

    <div class="col-md-4">
        <label for="reorder_level" class="form-label fw-semibold">
            Reorder Level <span class="text-danger">*</span>
        </label>
        <input type="number" name="reorder_level" id="reorder_level" min="0" step="1"
               class="form-control @if($productErrors) @error('reorder_level') is-invalid @enderror @endif"
               value="{{ old('reorder_level') !== null && $productErrors ? old('reorder_level') : ($product->reorder_level ?? 10) }}">
        @if($productErrors)@error('reorder_level')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        <small class="text-muted">Alert when stock reaches this level.</small>
    </div>

    <div class="col-md-4">
        <label for="unit_price" class="form-label fw-semibold">Unit Price (PHP) <span class="text-danger">*</span></label>
        <input type="number" name="unit_price" id="unit_price" min="0" step="0.01"
               class="form-control @if($productErrors) @error('unit_price') is-invalid @enderror @endif"
               value="{{ old('unit_price') !== null && $productErrors ? old('unit_price') : (isset($product) ? number_format((float) $product->unit_price, 2, '.', '') : '0.00') }}">
        @if($productErrors)@error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>

    <div class="col-12">
        <label for="description" class="form-label fw-semibold">Description</label>
        <textarea name="description" id="description" rows="3"
                  class="form-control @if($productErrors) @error('description') is-invalid @enderror @endif"
                  placeholder="Details such as size, colour, pack quantity...">{{ old('description') !== null && $productErrors ? old('description') : ($product->description ?? '') }}</textarea>
        @if($productErrors)@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-{{ isset($product) ? 'save' : 'plus-circle' }} me-1"></i>
        {{ isset($product) ? 'Update Product' : 'Save Product' }}
    </button>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>

{{-- The same add pop-ups used on the Brands / Categories pages. --}}
@include('partials.item-modal', [
    'item'     => null,
    'module'   => 'brand',
    'source'   => 'product-form',
    'productId'=> $product->id ?? null,
])
@include('partials.item-modal', [
    'item'      => null,
    'module'    => 'category',
    'source'    => 'product-form',
    'productId' => $product->id ?? null,
])

@if(!empty($draftKey))
    @push('scripts')
    <script>
        // Keeps a half-typed product while you pop open the Brand / Category form,
        // because that submit replaces the server-side "old input" with its own fields.
        (function () {
            const form = document.getElementById('productForm');
            const key  = form.dataset.draftKey;

            // The product form itself came back with errors: the server-side old input wins.
            const fromProductForm = {{ old() && ! old('modal_key') ? 'true' : 'false' }};
            // The page came back from the Brand / Category pop-up (saved, or failed validation).
            const fromPopup = {{ old('modal_key') || session('new_brand_id') || session('new_category_id') ? 'true' : 'false' }};
            // The brand / category that was just added must stay selected, not be overwritten by the draft.
            const serverPicked = @json(array_filter(['brand_id' => session('new_brand_id'), 'category_id' => session('new_category_id')]));

            if (fromProductForm || !fromPopup) {
                sessionStorage.removeItem(key);          // fresh visit or server input: no stale draft
            } else {
                const draft = JSON.parse(sessionStorage.getItem(key) || 'null');
                if (draft) {
                    Object.entries(draft).forEach(([name, value]) => {
                        const field = form.elements[name];
                        if (field && field.type !== 'hidden' && value !== '' && !(name in serverPicked)) {
                            field.value = value;
                        }
                    });
                }
            }

            form.addEventListener('input', () => {
                const draft = {};
                [...form.elements].forEach(el => {
                    if (el.name && el.type !== 'hidden') draft[el.name] = el.value;
                });
                sessionStorage.setItem(key, JSON.stringify(draft));
            });

            form.addEventListener('submit', () => sessionStorage.removeItem(key));
        })();
    </script>
    @endpush
@endif