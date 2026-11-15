@props([
    'name',
    'label',
    'value'       => null,
    'placeholder' => null,
    'col'         => 'col-md-3',
    'type'        => 'text',
    'min'         => null,
    'max'         => null,
    'step'        => null,
])

{{-- Lab 7 Part 7 Step 3: one labelled box that keeps whatever was searched.

     The value is read from the query string unless the caller hands one in,
     so a page that paginates or re-applies never loses the term — and a
     hand-written ?search= shows up in the box exactly as the query used it. --}}
@php $current = $value ?? request($name); @endphp

<div class="{{ $col }}">
    <label class="form-label fw-semibold" for="filter-{{ $name }}">{{ $label }}</label>

    <input type="{{ $type }}"
           id="filter-{{ $name }}"
           name="{{ $name }}"
           class="form-control"
           value="{{ $current }}"
           @if ($placeholder !== null) placeholder="{{ $placeholder }}" @endif
           @if ($type === 'number')
               min="{{ $min ?? 0 }}"
               step="{{ $step ?? 1 }}"
               inputmode="numeric"
               @if ($max !== null) max="{{ $max }}" @endif
           @endif>
</div>
