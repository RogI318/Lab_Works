@extends('procurement.emails.layout')

@section('title', 'Order Delivered')
@section('tag', 'Sales Order')

@section('content')
    <h2 style="margin-top:0;">Your order was delivered</h2>

    <p>
        Hello <strong>{{ $order->customer_name }}</strong>, your order
        <strong>{{ $order->reference_number }}</strong> was marked as delivered on
        <strong>{{ $order->delivered_at ? $order->delivered_at->format('M d, Y g:i A') : now()->format('M d, Y g:i A') }}</strong>.
        Thank you for your order.
    </p>

    <table width="100%" style="border-collapse:collapse; margin:16px 0;">
        <tr style="background-color:#F5F3F8;">
            <th align="left" style="padding:8px;">Item</th>
            <th align="center" style="padding:8px;">Qty</th>
            <th align="right" style="padding:8px;">Unit Price</th>
            <th align="right" style="padding:8px;">Line Total</th>
        </tr>
        @foreach ($order->items as $item)
            <tr>
                <td style="padding:8px; border-bottom:1px solid #eee;">{{ $item->product->name ?? '—' }}</td>
                <td align="center" style="padding:8px; border-bottom:1px solid #eee;">{{ $item->quantity }}</td>
                <td align="right" style="padding:8px; border-bottom:1px solid #eee;">₱{{ number_format((float) $item->unit_price, 2) }}</td>
                <td align="right" style="padding:8px; border-bottom:1px solid #eee;">₱{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="3" align="right" style="padding:8px;"><strong>Total</strong></td>
            <td align="right" style="padding:8px;"><strong>₱{{ number_format((float) $order->total, 2) }}</strong></td>
        </tr>
    </table>
@endsection