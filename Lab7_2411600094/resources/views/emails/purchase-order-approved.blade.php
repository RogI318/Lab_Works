{{--
    Lab 7 Part 4 Step 3: the buyer's own order was approved — the go-ahead to
    expect the delivery. Only the creator receives this one.
--}}
@extends('emails.layout')

@section('heading', 'Purchase order approved')

@section('content')

    <p style="margin:0 0 16px 0; color:#330033;">
        Good news: <strong>{{ $order->reference_number }}</strong> was approved by
        <strong>{{ $approverName }}</strong> on
        <strong>{{ $order->approved_at ? $order->approved_at->format('F j, Y') : now()->format('F j, Y') }}</strong>
        for <strong>PHP {{ number_format((float) $order->total, 2) }}</strong>.
        It can now be sent to {{ $order->supplier->name }}.
    </p>

    @include('emails.partials.order-summary')

    <p style="margin:18px 0 0 0; font-size:13px; color:#8a6b78;">
        @if ($order->expected_delivery_date)
            Expected on the shelf by <strong style="color:#330033;">{{ $order->expected_delivery_date->format('F j, Y') }}</strong>
            ({{ $order->supplier->leadTimeLabel() }} lead time).
        @else
            This order has no expected delivery date yet — set one before sending it.
        @endif
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0 6px 0;">
        <tr>
            <td style="background-color:#FB6F92; border-radius:6px;">
                <a href="{{ route('purchase-orders.show', $order) }}"
                   style="display:inline-block; padding:11px 22px; color:#ffffff; font-size:14px; font-weight:bold; text-decoration:none;">
                    Open {{ $order->reference_number }}
                </a>
            </td>
        </tr>
    </table>

@endsection
