@extends('layouts.master')

@section('title', 'Reorder Settings')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pb-2 mb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-0">Reorder Settings</h2>
            <small class="text-muted">Package quantities and supplier costs used by automatic purchasing</small>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('procurement.dashboard') }}">Back to Procurement</a>
    </div>

    <div class="card stat-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Primary supplier</th>
                        <th>Package size</th>
                        <th>Minimum order quantity</th>
                        <th>Default cost (PHP)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><strong>{{ $product->name }}</strong><div class="small text-muted">{{ $product->sku }}</div></td>
                            <td>{{ $product->supplierRecord->name ?? '—' }}</td>
                            <td colspan="4">
                                <form method="POST" action="{{ route('procurement.product-settings.update', $product) }}" class="row g-2 align-items-center">
                                    @csrf
                                    @method('PATCH')
                                    <div class="col-md-3">
                                        <input aria-label="Package size for {{ $product->name }}" type="number" min="1" name="package_size" class="form-control" value="{{ $product->package_size ?? 1 }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input aria-label="Minimum order quantity for {{ $product->name }}" type="number" min="0" name="min_order_quantity" class="form-control" value="{{ $product->min_order_quantity ?? 1 }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input aria-label="Default cost for {{ $product->name }}" type="number" min="0" step="0.01" name="cost_price" class="form-control" value="{{ $product->cost_price }}">
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-outline-primary" type="submit">Save</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $products->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
