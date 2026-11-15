@props([
    'label',
    'fromName'    => 'from',
    'toName'      => 'to',
    'fromLabel'   => 'From',
    'toLabel'     => 'To',
    'col'         => 'col-md-3',
])

{{-- Lab 7 Part 7 Step 3: a date range as one field.

     The two boxes share one label and sit side by side, which is what a
     range is: a question with two ends. Both names carry straight through to
     the query string, so the old hand-typed ?from=&to= and the ledger's
     ?date_from=&date_to= URLs both keep working — a page picks the names it
     has always used. --}}
@php
    $fromValue = request((string) $fromName);
    $toValue   = request((string) $toName);
@endphp

<div class="{{ $col }}">
    <label class="form-label fw-semibold">{{ $label }}</label>

    <div class="d-flex align-items-center gap-2">
        <input type="date" class="form-control" name="{{ $fromName }}"
               aria-label="{{ $fromLabel }}" value="{{ $fromValue }}">

        <span class="text-muted small">to</span>

        <input type="date" class="form-control" name="{{ $toName }}"
               aria-label="{{ $toLabel }}" value="{{ $toValue }}">
    </div>
</div>
