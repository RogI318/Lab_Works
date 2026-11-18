<div class="row">

    <div class="col-md-6 mb-3">
        <label class="form-label">Product Name</label>

        <input
            type="text"
            name="name"
            class="form-control"
            value="{{ old('name', $product->name ?? '') }}"
            required
        >

        @error('name')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Brand</label>

        <select name="brand" class="form-select">

            <option value="">No brand</option>

            @foreach ($brands as $brand)
                <option
                    value="{{ $brand }}"
                    {{ old('brand', $product->brand ?? '') == $brand ? 'selected' : '' }}
                >
                    {{ $brand }}
                </option>
            @endforeach

        </select>

        <div class="form-text">
            Not listed? <a href="{{ route('brands.index') }}">Add a brand</a>
        </div>

        @error('brand')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">SKU</label>

        <input
            type="text"
            name="sku"
            class="form-control"
            value="{{ old('sku', $product->sku ?? '') }}"
            required
        >

        @error('sku')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="mb-3">
    <label class="form-label">Category</label>

    <select name="category" class="form-select" required>

        <option value="">Select a category</option>

        @foreach ($categories as $category)
            <option
                value="{{ $category }}"
                {{ old('category', $product->category ?? '') == $category ? 'selected' : '' }}
            >
                {{ $category }}
            </option>
        @endforeach

    </select>

    <div class="form-text">
        Not listed? <a href="{{ route('categories.index') }}">Add a category</a>
    </div>

    @error('category')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="row">

    <div class="col-md-4 mb-3">
        <label class="form-label">Quantity</label>

        <input
            type="number"
            name="quantity"
            class="form-control"
            min="0"
            value="{{ old('quantity', $product->quantity ?? 0) }}"
            required
        >

        @error('quantity')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Reorder Level</label>

        <input
            type="number"
            name="reorder_level"
            class="form-control"
            min="0"
            value="{{ old('reorder_level', $product->reorder_level ?? 10) }}"
            required
        >

        @error('reorder_level')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Unit Price (₱)</label>

        <input
            type="number"
            step="0.01"
            name="unit_price"
            class="form-control"
            min="0.01"
            value="{{ old('unit_price', $product->unit_price ?? '') }}"
            required
        >

        @error('unit_price')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="mb-3">
    <label class="form-label">Supplier</label>

    <input
        type="text"
        name="supplier"
        class="form-control"
        value="{{ old('supplier', $product->supplier ?? '') }}"
    >

    @error('supplier')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Description</label>

    <textarea
        name="description"
        class="form-control"
        rows="3"
    >{{ old('description', $product->description ?? '') }}</textarea>

    @error('description')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
