{{--
    Lab 7 Part 5 Step 2: the five most recent purchase orders.
    $orders is the cached { rows, generated_at } pair from ViewServiceProvider;
    the rows are already limited to five and to the columns printed here.
--}}
<div class="card shadow-hover h-100" id="widget-recent-orders" data-widget="recent-orders">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-receipt-cutoff me-1"></i>Recent Purchase Orders</h5>
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-light btn-sm">All orders</a>
    </div>
    <div class="card-body activity-scroll">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr><th>Reference</th><th>Supplier</th><th>Status</th><th class="text-end">Total</th></tr>
            </thead>
            <tbody data-widget-body>
                @forelse ($orders['rows'] as $order)
                    <tr>
                        <td>
                            <a href="{{ $order['url'] }}" class="text-decoration-none"><code>{{ $order['reference'] }}</code></a>
                            <br><small class="text-muted">{{ $order['order_date'] }}{{ $order['auto'] ? ' - auto-generated' : '' }}</small>
                        </td>
                        <td class="small">{{ $order['supplier'] }}</td>
                        <td><span class="badge {{ $order['badge_class'] }}">{{ $order['status_label'] }}</span></td>
                        <td class="text-end">{{ number_format($order['total'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">No purchase orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
