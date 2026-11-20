{{-- SALES ORDER: printable receipt (no sidebar, the print dialog opens by itself) --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $order->reference_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; padding: 16px; background: #f2f2f2; }
        .sheet { max-width: 760px; margin: 0 auto; background: #fff; padding: 24px; border: 1px solid #ddd; }
        .center { text-align: center; }
        h1 { font-size: 20px; margin: 0; }
        h2 { font-size: 16px; margin: 4px 0 0; letter-spacing: 1px; }
        .info { width: 100%; margin: 20px 0; font-size: 13px; border-collapse: collapse; }
        .info td { padding: 3px 0; vertical-align: top; }
        .info td.label { width: 120px; font-weight: bold; }
        .table-wrap { overflow-x: auto; }
        table.items { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.items th, table.items td { border-bottom: 1px solid #ccc; padding: 8px 6px; }
        table.items thead th { border-top: 2px solid #111; border-bottom: 2px solid #111; text-align: left; }
        .right { text-align: right; }
        .total td { border-top: 2px solid #111; border-bottom: none; font-weight: bold; font-size: 15px; }
        .notes { margin-top: 16px; font-size: 13px; }
        .signatures { display: flex; gap: 40px; margin-top: 56px; font-size: 12px; }
        .signatures div { flex: 1; border-top: 1px solid #111; padding-top: 4px; text-align: center; }
        .footer { margin-top: 24px; font-size: 11px; color: #555; }
        .thanks { margin-top: 28px; font-size: 14px; }
        .actions { max-width: 760px; margin: 0 auto 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        .actions button, .actions a { padding: 12px 18px; border: 1px solid #333; background: #fff; cursor: pointer; font-size: 15px; text-decoration: none; color: #111; border-radius: 6px; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { border: none; padding: 0; max-width: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route('procurement.sales-orders.show', $order) }}">← Back</a>
    </div>

    <div class="sheet">
        <div class="center">
            <h1>RogieMart Hardware | Inventory</h1>
            <h2>SALES ORDER RECEIPT</h2>
        </div>

        <table class="info">
            <tr>
                <td class="label">Reference:</td><td>{{ $order->reference_number }}</td>
                <td class="label">Order date:</td><td>{{ $order->order_date->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Customer:</td><td>{{ $order->customer_name }}</td>
                <td class="label">Status:</td><td>{{ ucfirst($order->status) }}</td>
            </tr>
            <tr>
                <td class="label">Email:</td><td>{{ $order->customer_email ?: '—' }}</td>
                <td class="label">Delivered:</td><td>{{ $order->delivered_at ? $order->delivered_at->format('M d, Y g:i A') : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Phone:</td><td>{{ $order->customer_phone ?: '—' }}</td>
                <td class="label">Prepared by:</td><td>{{ $order->createdBy->name ?? '—' }}</td>
            </tr>
        </table>

        <div class="table-wrap">
            <table class="items">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th class="right">Qty</th>
                        <th class="right">Unit Price</th>
                        <th class="right">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product->sku ?? '—' }}</td>
                            <td>{{ $item->product->name ?? '—' }}</td>
                            <td class="right">{{ $item->quantity }}</td>
                            <td class="right">₱{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="right">₱{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td colspan="4" class="right">TOTAL</td>
                        <td class="right">₱{{ number_format((float) $order->total, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if ($order->notes)
            <div class="notes"><strong>Notes:</strong> {{ $order->notes }}</div>
        @endif

        <div class="signatures">
            <div>Prepared by</div>
            <div>Received by (customer)</div>
        </div>

        <div class="thanks center">Thank you for your order!</div>
        <div class="footer center">Printed {{ now()->timezone('Asia/Manila')->format('M d, Y g:i A') }}</div>
    </div>

    <script>
        // open the print dialog by itself (a short delay so the page is drawn first)
        window.addEventListener('load', () => setTimeout(() => window.print(), 300));
    </script>
</body>
</html>