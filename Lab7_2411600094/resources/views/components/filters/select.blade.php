@props([
    'name'        => null,
    'label',
    'options'     => [],
    'value'       => null,
    'placeholder' => null,
    'col'         => 'col-md-3',
])

{{-- Lab 7 Part 7 Step 3: one dropdown that keeps whatever was chosen.

     Options arrive either as value => label (a pluck of categories, a status
     list with its counts in the label) or as a plain list, where the value is
     its own label. The comparison is string-to-string because a query string
     is always text while an id from the database is not. --}}
@php
    $current = (string) ($value ?? request((string) $name));

    $normalized = [];
    foreach ($options as $optionValue => $optionLabel) {
        $normalized[is_int($optionValue) ? $optionLabel : $optionValue] = $optionLabel;
    }
@endphp

<div class="{{ $col }}">
    <label class="form-label fw-semibold" for="filter-{{ $name }}">{{ $label }}</label>

    <select id="filter-{{ $name }}" name="{{ $name }}" class="form-select">
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($normalized as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current !== '' && $current === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
</div>
