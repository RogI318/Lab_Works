@extends('layouts.master')
@section('title', $direction === 'purchase' ? 'New Purchase Order' : 'New Sales Order')
@section('content')
    <div class="pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">New {{ $direction === 'purchase' ? 'Purchase' : 'Sales' }} Order</h2>
        <p class="text-muted mb-0">Creating an order does not change stock. Process it when goods arrive or are shipped.</p>
    </div>
    <form method="POST" action="{{ route('orders.store') }}">@csrf<input type="hidden" name="direction"
            value="{{ $direction }}">
        <div class="card shadow-hover mb-3">
            <div class="card-body row g-3">
                <div class="col-md-6"><label
                        class="form-label">{{ $direction === 'purchase' ? 'Supplier' : 'Customer' }}</label><input
                        class="form-control" name="contact_name" required
                        value="{{ old('contact_name') }}">@error('contact_name')
                        <div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <h5>Order item</h5>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Product</label><select class="form-select"
                                name="items[0][product_id]" required>
                                <option value="">Choose product</option>@foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }}) — {{ $p->quantity }} in stock
                                </option>@endforeach
                            </select></div>
                        <div class="col-md-3"><label class="form-label">Quantity</label><input class="form-control"
                                type="number" min="1" name="items[0][quantity]" required></div>
                        <div class="col-md-3"><label class="form-label">Unit price</label><input class="form-control"
                                type="number" min="0" step="0.01" name="items[0][unit_price]"></div>
                    </div>
                </div>
                <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes"
                        rows="2">{{ old('notes') }}</textarea></div>
            </div>
        </div><button class="btn btn-primary">Save Order</button> <a class="btn btn-outline-secondary"
            href="{{ route($direction === 'purchase' ? 'purchase-orders.index' : 'sales-orders.index') }}">Cancel</a>
    </form>
@endsection