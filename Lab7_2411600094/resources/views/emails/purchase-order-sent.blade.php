{{--
    Lab 7 Part 4 Step 3: the vendor's copy of the order. The same view serves the
    internal carbon copy, so it states plainly what the attachment is; the
    dashboard link below is only meaningful to somebody with an account.
--}}
@extends('emails.layout')

@section('heading', 'Purchase order ' . $order->reference_number . ' — confirmed')

@section('content')

    <p style="margin:0 0 16px 0; color:#330033;">
        This confirms purchase order <strong>{{ $order->reference_number }}</strong> with
        <strong>{{ $order->supplier->name }}</strong>, dated
        <strong>{{ $order->order_date->format('F j, Y') }}</strong> and due
        <strong>{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('F j, Y') : 'as soon as possible' }}</strong>.
        The signed document is attached as
        <strong>{{ $order->reference_number }}.pdf</strong>; the line detail is repeated below so nothing has to be opened to be read.
    </p>

    @include('emails.partials.order-summary')

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 0 0; border-collapse:collapse;">
        <tr>
            <td style="padding:6px 0; font-size:13px; color:#8a6b78; width:38%;">Deliver to</td>
            <td style="padding:6px 0; font-size:14px; color:#330033;">
                {{ config('app.name') }}<br>
                <span style="font-size:13px; color:#8a6b78;">
                    {{ $order->supplier->contact_person ? 'Attn: ' . $order->supplier->contact_person : '' }}
                    @if ($order->supplier->email)
                        · {{ $order->supplier->email }}
                    @endif
                    @if ($order->supplier->phone)
                        · {{ $order->supplier->phone }}
                    @endif
                </span>
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0 6px 0;">
        <tr>
            <td style="background-color:#FB6F92; border-radius:6px;">
                <a href="{{ route('purchase-orders.show', $order) }}"
                   style="display:inline-block; padding:11px 22px; color:#ffffff; font-size:14px; font-weight:bold; text-decoration:none;">
                    Open the order (staff sign-in required)
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:14px 0 0 0; font-size:12px; color:#8a6b78;">
        Suppliers without an account should work from the attached PDF — nothing in this mail is payable without the signed order document.
    </p>

@endsection
