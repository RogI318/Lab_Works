@extends('layouts.master')

@section('title', 'Inventory')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Inventory</h2>
        <div class="btn-toolbar gap-2">
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Add Product
            </a>
        </div>
    </div>

    {{-- ===== Filter Panel (Lab 7 Part 7 Step 3: the shared filter components) ===== --}}
    <x-filters.bar title="Filter Inventory" :action="route('products.index')" submit="" button="col-md-1">
        <x-filters.text name="search" label="Search"
                        placeholder="Name, SKU or supplier..." col="col-md-3" />

        <x-filters.select name="category_id" label="Category" col="col-md-2"
                          placeholder="All Categories" :options="$categories->pluck('name', 'id')" />

        <x-filters.select name="brand_id" label="Brand" col="col-md-2"
                          placeholder="All Brands" :options="$brands->pluck('name', 'id')" />

        <x-filters.select name="supplier_id" label="Supplier" col="col-md-2"
                          placeholder="All Suppliers" :options="$suppliers->pluck('name', 'id')" />

        <x-filters.select name="status" label="Stock Status" col="col-md-2"
                          placeholder="All Statuses" :options="App\Models\Product::STOCK_STATUSES" />

        <x-filters.select name="sort" label="Sort by" col="col-md-2" :options="[
                            'name'          => 'Name (A to Z)',
                            'quantity'      => 'Quantity (most first)',
                            'unit_price'    => 'Unit price (highest first)',
                            'newest'        => 'Recently added',
                          ]" />
    </x-filters.bar>

    {{-- ===== Product table ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">{{ request('trashed') ? 'Deleted Products' : 'All Products' }}</h5>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-light text-dark">{{ $products->total() }} item{{ $products->total() !== 1 ? 's' : '' }}</span>
                @if (Auth::user()->is_admin)
                    @if (request('trashed'))
                        <a href="{{ route('products.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>All products
                        </a>
                    @else
                        <a href="{{ route('products.index', ['trashed' => 1]) }}" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-archive me-1"></i>Deleted
                        </a>
                    @endif
                @endif
                <a href="{{ route('reports.index') }}" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i>Reports
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle data-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Low Stock Level</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td><strong>{{ $product->sku }}</strong></td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category->name }}</td>
                                <td>{{ $product->brand->name }}</td>
                                <td class="text-end fw-bold {{ $product->needsReorder() ? 'text-danger' : '' }}">{{ $product->quantity }}</td>
                                <td class="text-end">{{ $product->reorder_level }}</td>
                                <td class="text-end">{{ 'PHP ' . number_format((float) $product->unit_price, 2) }}</td>
                                <td class="text-end">{{ 'PHP ' . number_format($product->stockValue(), 2) }}</td>
                                <td>
                                    <span class="badge {{ $product->statusBadgeClass() }}">{{ $product->stockStatus() }}</span>
                                    @if ($product->trashed())
                                        <span class="badge bg-secondary ms-1">Deleted</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if ($product->trashed())
                                        {{-- Soft-deleted: restore, or erase for good (removes its ledger too). --}}
                                        <form action="{{ route('products.restore', $product) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button class="btn btn-sm btn-success" title="Restore">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Permanently erase {{ $product->name }} AND its {{ $product->transactions()->count() }} stock movement(s)? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" title="Delete permanently">
                                                <i class="bi bi-x-octagon"></i>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-info text-white" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete {{ $product->name }}? Its stock movements are kept for the reports, and an admin can restore the product.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="bi bi-inbox fs-1 d-block text-muted"></i>
                                    <p class="text-muted mb-0">No products match your filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $products->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

@endsection
