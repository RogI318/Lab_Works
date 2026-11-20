@extends('layouts.master')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom flex-wrap gap-2">
    <h2 class="mb-0">Sales Orders</h2>
    @can('manage-stock')
        <a href="{{ route('procurement.sales-orders.create') }}" class="btn" style="background-color: var(--theme-accent); color: white;">+ New Sales Order</a>
    @endcan
</div>

<form method="GET" action="{{ route('procurement.sales-orders.index') }}" class="row g-2 mb-3">
    <div class="col-12 col-md-5">
        <input type="text" name="search" class="form-control" placeholder="Search reference or customer" value="{{ request('search') }}">
    </div>
    <div class="col-8 col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach (\App\Models\SalesOrder::STATUSES as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-4 col-md-2">
        <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
    </div>
</form>

<div class="card stat-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="fw-bold"><a href="{{ route('procurement.sales-orders.show', $order) }}">{{ $order->reference_number }}</a></td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $order->order_date->format('M d, Y') }}</td>
                            <td class="text-center">{{ $order->items_count }}</td>
                            <td class="text-end">₱{{ number_format((float) $order->total, 2) }}</td>
                            <td><span class="badge bg-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span></td>
                            <td>{{ $order->createdBy->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('procurement.sales-orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No sales orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</div>

@endsection