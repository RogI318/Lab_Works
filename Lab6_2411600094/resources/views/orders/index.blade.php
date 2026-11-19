@extends('layouts.master')
@section('title', 'Purchase & Sales Orders')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pb-2 mb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-0">{{ $direction === 'purchase' ? 'Purchase Orders' : 'Sales Orders' }}</h2><small
                class="text-muted">Stock changes when an order is processed.</small>
        </div><a class="btn btn-primary" href="{{ route('orders.create', ['direction' => $direction]) }}">New
            {{ $direction === 'purchase' ? 'Purchase' : 'Sales' }} Order</a>
    </div>
    <form method="GET" action="{{ route($direction === 'purchase' ? 'purchase-orders.index' : 'sales-orders.index') }}"
        class="card shadow-hover mb-3 order-filter-form">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-lg-4"><label for="search"
                        class="form-label">{{ $direction === 'purchase' ? 'Search order or supplier' : 'Search order or customer' }}</label><input
                        id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Order number or {{ $direction === 'purchase' ? 'supplier' : 'customer' }}"></div>
                <div class="col-6 col-sm-6 col-lg-2"><label for="status" class="form-label">Status</label><select
                        id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                        <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                        <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Completed</option>
                        <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelled</option>
                    </select></div>
                <div class="col-6 col-sm-6 col-lg-2"><label for="date_from" class="form-label">From</label><input
                        type="date" id="date_from" name="date_from" class="form-control"
                        value="{{ $filters['date_from'] ?? '' }}"></div>
                <div class="col-6 col-sm-6 col-lg-2"><label for="date_to" class="form-label">To</label><input type="date"
                        id="date_to" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
                <div class="col-6 col-sm-6 col-lg-2">
                    <div class="order-filter-actions"><button class="btn btn-primary" type="submit">Filter</button><a
                            class="btn btn-outline-secondary"
                            href="{{ route($direction === 'purchase' ? 'purchase-orders.index' : 'sales-orders.index') }}">Reset</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <style>
        .order-filter-form .form-label {
            display: block;
            margin-bottom: .4rem;
            font-weight: 600;
        }

        .order-filter-form .form-control,
        .order-filter-form .form-select {
            min-width: 0;
            width: 100%;
        }

        .order-filter-actions {
            display: flex;
            gap: .5rem;
        }

        .order-filter-actions .btn {
            flex: 1 1 auto;
            white-space: nowrap;
        }

        @media (max-width: 575.98px) {
            .order-filter-form .card-body {
                padding: 1rem;
            }

            .order-filter-actions .btn {
                padding-inline: .65rem;
            }
        }
    </style>
    <div class="card shadow-hover">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Type</th>
                        <th>{{ $direction === 'purchase' ? 'Supplier' : 'Customer' }}</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="ordersRows">@forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ ucfirst($order->direction) }}</td>
                        <td>{{ $order->contact_name }}</td>
                        <td><span
                                class="badge {{ $order->status === 'completed' ? 'bg-success' : ($order->status === 'cancelled' ? 'bg-secondary' : ($order->approved_at ? 'bg-info text-dark' : 'bg-warning text-dark')) }}">{{ $order->status === 'pending' && $order->approved_at ? 'Approved' : ucfirst($order->status) }}</span>
                        </td>
                        <td>{{ $order->created_at->format('M j, Y') }}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('orders.show', $order) }}">View</a>
                        </td>
                </tr>@empty<tr>
                    <td colspan="6" class="text-center text-muted py-4">No orders yet.</td>@endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $orders->links() }}</div>
    </div>
@endsection
@push('scripts')
    <script>
        (() => { const body = document.getElementById('ordersRows'); if (!body || window.location.search) return; const esc = v => { const e = document.createElement('span'); e.textContent = v ?? ''; return e.innerHTML }; async function refresh() { try { const r = await fetch(@json(route($direction === 'purchase' ? 'purchase-orders.latest' : 'sales-orders.latest')), { headers: { Accept: 'application/json' } }); if (!r.ok) return; const rows = await r.json(); body.innerHTML = rows.length ? rows.map(o => { const label = o.status === 'pending' && o.approved ? 'Approved' : o.status[0].toUpperCase() + o.status.slice(1); const cls = o.status === 'completed' ? 'bg-success' : o.status === 'cancelled' ? 'bg-secondary' : o.approved ? 'bg-info text-dark' : 'bg-warning text-dark'; return `<tr><td>${esc(o.number)}</td><td>${@json(ucfirst($direction))}</td><td>${esc(o.contact)}</td><td><span class="badge ${cls}">${label}</span></td><td>${esc(o.created)}</td><td><a class="btn btn-sm btn-outline-primary" href="${esc(o.url)}">View</a></td></tr>` }).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr>' } catch (e) { } } setInterval(refresh, 5000) })();
    </script>
@endpush