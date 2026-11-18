@extends('layouts.master')
@section('title', 'Product Trash')
@section('content')
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Product Trash</h2>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Back to Inventory</a>
    </div>
    <div class="card shadow-hover">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Deleted</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td>{{ $product->deleted_at?->format('M j, Y') }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('products.restore', $product->id) }}">@csrf<button
                                        class="btn btn-sm btn-outline-primary">Restore</button></form>
                            </td>
                        </tr>
                    @empty<tr>
                        <td colspan="5" class="text-center text-muted py-4">Trash is empty.</td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $products->links() }}</div>
    </div>
@endsection