{{--
    Lab 7 Part 4 Step 3: the digest the reorder sweep mails once per run.
    One mail, one table — the alternative (one mail per short product) is what
    Lab 6's bell does for a single item, and 23 SKUs would be 23 inboxes.
--}}
@extends('emails.layout')

@section('heading', 'Low stock digest — ' . $count . ' item(s) at or below their reorder point')

@section('content')

    <p style="margin:0 0 16px 0; color:#330033;">
        The reorder check ran on <strong>{{ $checkedAt }}</strong> over a
        <strong>{{ $lookbackDays }}-day</strong> demand window.
        {{ $raised }} of these items went onto a new draft purchase order; the rest are already covered by an
        open order or have no supplier on file, so the suggestion column shows what is still to do rather than
        what was bought.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <tr>
            <th align="left"  style="background-color:#FB6F92; color:#ffffff; font-size:12px; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px;">Product</th>
            <th align="left"  style="background-color:#FB6F92; color:#ffffff; font-size:12px; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px;">Supplier</th>
            <th align="right" style="background-color:#FB6F92; color:#ffffff; font-size:12px; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px; width:70px;">On hand</th>
            <th align="right" style="background-color:#FB6F92; color:#ffffff; font-size:12px; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px; width:86px;">Reorder pt</th>
            <th align="right" style="background-color:#FB6F92; color:#ffffff; font-size:12px; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px; width:90px;">Suggested</th>
        </tr>

        @foreach ($items as $item)
            <tr>
                <td style="padding:9px 10px; border-bottom:1px solid #FFE5EC; font-size:14px;">
                    <a href="{{ route('products.show', $item['id']) }}" style="color:#C9184A; text-decoration:none; font-weight:bold;">
                        {{ $item['name'] }}
                    </a>
                    <br>
                    <span style="font-size:12px; color:#8a6b78;">{{ $item['sku'] }}</span>
                </td>
                <td style="padding:9px 10px; border-bottom:1px solid #FFE5EC; font-size:13px; color:#330033;">
                    {{ $item['supplier'] }}
                    @if ($item['on_order'] > 0)
                        <br><span style="font-size:12px; color:#8a6b78;">{{ number_format($item['on_order']) }} on an open order</span>
                    @endif
                </td>
                <td align="right" style="padding:9px 10px; border-bottom:1px solid #FFE5EC; font-size:14px; color:{{ $item['on_hand'] == 0 ? '#C9184A' : '#330033' }}; font-weight:{{ $item['on_hand'] == 0 ? 'bold' : 'normal' }};">
                    {{ number_format($item['on_hand']) }}
                </td>
                <td align="right" style="padding:9px 10px; border-bottom:1px solid #FFE5EC; font-size:14px; color:#330033;">
                    {{ number_format($item['point']) }}
                </td>
                <td align="right" style="padding:9px 10px; border-bottom:1px solid #FFE5EC; font-size:14px; font-weight:bold; color:#330033;">
                    {{ $item['suggested'] > 0 ? number_format($item['suggested']) . ' unit(s)' : 'covered' }}
                </td>
            </tr>
        @endforeach
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0 6px 0;">
        <tr>
            <td style="background-color:#FB6F92; border-radius:6px;">
                <a href="{{ route('purchase-orders.index', ['status' => 'draft']) }}"
                   style="display:inline-block; padding:11px 22px; color:#ffffff; font-size:14px; font-weight:bold; text-decoration:none;">
                    Review the draft purchase orders
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:14px 0 0 0; font-size:12px; color:#8a6b78;">
        Suggested quantities are whole packages from the supplier's price agreement and already ignore units
        still owed on an open order — that is why running the check twice raises nothing twice.
    </p>

@endsection
