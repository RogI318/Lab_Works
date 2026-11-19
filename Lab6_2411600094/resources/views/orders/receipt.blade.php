<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $order->direction === 'purchase' ? 'Purchase' : 'Sales' }} Receipt {{ $order->order_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f3f4f6;
            color: #212529;
        }

        .btn-print {
            background-color: #FB6F92;
            border-color: #FB6F92;
            color: #fff;
        }

        .btn-print:hover,
        .btn-print:focus {
            background-color: #e5527a;
            border-color: #e5527a;
            color: #fff;
        }

        .receipt {
            max-width: 850px;
            margin: 32px auto;
            padding: 40px;
            background: #fff;
        }

        @media print {
            body {
                background: #fff;
            }

            .receipt {
                max-width: none;
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            thead {
                display: table-header-group;
            }

            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <main class="receipt shadow-sm">
        <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
            <div>
                <h1 class="h3 mb-1">
                    {{ $order->direction === 'purchase' ? 'Purchase Order Receipt' : 'Sales Order Receipt' }}</h1>
                <div class="text-muted">{{ config('app.name') }}</div>
            </div>
            <div class="text-end">
                <div class="fw-bold fs-5">{{ $order->order_number }}</div>
                <div>{{ $order->created_at?->format('M j, Y g:i A') }}</div>
                <span
                    class="badge text-bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'cancelled' ? 'secondary' : 'warning') }}">{{ ucfirst($order->status) }}</span>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-sm-6">
                <div class="text-muted small">{{ $order->direction === 'purchase' ? 'Supplier' : 'Customer' }}</div>
                <strong>{{ $order->contact_name }}</strong>
            </div>
            <div class="col-sm-6 text-sm-end">
                <div class="text-muted small">Recorded by</div><strong>{{ $order->user?->name ?? 'Unknown' }}</strong>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->product?->name ?? 'Deleted product' }}</td>
                            <td>{{ $item->product?->sku ?? '—' }}</td>
                            <td class="text-end">{{ number_format($item->quantity) }}</td>
                            <td class="text-end">PHP {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="text-end">PHP {{ number_format($item->quantity * (float) $item->unit_price, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th class="text-end">PHP
                            {{ number_format($order->items->sum(fn($item) => $item->quantity * (float) $item->unit_price), 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($order->notes)
            <div class="mt-4">
                <div class="text-muted small">Notes</div>
                <p class="mb-0">{{ $order->notes }}</p>
        </div>@endif
        @if($order->approved_at)
            <div class="mt-3 small text-muted">Approved by {{ $order->approver?->name ?? 'Admin' }} on
        {{ $order->approved_at->format('M j, Y g:i A') }}.</div>@endif
        @if($order->completed_at)
        <div class="small text-muted">Processed on {{ $order->completed_at->format('M j, Y g:i A') }}.</div>@endif

        <div class="d-flex justify-content-end gap-2 mt-4 no-print">
            <a class="btn btn-outline-secondary" href="{{ route('orders.show', $order) }}">Back to Order</a>
            <button class="btn btn-print" type="button" onclick="window.print()"><i
                    class="bi bi-printer me-1"></i>Print</button>
        </div>
        <p class="text-center text-muted small border-top pt-3 mt-4 mb-0">Generated {{ now()->format('M j, Y g:i A') }}
        </p>
    </main>
</body>

</html>