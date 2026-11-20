@extends('layouts.master')

@section('title', 'Procurement Dashboard')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pb-2 mb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-0">Procurement Dashboard</h2>
            <small class="text-muted">Purchase and sales order activity</small>
        </div>
        @can('admin')
            <form method="POST" action="{{ route('procurement.reorders.generate') }}">
                @csrf
                <button class="btn btn-primary" type="submit">Generate reorder drafts</button>
            </form>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small">Open purchase orders</div>
                <div class="fs-3 fw-bold">{{ $metrics['open_purchase_orders'] }}</div>
                <a href="{{ route('procurement.purchase-orders.index') }}">View orders</a>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small">Awaiting approval</div>
                <div class="fs-3 fw-bold">{{ $metrics['awaiting_approval'] }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small">Awaiting receipt</div>
                <div class="fs-3 fw-bold">{{ $metrics['awaiting_receipt'] }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small">Confirmed sales</div>
                <div class="fs-3 fw-bold">{{ $metrics['confirmed_sales_orders'] }}</div>
                <a href="{{ route('procurement.sales-orders.index') }}">View sales orders</a>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small">Products at reorder point</div>
                <div class="fs-3 fw-bold">{{ $metrics['products_needing_reorder'] }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card stat-card h-100"><div class="card-body">
                <h5 class="card-title">Recent purchase orders</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Reference</th><th>Supplier</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td><a href="{{ route('procurement.purchase-orders.show', $order) }}">{{ $order->reference_number }}</a></td>
                                    <td>{{ $order->supplier->name ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span></td>
                                    <td class="text-end">₱{{ number_format((float) $order->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No procurement orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card stat-card h-100"><div class="card-body">
                <h5 class="card-title">Procurement tools</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('procurement.purchase-orders.index') }}">Purchase orders</a>
                    <a class="btn btn-outline-primary" href="{{ route('procurement.sales-orders.index') }}">Sales orders</a>
                    <a class="btn btn-outline-primary" href="{{ route('procurement.suppliers.index') }}">Supplier management</a>
                    <a class="btn btn-outline-primary" href="{{ route('procurement.supplier-performance') }}">Supplier performance</a>
                    <a class="btn btn-outline-primary" href="{{ route('procurement.product-settings.index') }}">Reorder settings</a>
                </div>
            </div></div>
        </div>
    </div>
@endsection
