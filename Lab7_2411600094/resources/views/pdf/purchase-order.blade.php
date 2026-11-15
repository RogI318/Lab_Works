{{--
    Lab 7 Part 4 Step 2: the purchase order document.

    ONE view, three jobs — it is attached to the "submitted" and "sent" mails
    now, and Part 8 reuses it for the on-demand export, because a vendor must
    never get a different number in an e-mail than in the file they print.

    Written as plain HTML with a <style> block: dompdf reads local CSS and
    ignores the app's Bootstrap bundle, so nothing here may depend on
    public/css/custom.css. A4 keeps it printable on the shop's laser printer.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $order->reference_number }}</title>
    <style>
        @page { margin: 26mm 14mm 18mm 14mm; }

        body  { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #2b2b2b; }
        .muted { color: #6d6d6d; }
        .right { text-align: right; }
        table { width: 100%; border-collapse: collapse; }

        /* Running head: dompdf repeats this on every page of a long order. */
        #head td { border: 0; padding: 0; vertical-align: top; }
        #head .brand { font-size: 17pt; font-weight: bold; color: #C9184A; letter-spacing: .4pt; }
        #head .rule  { border-bottom: 2.4pt solid #FB6F92; padding-bottom: 4pt; }
        #head .doc   { font-size: 12pt; font-weight: bold; color: #2b2b2b; }

        #meta td        { padding: 2.5pt 0; vertical-align: top; width: 25%; }
        #meta .label    { color: #6d6d6d; font-size: 8.5pt; text-transform: uppercase; letter-spacing: .4pt; }
        #meta .value    { font-size: 10pt; }
        #meta .value b  { font-size: 11pt; }

        #parties td       { width: 50%; padding: 8pt 0 2pt 0; vertical-align: top; border: 0; }
        #parties h3       { font-size: 8.5pt; color: #6d6d6d; text-transform: uppercase; letter-spacing: .4pt; margin: 0 0 3pt 0; }
        #parties .name    { font-weight: bold; }

        #lines { margin-top: 12pt; }
        #lines th { background: #FB6F92; color: #ffffff; font-size: 8.5pt; text-transform: uppercase;
                    letter-spacing: .4pt; text-align: left; padding: 5pt 6pt; }
        #lines th.n { text-align: right; }
        #lines td { padding: 5pt 6pt; border-bottom: .6pt solid #e6e6e6; }
        #lines td.n { text-align: right; }
        #lines .sku { color: #6d6d6d; font-size: 8.5pt; }

        #totals td { padding: 3pt 6pt; border: 0; }
        #totals .k { text-align: right; width: 60%; color: #6d6d6d; }
        #totals .v { text-align: right; width: 22%; }
        #totals tr.grand td { border-top: 1.2pt solid #2b2b2b; font-weight: bold; font-size: 11pt; padding-top: 6pt; }

        #notes { margin-top: 14pt; }
        #notes h3 { font-size: 8.5pt; color: #6d6d6d; text-transform: uppercase; letter-spacing: .4pt; margin: 0 0 3pt 0; }
        #notes p  { margin: 0 0 8pt 0; }

        #sign { margin-top: 26pt; }
        #sign td { width: 33%; border: 0; padding: 0 12pt 0 0; vertical-align: bottom; }
        #sign .line { border-bottom: .8pt solid #2b2b2b; height: 26pt; }
        #sign .cap  { font-size: 8.5pt; color: #6d6d6d; padding-top: 3pt; }

        #foot { position: fixed; bottom: -14mm; left: 0; width: 100%; font-size: 8pt; color: #6d6d6d; border-top: .6pt solid #e6e6e6; padding-top: 3pt; }
    </style>
</head>
<body>

    <table id="head">
        <tr>
            <td class="rule" style="width:62%;">
                <span class="brand">{{ config('app.name') }}</span><br>
                <span class="muted">Hardware, lumber &amp; building supplies</span>
            </td>
            <td class="rule right">
                <span class="doc">PURCHASE ORDER</span><br>
                <span style="font-size:11pt; font-weight:bold;">{{ $order->reference_number }}</span><br>
                <span class="muted">Status: {{ $order->statusLabel() }}</span>
            </td>
        </tr>
    </table>

    <table id="meta">
        <tr>
            <td>
                <div class="label">Order date</div>
                <div class="value">{{ $order->order_date->format('F j, Y') }}</div>
            </td>
            <td>
                <div class="label">Expected delivery</div>
                <div class="value">
                    {{ $order->expected_delivery_date ? $order->expected_delivery_date->format('F j, Y') : 'To be advised' }}
                </div>
            </td>
            <td>
                <div class="label">Payment terms</div>
                <div class="value">{{ $order->terms ?: ($order->supplier->payment_terms ?: 'Not specified') }}</div>
            </td>
            <td>
                <div class="label">Raised by</div>
                <div class="value">{{ $order->createdBy?->name ?? 'Reorder engine' }}</div>
            </td>
        </tr>
    </table>

    <table id="parties">
        <tr>
            <td>
                <h3>Supplier</h3>
                <div class="name">{{ $order->supplier->name }}</div>
                @if ($order->supplier->contact_person)
                    <div>Attn: {{ $order->supplier->contact_person }}</div>
                @endif
                @if ($order->supplier->address)
                    <div class="muted">{{ $order->supplier->address }}</div>
                @endif
                <div class="muted">
                    {{ collect([$order->supplier->email, $order->supplier->phone])->filter()->implode(' · ') }}
                </div>
            </td>
            <td>
                <h3>Deliver to</h3>
                <div class="name">{{ config('app.name') }}</div>
                <div class="muted">Receiving dock — present this document with the delivery note.</div>
                <div class="muted">Lead time on file: {{ $order->supplier->leadTimeLabel() }}</div>
            </td>
        </tr>
    </table>

    <table id="lines">
        <thead>
            <tr>
                <th style="width:6%;">#</th>
                <th>Item</th>
                <th class="n" style="width:12%;">Qty</th>
                <th class="n" style="width:18%;">Unit price</th>
                <th class="n" style="width:20%;">Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product->name }}
                        <div class="sku">SKU {{ $item->product->sku }}</div>
                    </td>
                    <td class="n">{{ number_format($item->quantity) }}</td>
                    <td class="n">PHP {{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="n">PHP {{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table id="totals">
        <tr>
            <td class="k">Subtotal ({{ number_format($order->orderedUnits()) }} unit(s) on {{ $order->items->count() }} line(s))</td>
            <td class="v"></td>
            <td class="v">PHP {{ number_format((float) $order->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="k">VAT {{ $order->taxRateLabel() }}</td>
            <td class="v"></td>
            <td class="v">PHP {{ number_format((float) $order->tax, 2) }}</td>
        </tr>
        <tr class="grand">
            <td class="k">TOTAL</td>
            <td class="v"></td>
            <td class="v">PHP {{ number_format((float) $order->total, 2) }}</td>
        </tr>
    </table>

    @if ($order->notes || $order->terms)
        <table id="notes">
            <tr>
                <td>
                    @if ($order->notes)
                        <h3>Notes</h3>
                        {{-- escaped first, then the line breaks turned back into markup:
                             the auto-generated warning is written on its own line. --}}
                        <p>{!! nl2br(e($order->notes)) !!}</p>
                    @endif

                    @if ($order->terms)
                        <h3>Terms</h3>
                        <p>{{ $order->terms }}</p>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <table id="sign">
        <tr>
            <td>
                <div class="line"></div>
                <div class="cap">Approved for {{ config('app.name') }}</div>
            </td>
            <td>
                <div class="line"></div>
                <div class="cap">Date</div>
            </td>
            <td>
                <div class="line"></div>
                <div class="cap">Acknowledged by supplier</div>
            </td>
        </tr>
    </table>

    <div id="foot">
        {{ $order->reference_number }} · generated {{ now()->format('F j, Y, g:i a') }} ·
        {{ $order->is_auto_generated ? 'written by the reorder engine' : 'typed by ' . ($order->createdBy?->name ?? 'a user') }}
    </div>

</body>
</html>
