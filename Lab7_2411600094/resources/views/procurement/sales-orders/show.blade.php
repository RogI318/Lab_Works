@extends('layouts.master')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom flex-wrap gap-2">
    <h2 class="mb-0">
        {{ $order->reference_number }}
        <span class="badge bg-{{ $order->status_color }} fs-6">{{ ucfirst($order->status) }}</span>
    </h2>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('procurement.sales-orders.index') }}" class="btn btn-outline-secondary">← Back</a>
        <a href="{{ route('procurement.sales-orders.print', $order) }}" target="_blank" class="btn btn-success">🖨 Print Receipt</a>

        @can('manage-stock')
            @if ($order->isDraft())
                <a href="{{ route('procurement.sales-orders.edit', $order) }}" class="btn btn-outline-primary">Edit</a>
            @endif

            @if ($order->canBeConfirmed())
                <form action="{{ route('procurement.sales-orders.confirm', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Confirm this sales order?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">Confirm</button>
                </form>
            @endif

            @if ($order->canBeDelivered())
                <form action="{{ route('procurement.sales-orders.deliver', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Mark as delivered? The stock will be reduced.');">
                    @csrf
                    <button type="submit" class="btn btn-success">Mark as Delivered</button>
                </form>
            @endif

            @if ($order->canBeCancelled())
                <form action="{{ route('procurement.sales-orders.cancel', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this sales order?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-dark">Cancel Order</button>
                </form>
            @endif
        @endcan
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h6 class="text-uppercase text-muted">Customer</h6>
                <div class="fs-5 fw-bold">{{ $order->customer_name }}</div>
                <div>{{ $order->customer_email ?: '—' }}</div>
                <div>{{ $order->customer_phone ?: '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h6 class="text-uppercase text-muted">Order details</h6>
                <div>Order date: <strong>{{ $order->order_date->format('M d, Y') }}</strong></div>
                <div>Created by: <strong>{{ $order->createdBy->name ?? '—' }}</strong></div>
                <div>Delivered: <strong>{{ $order->delivered_at ? $order->delivered_at->format('M d, Y g:i A') : '—' }}</strong></div>
                @if ($order->notes)
                    <div class="mt-2 text-muted">{{ $order->notes }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card stat-card">
    <div class="card-body">
        <h6 class="text-uppercase text-muted">Items</h6>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Line Total</th>
                        @if (in_array($order->status, ['draft', 'confirmed']))
                            <th class="text-center">In stock now</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product->sku ?? '—' }}</td>
                            <td>{{ $item->product->name ?? '—' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">₱{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="text-end">₱{{ number_format($item->line_total, 2) }}</td>
                            @if (in_array($order->status, ['draft', 'confirmed']))
                                <td class="text-center">
                                    @php $stock = (int) ($item->product->quantity ?? 0); @endphp
                                    <span class="{{ $stock < $item->quantity ? 'text-danger fw-bold' : 'text-muted' }}">{{ $stock }}</span>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th class="text-end">₱{{ number_format((float) $order->total, 2) }}</th>
                        @if (in_array($order->status, ['draft', 'confirmed']))
                            <th></th>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endsection