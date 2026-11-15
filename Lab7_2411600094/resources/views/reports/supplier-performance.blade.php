@extends('layouts.master')

@section('title', 'Supplier Performance')

@section('content')

    {{-- ===== Lab 7 Part 6 Step 2 =====
         Every figure below comes from SupplierPerformanceService's single
         grouped query for the window shown in the filter. The report adds no
         arithmetic of its own, so a number here is the same number the supplier
         profile and the dashboard widget print. --}}

    @php
        // The pink bands the service chose, as chart paint. Read from the same
        // rating_badge_class the table badges use, so a bar is never greener
        // than the badge next to it.
        $barColour = fn (?float $percent): string => match (true) {
            $percent === null => '#adb5bd',
            $percent >= 90    => '#28a745',
            $percent >= 75    => '#f3a712',
            default           => '#dc3545',
        };

        $measured = $rows->filter(fn (array $row): bool => $row['on_time_percentage'] !== null)->values();
    @endphp

    {{-- Page-local: only this table is sortable, so this stays here rather than
         in the global stylesheet every other page loads. --}}
    <style>
        #performanceTable th.sortable { cursor: pointer; user-select: none; white-space: nowrap; }
        #performanceTable th.sortable:hover { color: var(--bs-primary); }
        #performanceTable th.sortable[aria-sort="ascending"]::after  { content: " \2191"; color: var(--bs-primary); }
        #performanceTable th.sortable[aria-sort="descending"]::after { content: " \2193"; color: var(--bs-primary); }
    </style>

    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-0">Supplier Performance</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Inventory Reports
            </a>
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-building me-1"></i> Suppliers
            </a>
        </div>
    </div>

    {{-- ---------- Filter panel: the same layout as the Lab 5/6 reports ---------- --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <h5 class="mb-0">Reporting Window</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.supplier-performance') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="from">Orders raised from</label>
                    <input type="date" id="from" name="from" class="form-control" value="{{ $from }}" max="{{ $to }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="to">Orders raised to</label>
                    <input type="date" id="to" name="to" class="form-control" value="{{ $to }}" min="{{ $from }}">
                </div>
                <div class="col-md-6 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Apply</button>
                    <a href="{{ route('reports.supplier-performance') }}" class="btn btn-outline-secondary">Reset to {{ $summary['months'] }} months</a>
                    <span class="align-self-center small text-muted ms-auto">
                        <i class="bi bi-hdd me-1"></i>cached until a purchase order moves
                    </span>
                </div>
            </form>
            <div class="small text-muted mt-3 mb-0">
                The window slices by the date the order was <strong>raised</strong> — the same column the purchase
                order list filters on — so a vendor cannot look better here than they do there.
                Orders counted: <strong>{{ $totals['orders'] }}</strong> placed,
                <strong>{{ $totals['received'] }}</strong> received,
                across <strong>{{ $totals['suppliers'] }}</strong> supplier(s),
                <strong>PHP {{ number_format($totals['spend'], 2) }}</strong> committed.
            </div>
        </div>
    </div>

    {{-- ---------- Top / bottom ---------- --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-trophy me-1"></i>Top {{ min(3, count($summary['top'])) }}</h5>
                </div>
                <div class="card-body">
                    @forelse ($summary['top'] as $rank => $row)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2 {{ $rank === 2 ? 'border-bottom-0' : '' }}">
                            <span>
                                <span class="badge bg-primary me-2">{{ $rank + 1 }}</span>
                                <a href="{{ route('suppliers.show', ['supplier' => $row['supplier_id']]) }}"
                                   class="text-decoration-none fw-semibold">{{ $row['name'] }}</a>
                                <div class="small text-muted">
                                    {{ $row['on_time_orders'] }} of {{ $row['received_orders'] }} deliveries on time
                                </div>
                            </span>
                            <span class="badge {{ $row['rating_badge_class'] }} fs-6">{{ number_format($row['on_time_percentage'], 1) }} %</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">
                            No supplier has {{ $summary['min'] }} received deliveries in this window yet, so nothing
                            can be ranked.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-diamond me-1"></i>Bottom {{ count($summary['bottom']) }}</h5>
                </div>
                <div class="card-body">
                    @forelse ($summary['bottom'] as $rank => $row)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2 {{ $rank === count($summary['bottom']) - 1 ? 'border-bottom-0' : '' }}">
                            <span>
                                <span class="badge bg-danger me-2">{{ $rank + 1 }}</span>
                                <a href="{{ route('suppliers.show', ['supplier' => $row['supplier_id']]) }}"
                                   class="text-decoration-none fw-semibold">{{ $row['name'] }}</a>
                                <div class="small text-muted">
                                    {{ $row['on_time_orders'] }} of {{ $row['received_orders'] }} deliveries on time
                                    @if ($row['lead_time_variance_days'] !== null)
                                        &middot; {{ \App\Services\SupplierPerformanceService::varianceLabel($row['lead_time_variance_days']) }}
                                    @endif
                                </div>
                            </span>
                            <span class="badge {{ $row['rating_badge_class'] }} fs-6">{{ number_format($row['on_time_percentage'], 1) }} %</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">
                            @if ($summary['rankable'] <= 3)
                                Only {{ $summary['rankable'] }} supplier(s) qualify for a ranking in this window, so
                                a "bottom" list would just repeat the top one in reverse.
                            @else
                                Nothing to show.
                            @endif
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if ($summary['ignored'] !== [])
        <div class="alert alert-warning shadow-hover d-flex gap-2">
            <i class="bi bi-info-circle-fill"></i>
            <div>
                <strong>{{ count($summary['ignored']) }} supplier(s) left out of the ranking</strong>
                — fewer than {{ $summary['min'] }} received deliveries is one data point, not a track record:
                @foreach ($summary['ignored'] as $row)
                    <span class="d-inline-block me-2">
                        {{ $row['name'] }}
                        <span class="text-muted">({{ $row['received_orders'] }} received)</span>
                    </span>
                @endforeach
                They are still in the table below, with their figures.
            </div>
        </div>
    @endif

    {{-- ---------- Summary table ---------- --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Scorecard</h5>
            <span class="badge bg-light text-dark">click a column to sort</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 data-table" id="performanceTable">
                    <thead>
                        <tr>
                            <th scope="col" class="sortable" data-sort="text" aria-sort="none">Supplier</th>
                            <th scope="col" class="sortable text-end" data-sort="number" aria-sort="none">Orders</th>
                            <th scope="col" class="sortable text-end" data-sort="number" aria-sort="none">Received</th>
                            <th scope="col" class="sortable" data-sort="number" aria-sort="none">On-time %</th>
                            <th scope="col" class="sortable text-end" data-sort="number" aria-sort="none">Avg days</th>
                            <th scope="col" class="sortable" data-sort="number" aria-sort="none">vs quoted lead time</th>
                            <th scope="col" class="sortable text-end" data-sort="number" aria-sort="none">Accuracy %</th>
                            <th scope="col" class="sortable text-end" data-sort="number" aria-sort="none">Value (PHP)</th>
                            <th scope="col">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('suppliers.show', ['supplier' => $row['supplier_id']]) }}"
                                       class="text-decoration-none fw-semibold"
                                       data-sort-value="{{ strtolower($row['name']) }}">{{ $row['name'] }}</a>
                                    @unless ($row['is_active'])
                                        <span class="badge bg-secondary ms-1">Retired</span>
                                    @endunless
                                </td>
                                <td class="text-end" data-sort-value="{{ $row['total_orders'] }}">{{ $row['total_orders'] }}</td>
                                <td class="text-end" data-sort-value="{{ $row['received_orders'] }}">{{ $row['received_orders'] }}</td>
                                <td data-sort-value="{{ $row['on_time_percentage'] ?? -1 }}">
                                    @if ($row['on_time_percentage'] === null)
                                        <span class="text-muted">—</span>
                                    @else
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" role="progressbar"
                                                 aria-label="On-time delivery"
                                                 aria-valuenow="{{ $row['on_time_percentage'] }}"
                                                 aria-valuemin="0" aria-valuemax="100" style="height: 8px; min-width: 70px;">
                                                <div class="progress-bar {{ $row['rating_badge_class'] }}"
                                                     style="width: {{ min(100, $row['on_time_percentage']) }}%"></div>
                                            </div>
                                            <span class="small text-nowrap">{{ number_format($row['on_time_percentage'], 1) }} %</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end" data-sort-value="{{ $row['average_delivery_days'] ?? -1 }}">
                                    {{ $row['average_delivery_days'] === null ? '—' : number_format($row['average_delivery_days'], 1) }}
                                </td>
                                <td data-sort-value="{{ $row['lead_time_variance_days'] ?? -999 }}">
                                    @if ($row['lead_time_variance_days'] === null)
                                        <span class="text-muted">—</span>
                                    @else
                                        <span class="{{ $row['lead_time_variance_days'] > 0 ? 'text-danger' : ($row['lead_time_variance_days'] < 0 ? 'text-success' : 'text-muted') }}">
                                            {{ \App\Services\SupplierPerformanceService::varianceLabel($row['lead_time_variance_days']) }}
                                        </span>
                                        <div class="small text-muted">quoted {{ $row['lead_time_days'] ?? '—' }} days</div>
                                    @endif
                                </td>
                                <td class="text-end" data-sort-value="{{ $row['order_accuracy_percentage'] ?? -1 }}">
                                    {{ $row['order_accuracy_percentage'] === null
                                        ? '—'
                                        : number_format($row['order_accuracy_percentage'], 1).' %' }}
                                    <div class="small text-muted">{{ $row['lines_exact'] }}/{{ $row['lines_received'] }} lines</div>
                                </td>
                                <td class="text-end" data-sort-value="{{ $row['total_order_value'] }}">
                                    {{ number_format($row['total_order_value'], 2) }}
                                </td>
                                <td><span class="badge {{ $row['rating_badge_class'] }}">
                                    {{ $row['is_rankable'] ? 'Measured' : 'Low sample' }}
                                </span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block"></i>
                                    No purchase order was placed inside this window.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mt-3 mb-0">
                <strong>Orders</strong> counts what the vendor was actually given (sent, partially received,
                received) &middot; <strong>On-time %</strong> is deliveries landed on or before the promised
                <em>day</em>, over closed receipts &middot; <strong>Avg days</strong> is sent to received &middot;
                <strong>Accuracy %</strong> is lines received exactly as ordered &middot;
                <strong>Value</strong> is the header totals of those orders, VAT included.
                A partially received order is in the money and the count, never in the scores: it still owes
                goods, so it cannot have failed a delivery.
            </div>
        </div>
    </div>

    {{-- ---------- Charts ---------- --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-steps me-1"></i>On-time vs late deliveries by month</h5>
                </div>
                <div class="card-body">
                    @if ($totals['received'] === 0)
                        <p class="text-muted small mb-0">Nothing has been received in this window.</p>
                    @else
                        {{-- Chart.js with maintainAspectRatio off sizes to its
                             box, so the box has to own the height. --}}
                        <div style="position: relative; height: 320px;">
                            <canvas id="trendChart" role="img"
                                    aria-label="Stacked bar chart of on-time and late deliveries per month"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-1"></i>On-time % per supplier</h5>
                </div>
                <div class="card-body">
                    @if ($measured->isEmpty())
                        <p class="text-muted small mb-0">No supplier has a completed delivery in this window.</p>
                    @else
                        <div style="position: relative; height: {{ max(220, $measured->count() * 44) }}px;">
                            <canvas id="supplierChart" role="img"
                                    aria-label="Bar chart of on-time delivery percentage per supplier"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@php
    // Built here rather than inside @json: that directive reads its argument up
    // to the first closing parenthesis, so a nested arrow function truncates it.
    $chartPayload = [
        'trend' => $trend,
        'suppliers' => $measured->map(fn (array $row): array => [
            'name'  => $row['name'],
            'value' => $row['on_time_percentage'],
            'color' => $barColour($row['on_time_percentage']),
        ])->values(),
    ];
@endphp
@push('scripts')
    {{-- The layout loads Bootstrap from jsdelivr and nothing else, so Chart.js is
         pulled the same way here rather than on every page in the app: this is
         the only screen with a chart on it. --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            // Data in, drawn out — the numbers are computed in SQL, never here.
            const data = @json($chartPayload);

            const pink = '#FB6F92';
            Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
            Chart.defaults.color = '#5a3d4a';

            const trend = document.getElementById('trendChart');
            if (trend) {
                new Chart(trend, {
                    type: 'bar',
                    data: {
                        labels: data.trend.map(function (month) { return month.label; }),
                        datasets: [
                            {
                                label: 'On time',
                                data: data.trend.map(function (month) { return month.on_time; }),
                                backgroundColor: '#28a745',
                                stack: 'deliveries',
                            },
                            {
                                label: 'Late',
                                data: data.trend.map(function (month) { return month.late; }),
                                backgroundColor: '#dc3545',
                                stack: 'deliveries',
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { stacked: true, grid: { display: false } },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: { precision: 0 },
                                title: { display: true, text: 'Deliveries' },
                            },
                        },
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    afterBody: function (items) {
                                        const month = data.trend[items[0].dataIndex];
                                        const total = month.on_time + month.late;
                                        // The monthly rate, so a month with one
                                        // late drop out of one is not read as a
                                        // collapse of the whole quarter.
                                        return total ? total + ' scored · ' +
                                            Math.round(month.on_time / total * 100) + '% on time'
                                            : 'nothing scored';
                                    },
                                },
                            },
                        },
                    },
                });
            }

            const supplier = document.getElementById('supplierChart');
            if (supplier) {
                new Chart(supplier, {
                    type: 'bar',
                    data: {
                        labels: data.suppliers.map(function (row) { return row.name; }),
                        datasets: [{
                            label: 'On-time %',
                            data: data.suppliers.map(function (row) { return row.value; }),
                            // The same band colours the badges use, per bar.
                            backgroundColor: data.suppliers.map(function (row) { return row.color; }),
                            borderColor: pink,
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                beginAtZero: true,
                                max: 100,
                                ticks: { callback: function (value) { return value + ' %'; } },
                            },
                            y: { grid: { display: false }, ticks: { autoSkip: false } },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: { label: function (item) { return item.parsed.x + ' % on time'; } },
                            },
                        },
                    },
                });
            }

            /* ---------- Sortable columns ----------
               Sorting is a view concern, so it sorts what the server printed
               instead of asking for another round trip: every cell carries the
               value it sorts on in data-sort-value (the printed one is for
               humans — "—" and "+4 days late" cannot be compared). */
            const table = document.getElementById('performanceTable');
            if (!table) return;

            const headings = table.querySelectorAll('th.sortable');

            headings.forEach(function (heading) {
                heading.addEventListener('click', function () {
                    const column = Array.prototype.indexOf.call(heading.parentNode.children, heading);
                    const ascending = heading.getAttribute('aria-sort') !== 'ascending';

                    const body = table.querySelector('tbody');
                    const rows = Array.prototype.slice.call(body.querySelectorAll('tr'));

                    rows.sort(function (left, right) {
                        const a = valueOf(left.children[column]);
                        const b = valueOf(right.children[column]);

                        if (typeof a === 'number' && typeof b === 'number') {
                            return ascending ? a - b : b - a;
                        }

                        return ascending ? String(a).localeCompare(String(b))
                                         : String(b).localeCompare(String(a));
                    });

                    rows.forEach(function (row) { body.appendChild(row); });

                    headings.forEach(function (other) { other.setAttribute('aria-sort', 'none'); });
                    heading.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');
                });
            });

            function valueOf(cell) {
                if (!cell) return '';

                const raw = cell.getAttribute('data-sort-value');
                const text = raw !== null ? raw : cell.textContent.trim();

                // A missing measure sorts below every real number, whatever the
                // direction: -1 / -999 in the markup says "nothing measured".
                return text === '' || isNaN(Number(text)) ? text : Number(text);
            }
        })();
    </script>
@endpush
