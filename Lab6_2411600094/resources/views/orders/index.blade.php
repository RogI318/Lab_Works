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
    <div class="card shadow-hover">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Type</th>
                        <th>Supplier / Customer</th>
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
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('orders.show', $order) }}">View</a></td>
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
        (() => { const body = document.getElementById('ordersRows'); if (!body) return; const esc = v => { const e = document.createElement('span'); e.textContent = v ?? ''; return e.innerHTML }; async function refresh() { try { const r = await fetch(@json(route($direction === 'purchase' ? 'purchase-orders.latest' : 'sales-orders.latest')), { headers: { Accept: 'application/json' } }); if (!r.ok) return; const rows = await r.json(); body.innerHTML = rows.length ? rows.map(o => { const label = o.status === 'pending' && o.approved ? 'Approved' : o.status[0].toUpperCase() + o.status.slice(1); const cls = o.status === 'completed' ? 'bg-success' : o.status === 'cancelled' ? 'bg-secondary' : o.approved ? 'bg-info text-dark' : 'bg-warning text-dark'; return `<tr><td>${esc(o.number)}</td><td>${@json(ucfirst($direction))}</td><td>${esc(o.contact)}</td><td><span class="badge ${cls}">${label}</span></td><td>${esc(o.created)}</td><td><a class="btn btn-sm btn-outline-primary" href="${esc(o.url)}">View</a></td></tr>` }).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr>' } catch (e) { } } setInterval(refresh, 5000) })();
    </script>
@endpush