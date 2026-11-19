@extends('layouts.master')
@section('title', $supplier->name)
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pb-2 mb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1">{{ $supplier->name }}</h2><span
                class="badge {{ $supplier->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('suppliers.index') }}"><i class="bi bi-arrow-left me-1"></i>Back
            to Suppliers</a>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Contact person</dt>
                        <dd class="col-sm-8">{{ $supplier->contact_person ?: 'Not provided' }}</dd>
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">@if($supplier->email)<a
                        href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a>@else No email @endif</dd>
                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">@if($supplier->phone)<a
                        href="tel:{{ $supplier->phone }}">{{ $supplier->phone }}</a>@else Not provided @endif</dd>
                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8">{{ $supplier->address ?: 'Not provided' }}</dd>
                        <dt class="col-sm-4">Lead time</dt>
                        <dd class="col-sm-8">{{ $supplier->lead_time_days ?? 0 }} days</dd>
                        <dt class="col-sm-4">Notes</dt>
                        <dd class="col-sm-8">{{ $supplier->notes ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Supplier Summary</h5>
                </div>
                <div class="card-body">
                    <p><strong>Products supplied:</strong> {{ $supplier->products()->count() }}</p>
                    <p><strong>Supplier since:</strong> {{ $supplier->created_at?->format('M j, Y') }}</p>
                    <form method="POST" action="{{ route('suppliers.status', $supplier) }}">@csrf @method('PATCH')<button
                            class="btn btn-outline-{{ $supplier->is_active ? 'secondary' : 'success' }}">{{ $supplier->is_active ? 'Mark Inactive' : 'Mark Active' }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Products from {{ $supplier->name }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>On hand</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td>{{ $product->brand?->name ?? '—' }}</td>
                            <td>{{ $product->quantity }}</td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('products.show', $product) }}">View
                                    Product</a></td>
                    </tr>@empty<tr>
                        <td colspan="6" class="text-center text-muted py-4">No products assigned to this supplier.</td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $products->links() }}</div>
    </div>
@endsection