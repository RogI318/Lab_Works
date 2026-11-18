@extends('layouts.app')

@section('title', $category->name)

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="mb-1">{{ $category->name }}</h2>
        <p class="text-muted mb-0">Category details</p>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Category Name</label>
                    <input type="text" class="form-control" value="{{ $category->name }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Products</label>
                    <input type="text" class="form-control" value="{{ $products->count() }}" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" rows="3" readonly>{{ $category->description }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <a href="{{ route('categories.edit', $category) }}" class="btn btn-primary">Edit</a>
                <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Back to Categories</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Products using this category</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>{{ $product->sku }}</td>
                                <td><a href="{{ route('products.show', $product) }}" class="text-decoration-none">{{ $product->name }}</a></td>
                                <td>{{ $product->quantity }}</td>
                                <td>
                                    @if ($product->isOutOfStock())
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @elseif ($product->isLowStock())
                                        <span class="badge bg-warning text-dark">Low Stock</span>
                                    @else
                                        <span class="badge bg-success">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No products use this category yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection
