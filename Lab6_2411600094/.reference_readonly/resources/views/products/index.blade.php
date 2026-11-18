@extends('layouts.app')

@section('title', 'Inventory')

@section('content')

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="mb-1">
                Inventory
            </h2>

            <p class="text-muted mb-0">
                Manage and search hardware products
            </p>
        </div>

        <div class="d-flex gap-2">

        <a href="{{ route('products.trash') }}" class="btn btn-outline-secondary">

            <i class="bi bi-trash me-1"></i>Trash ({{ $trashedCount ?? 0 }})

        </a>

        <a
            href="{{ route('products.create') }}"
            class="btn btn-primary"
        >
            <i class="bi bi-plus-circle me-1"></i>
            Add Product
        </a>
        </div>

    </div>


    <!-- Product Filters -->
    <div class="card shadow-hover mb-4">

        <div class="card-header">

            <h5 class="mb-0">
                <i class="bi bi-funnel me-2"></i>
                Product Filters
            </h5>

        </div>

        <div class="card-body">

            <form
                method="GET"
                action="{{ route('products.index') }}"
            >

                <div class="row g-3 align-items-end">

                    <!-- Category -->
                    <div class="col-md-4">

                        <label
                            for="category"
                            class="form-label fw-semibold"
                        >
                            Category
                        </label>

                        <select
                            name="category"
                            id="category"
                            class="form-select"
                        >

                            <option value="">
                                All Categories
                            </option>

                            @foreach ($categories as $category)

                                <option
                                    value="{{ $category }}"
                                    {{ request('category') == $category ? 'selected' : '' }}
                                >
                                    {{ $category }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <!-- Search -->
                    <div class="col-md-5">

                        <label
                            for="search"
                            class="form-label fw-semibold"
                        >
                            Search Product
                        </label>

                        <input
                            type="text"
                            name="search"
                            id="search"
                            class="form-control"
                            placeholder="Search by Name, Brand or SKU"
                            value="{{ request('search') }}"
                        >

                    </div>


                    <!-- Buttons -->
                    <div class="col-md-3">

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary flex-grow-1"
                            >
                                <i class="bi bi-search me-1"></i>
                                Apply
                            </button>

                            <a
                                href="{{ route('products.index') }}"
                                class="btn btn-outline-secondary"
                                title="Reset Filters"
                            >
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- Product Table -->
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h5 class="mb-0">
                Product List
            </h5>

            <span class="badge bg-secondary">
                {{ $products->total() }} product(s)
            </span>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead>

                        <tr>

                            <th>SKU</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Reorder Level</th>
                            <th>Unit Price</th>
                            <th>Status</th>
                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($products as $product)

                            {{-- Row color: red = out of stock, yellow = low stock --}}
                            <tr class="{{ $product->isOutOfStock() ? 'table-danger' : ($product->isLowStock() ? 'table-warning' : '') }}">

                                <td>
                                    {{ $product->sku }}
                                </td>

                                <td>
                                    {{ $product->name }}
                                </td>

                                <td>
                                    {{ $product->brand ?? '—' }}
                                </td>

                                <td>
                                    {{ $product->category }}
                                </td>

                                <td>
                                    {{ $product->quantity }}
                                </td>

                                <td>
                                    {{ $product->reorder_level }}
                                </td>

                                <td>
                                    ₱{{ number_format($product->unit_price, 2) }}
                                </td>

                                <td>

                                    @php
                                        $badgeClass = match ($product->status()) {
                                            'In Stock' => 'bg-success',
                                            'Low Stock' => 'bg-warning text-dark',
                                            default => 'bg-danger',
                                        };
                                    @endphp

                                    <span class="badge status-badge {{ $badgeClass }}">
                                        {{ $product->status() }}
                                    </span>

                                </td>


                                <!-- Actions -->
                                <td>

                                    <div class="d-flex gap-1">

                                        <a
                                            href="{{ route('products.show', $product) }}"
                                            class="btn btn-sm btn-outline-primary"
                                            title="View"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a
                                            href="{{ route('products.edit', $product) }}"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Edit"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        @if($product->quantity === 0)
                                            <form
                                                method="POST"
                                                action="{{ route('products.deactivate', $product) }}"
                                                onsubmit="return confirm('Mark this product as inactive? It will be hidden from the system.');"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    title="Mark Inactive"
                                                >
                                                    <i class="bi bi-eye-slash"></i>
                                                </button>
                                            </form>
                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    class="text-center text-muted py-4"
                                >

                                    <i class="bi bi-search fs-3 d-block mb-2"></i>

                                    No products found matching your filters.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $products->links('pagination::bootstrap-5') }}
            </div>

        </div>

    </div>

</div>

@endsection
