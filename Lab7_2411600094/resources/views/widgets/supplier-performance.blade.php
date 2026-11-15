{{--
    Lab 7 Part 5 Step 2: top five suppliers by on-time delivery.
    $performance is the cached aggregate the reorder observer keeps honest;
    Part 6 swaps its body for the full performance service without touching
    this file. The bar colour comes with the data, so the widget and the
    polling JSON always agree.
--}}
<div class="card shadow-hover h-100" id="widget-supplier-performance" data-widget="supplier-performance">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-truck me-1"></i>Supplier Performance</h5>
        <a href="{{ route('suppliers.index') }}" class="btn btn-light btn-sm">Suppliers</a>
    </div>
    <div class="card-body activity-scroll">
        <ul class="list-unstyled mb-0" data-widget-body>
            @forelse ($performance['rows'] as $supplier)
                <li class="mb-3">
                    <div class="d-flex justify-content-between align-items-baseline small">
                        <a href="{{ $supplier['url'] }}" class="text-decoration-none fw-semibold">{{ $supplier['name'] }}</a>
                        <span class="text-muted">{{ number_format($supplier['percent'], 1) }} % ({{ $supplier['on_time'] }}/{{ $supplier['received'] }})</span>
                    </div>
                    <div class="progress" role="progressbar" aria-label="On-time delivery"
                         aria-valuenow="{{ $supplier['percent'] }}" aria-valuemin="0" aria-valuemax="100"
                         style="height: 8px;">
                        <div class="progress-bar {{ $supplier['bar_class'] }}"
                             style="width: {{ min(100, $supplier['percent']) }}%"></div>
                    </div>
                </li>
            @empty
                <li class="text-center text-muted py-3">
                    <i class="bi bi-clock-history d-block mb-2 fs-3"></i>
                    Nothing has been received yet, so there is no delivery history to score.
                </li>
            @endforelse
        </ul>
    </div>
</div>
