{{--
    Lab 7 Part 4 Step 3: the order block shared by the three purchase-order
    e-mails, so an approval mail and the vendor's copy of the order can never
    disagree about what was on it.

    Needs $order loaded with items.product.
--}}

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; margin:0 0 18px 0;">
    <tr>
        <td style="padding:6px 0; font-size:13px; color:#8a6b78; width:38%;">Reference</td>
        <td style="padding:6px 0; font-size:14px; font-weight:bold; color:#330033;">{{ $order->reference_number }}</td>
    </tr>
    <tr>
        <td style="padding:6px 0; font-size:13px; color:#8a6b78;">Supplier</td>
        <td style="padding:6px 0; font-size:14px; color:#330033;">
            {{ $order->supplier->name }}
            @if ($order->supplier->contact_person)
                <span style="color:#8a6b78;">— {{ $order->supplier->contact_person }}</span>
            @endif
        </td>
    </tr>
    <tr>
        <td style="padding:6px 0; font-size:13px; color:#8a6b78;">Order date</td>
        <td style="padding:6px 0; font-size:14px; color:#330033;">{{ $order->order_date->format('F j, Y') }}</td>
    </tr>
    <tr>
        <td style="padding:6px 0; font-size:13px; color:#8a6b78;">Expected delivery</td>
        <td style="padding:6px 0; font-size:14px; color:#330033;">
            {{ $order->expected_delivery_date ? $order->expected_delivery_date->format('F j, Y') : 'not set' }}
        </td>
    </tr>
    <tr>
        <td style="padding:6px 0; font-size:13px; color:#8a6b78;">Raised by</td>
        <td style="padding:6px 0; font-size:14px; color:#330033;">{{ $order->createdBy?->name ?? 'the system' }}</td>
    </tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;">
    <tr>
        <th align="left"  style="background-color:#FB6F92; color:#ffffff; font-size:12px; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px;">Item</th>
        <th align="right" style="background-color:#FB6F92; color:#ffffff; font-size:12px; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px; width:64px;">Qty</th>
        <th align="right" style="background-color:#FB6F92; color:#ffffff; font-size:12px; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px; width:96px;">Unit price</th>
        <th align="right" style="background-color:#FB6F92; color:#ffffff; font-size:12px; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px; width:104px;">Line total</th>
    </tr>

    @foreach ($order->items as $item)
        <tr>
            <td style="padding:9px 10px; border-bottom:1px solid #FFE5EC; font-size:14px; color:#330033;">
                {{ $item->product->name }}
                <span style="color:#8a6b78; font-size:12px;">({{ $item->product->sku }})</span>
            </td>
            <td align="right" style="padding:9px 10px; border-bottom:1px solid #FFE5EC; font-size:14px; color:#330033;">{{ number_format($item->quantity) }}</td>
            <td align="right" style="padding:9px 10px; border-bottom:1px solid #FFE5EC; font-size:14px; color:#330033;">PHP {{ number_format((float) $item->unit_price, 2) }}</td>
            <td align="right" style="padding:9px 10px; border-bottom:1px solid #FFE5EC; font-size:14px; font-weight:bold; color:#330033;">PHP {{ number_format($item->line_total, 2) }}</td>
        </tr>
    @endforeach

    <tr>
        <td align="right" colspan="3" style="padding:8px 10px 2px 10px; font-size:13px; color:#8a6b78;">Subtotal</td>
        <td align="right" style="padding:8px 10px 2px 10px; font-size:13px; color:#330033;">PHP {{ number_format((float) $order->subtotal, 2) }}</td>
    </tr>
    <tr>
        <td align="right" colspan="3" style="padding:2px 10px; font-size:13px; color:#8a6b78;">VAT ({{ $order->taxRateLabel() }})</td>
        <td align="right" style="padding:2px 10px; font-size:13px; color:#330033;">PHP {{ number_format((float) $order->tax, 2) }}</td>
    </tr>
    <tr>
        <td align="right" colspan="3" style="padding:10px; background-color:#FFE5EC; font-size:14px; font-weight:bold; color:#330033;">Total</td>
        <td align="right" style="padding:10px; background-color:#FFE5EC; font-size:14px; font-weight:bold; color:#330033;">PHP {{ number_format((float) $order->total, 2) }}</td>
    </tr>
</table>

@if ($order->terms)
    <p style="margin:16px 0 0 0; font-size:13px; color:#8a6b78;">
        <strong style="color:#330033;">Terms:</strong> {{ Str::limit($order->terms, 200) }}
    </p>
@endif

@if ($order->notes)
    <p style="margin:10px 0 0 0; font-size:13px; color:#8a6b78;">
        <strong style="color:#330033;">Notes:</strong> {{ Str::limit($order->notes, 400) }}
    </p>
@endif
