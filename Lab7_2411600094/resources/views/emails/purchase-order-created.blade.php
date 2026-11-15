{{--
    Lab 7 Part 4 Step 3: a purchase order has just been submitted, so the
    people who can approve it get the paper and a link to the decision.
--}}
@extends('emails.layout')

@section('heading', 'New purchase order awaiting approval')

@section('content')

    <p style="margin:0 0 16px 0; color:#330033;">
        <strong>{{ $order->createdBy?->name ?? 'Someone' }}</strong> submitted
        <strong>{{ $order->reference_number }}</strong> against
        <strong>{{ $order->supplier->name }}</strong> for
        <strong>PHP {{ number_format((float) $order->total, 2) }}</strong>.
        It is pending approval — it becomes a real commitment to the vendor only once a manager or administrator who did not raise it signs it off.
    </p>

    @include('emails.partials.order-summary')

    {{-- The same document the vendor will receive, generated from the shared
         PDF view so an approval is always read against the real paper. --}}
    <p style="margin:18px 0 0 0; font-size:13px; color:#8a6b78;">
        <strong style="color:#330033;">Attachment:</strong>
        {{ $order->reference_number }}.pdf — the purchase order document.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0 6px 0;">
        <tr>
            <td style="background-color:#FB6F92; border-radius:6px;">
                <a href="{{ route('purchase-orders.show', $order) }}"
                   style="display:inline-block; padding:11px 22px; color:#ffffff; font-size:14px; font-weight:bold; text-decoration:none;">
                    Review &amp; approve this order
                </a>
            </td>
        </tr>
    </table>

@endsection
