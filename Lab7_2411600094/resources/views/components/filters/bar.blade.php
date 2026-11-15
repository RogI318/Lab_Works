@props([
    'title'   => 'Filter',
    'action',
    'reset'   => null,
    'submit'  => 'Search',
    'button'  => 'col-md-2',
])

{{-- Lab 7 Part 7 Step 3: the one filter card every listing page uses.

     The card, the gradient header, the GET form and the Reset link are all
     here so a page cannot drift away from them; the fields come in through
     the slot. Reset points at the plain route, which is the same page with
     no query string at all — the only link on these screens that leaves the
     filters behind. --}}
<div {{ $attributes->merge(['class' => 'card shadow-hover mb-4']) }}>
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ $title }}</h5>
        <a href="{{ $reset ?? $action }}" class="btn btn-outline-light btn-sm">Reset filters</a>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ $action }}" class="row g-3 align-items-end">
            {{ $slot }}

            {{-- ms-auto keeps the button at the right edge of its row, even
                 when the fields above wrapped onto a second line. --}}
            <div class="{{ $button }} ms-auto">
                <button class="btn btn-primary w-100" type="submit" aria-label="Apply filters">
                    <i class="bi bi-search{{ $submit === '' ? '' : ' me-1' }}"></i>{{ $submit }}
                </button>
            </div>
        </form>
    </div>
</div>
