@extends('layouts.master')

@section('title', 'Inventory Reports')

@section('content')

    @php
        $exportUrl = fn (string $type) => route('reports.export', array_filter([
            'report'      => $type,
            'date_from'   => $range[0],
            'date_to'     => $range[1],
            'product_id'  => $type === 'movement' ? $filters['product_id'] : null,
            'type'        => $type === 'movement' ? $filters['type'] : null,
            'category_id' => $type === 'low-stock' ? $filters['category_id'] : null,
            'search'      => $type === 'low-stock' ? $filters['search'] : null,
        ]));
        $tabs = [
            'valuation' => 'Inventory Valuation',
            'movement'  => 'Stock Movement',
            'low-stock' => 'Low Stock',
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-0">Inventory Reports</h2>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Inventory
        </a>
    </div>

    {{-- ===== Headline figures ===== --}}
    <div class="row mb-4">
        @foreach([
            ['Total Products', number_format($totals['products']), 'border-primary-custom'],
            ['Units on Hand', number_format($totals['units']), 'border-accent'],
            ['Inventory Value (PHP)', number_format($totals['value'], 2), 'border-success-custom'],
            ['Low Stock Items', number_format($totals['low_stock']), 'border-warning-custom'],
            ['Out of Stock Items', number_format($totals['out_of_stock']), 'border-danger-custom'],
            ['Units Sold', number_format($totals['stock_out_units']), 'border-accent'],
        ] as [$label, $value, $border])
            <div class="col-md mb-3">
                <div class="card stat-card text-center shadow-hover {{ $border }}">
                    <div class="card-body">
                        <h6 class="card-title text-muted">{{ strtoupper($label) }}</h6>
                        <h3 class="card-text fw-bold text-primary">{{ $value }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Part 9 Step 2: one page, three reports ===== --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-0">
        <ul class="nav nav-tabs">
            @foreach($tabs as $key => $label)
                <li class="nav-item">
                    <a class="nav-link {{ $report === $key ? 'active fw-semibold' : 'text-muted' }}"
                       data-bs-toggle="tab" href="#report-{{ $key }}">{{ $label }}</a>
                </li>
            @endforeach
        </ul>
        <a href="{{ $exportUrl($report) }}" id="reportExportBtn" class="btn btn-success btn-sm">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
    </div>

    <div class="tab-content bg-white border border-top-0 rounded-bottom p-3">

        {{-- ---------- Stock Movement ---------- --}}
        <div class="tab-pane fade {{ $report === 'movement' ? 'show active' : '' }}" id="report-movement">

            {{-- Filter panel: same layout as the Lab 5 roster filter --}}
            <div class="card shadow-hover mb-4">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">Filter Stock Movements</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end">
                        <input type="hidden" name="report" value="movement">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Product</label>
                            <select name="product_id" class="form-select">
                                <option value="">All Products</option>
                                @foreach($filterProducts as $p)
                                    <option value="{{ $p->id }}" @selected($filters["product_id"] == $p->id)>{{ $p->name }} ({{ $p->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Type</label>
                            <select name="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="in" @selected($filters['type'] === 'in')>Stock In</option>
                                <option value="out" @selected($filters['type'] === 'out')>Stock Out</option>
                                <option value="adjustment" @selected($filters['type'] === 'adjustment')>Adjustment</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $range[0] }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $range[1] }}">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search"></i></button>
                            <a href="{{ route('reports.index', ['report' => 'movement']) }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-hover">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Stock Movement Report</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-light text-dark">
                            {{ number_format($movement->count()) }} movement{{ $movement->count() !== 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-4 small text-muted mb-3">
                        <span>Window: <strong>{{ $range[0] }}</strong> to <strong>{{ $range[1] }}</strong></span>
                        <span>Units in: <strong class="text-success">+{{ number_format($movement->sum(fn ($t) => max(0, $t->signedQuantity()))) }}</strong></span>
                        <span>Units out: <strong class="text-danger">{{ number_format($movement->sum(fn ($t) => min(0, $t->signedQuantity()))) }}</strong></span>
                        <span>Adjustments: <strong>{{ number_format($movement->where('type', 'adjustment')->sum(fn ($t) => $t->signedQuantity())) }}</strong></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0 data-table">
                            <thead>
                                <tr>
                                    <th>Date / Time</th><th>Product</th><th>Type</th>
                                    <th class="text-end">Qty change</th>
                                    <th class="text-end">Balance after</th>
                                    <th>Reference</th><th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movement as $transaction)
                                    <tr>
                                        <td class="text-nowrap">{{ $transaction->created_at->format('M j, Y g:i A') }}</td>
                                        <td>
                                            <a href="{{ route('products.show', $transaction->product) }}" class="text-decoration-none">
                                                {{ $transaction->product->name }}
                                            </a>
                                            <div class="small text-muted"><code>{{ $transaction->product->sku }}</code></div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $transaction->typeBadgeClass() }}">{{ $transaction->typeLabel() }}</span>
                                            @if ($transaction->isAdjustment() && $transaction->reasonLabel())
                                                <div class="small text-muted">{{ $transaction->reasonLabel() }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold {{ $transaction->signedQuantity() >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->signedQuantity() > 0 ? '+' : '' }}{{ number_format($transaction->signedQuantity()) }}
                                        </td>
                                        <td class="text-end fw-semibold">{{ number_format($transaction->balance_after) }}</td>
                                        <td><code>{{ $transaction->reference_number ?: 'n/a' }}</code></td>
                                        <td>{{ $transaction->user?->name ?? 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">No movements inside this date range.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------- Low Stock ---------- --}}
        <div class="tab-pane fade {{ $report === 'low-stock' ? 'show active' : '' }}" id="report-low-stock">

            <div class="card shadow-hover mb-4">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">Filter Low Stock Items</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end">
                        <input type="hidden" name="report" value="low-stock">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Search</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Name, SKU or supplier..." value="{{ $filters['search'] }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($filterCategories as $c)
                                    <option value="{{ $c->id }}" @selected($filters["category_id"] == $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search"></i></button>
                            <a href="{{ route('reports.index', ['report' => 'low-stock']) }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-hover">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Low Stock Report <span class="small fw-normal opacity-75">(at or below the low stock level)</span></h5>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-light text-dark">{{ $lowStockReport->count() }} item(s)</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0 data-table">
                            <thead>
                                <tr>
                                    <th>SKU</th><th>Item</th><th>Category</th><th>Supplier</th>
                                    <th class="text-end">On Hand</th><th class="text-end">Low Stock Level</th>
                                    <th class="text-end">Deficit</th>
                                    <th class="text-end">Suggested Qty</th>
                                    <th class="text-end">Est. Cost (PHP)</th><th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockReport as $product)
                                    <tr>
                                        <td><code>{{ $product->sku }}</code></td>
                                        <td><a href="{{ route('products.show', $product) }}" class="text-decoration-none">{{ $product->name }}</a></td>
                                        <td class="small">{{ $product->category->name }}</td>
                                        <td class="small">{{ $product->supplier ?: 'not recorded' }}</td>
                                        <td class="text-end">{{ $product->quantity }}</td>
                                        <td class="text-end">{{ $product->reorder_level }}</td>
                                        <td class="text-end">{{ number_format($product->reorder_level - $product->quantity) }}</td>
                                        <td class="text-end fw-bold">{{ $product->unitQuantityMissing() }}</td>
                                        <td class="text-end">{{ number_format($product->unitQuantityMissing() * (float) $product->unit_price, 2) }}</td>
                                        <td><span class="badge {{ $product->statusBadgeClass() }}">{{ $product->stockStatus() }}</span></td>
                                        <td class="text-end">
                                            @if (Auth::user()->is_admin)
                                                <a href="{{ route('transactions.index', ['adjust' => $product->id]) }}"
                                                   class="btn btn-sm btn-outline-secondary text-nowrap">Adjust</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="11" class="text-center text-success py-4">
                                        <i class="bi bi-check-circle me-1"></i>No items are running low on stock.
                                    </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------- Inventory Valuation (Lab 5) ---------- --}}
        <div class="tab-pane fade {{ $report === 'valuation' ? 'show active' : '' }}" id="report-valuation">
            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="card shadow-hover h-100">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">Valuation by Category</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle mb-0 data-table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th class="text-end">Products</th>
                                            <th class="text-end">Units</th>
                                            <th class="text-end">Low Stock</th>
                                            <th class="text-end">Value (PHP)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($byCategory as $row)
                                            <tr>
                                                <td>{{ $row['name'] }}</td>
                                                <td class="text-end">{{ $row['items'] }}</td>
                                                <td class="text-end">{{ number_format($row['units']) }}</td>
                                                <td class="text-end {{ $row['low_stock'] ? 'text-danger fw-bold' : '' }}">{{ $row['low_stock'] }}</td>
                                                <td class="text-end">{{ number_format($row['value'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="fw-bold table-light">
                                            <td>TOTAL</td>
                                            <td class="text-end">{{ $totals['products'] }}</td>
                                            <td class="text-end">{{ number_format($totals['units']) }}</td>
                                            <td class="text-end">{{ $totals['low_stock'] + $totals['out_of_stock'] }}</td>
                                            <td class="text-end">{{ number_format($totals['value'], 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="card shadow-hover h-100">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-1"></i>Stock Movement Totals</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex justify-content-between border-bottom py-2">
                                    <span>Units received (all time)</span>
                                    <span class="text-success fw-bold">+{{ number_format($totals['stock_in_units']) }}</span>
                                </li>
                                <li class="d-flex justify-content-between border-bottom py-2">
                                    <span>Units sold (all time)</span>
                                    <span class="text-danger fw-bold">-{{ number_format($totals['stock_out_units']) }}</span>
                                </li>
                                <li class="d-flex justify-content-between border-bottom py-2">
                                    <span>Net units on hand</span>
                                    <span class="fw-bold">{{ number_format($totals['units']) }}</span>
                                </li>
                                <li class="d-flex justify-content-between py-2">
                                    <span>Items at or below the low stock level</span>
                                    <span class="fw-bold text-warning">{{ number_format($lowStockReport->count()) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card shadow-hover h-100">
                        <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Valuation by Brand</h5></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead><tr><th>Brand</th><th class="text-end">Products</th><th class="text-end">Value (PHP)</th></tr></thead>
                                    <tbody>
                                        @forelse($byBrand as $row)
                                            <tr>
                                                <td>{{ $row['name'] }}</td>
                                                <td class="text-end">{{ $row['items'] }}</td>
                                                <td class="text-end">{{ number_format($row['value'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-muted text-center py-3">No brands recorded.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card shadow-hover h-100">
                        <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Spend Exposure by Supplier</h5></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead><tr><th>Supplier</th><th class="text-end">Items</th><th class="text-end">Stock Value (PHP)</th></tr></thead>
                                    <tbody>
                                        @forelse($suppliers as $supplier)
                                            <tr>
                                                <td>{{ $supplier->supplier }}</td>
                                                <td class="text-end">{{ $supplier->items }}</td>
                                                <td class="text-end">{{ number_format((float) $supplier->total_value, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-muted text-center py-3">No suppliers recorded.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const exportUrls = {{ json_encode([
                'valuation' => $exportUrl('valuation'),
                'movement'  => $exportUrl('movement'),
                'low-stock' => $exportUrl('low-stock'),
            ]) }};
            const btn = document.getElementById('reportExportBtn');
            document.querySelectorAll('.nav-tabs .nav-link').forEach(function (tab) {
                tab.addEventListener('shown.bs.tab', function (event) {
                    const key = event.target.getAttribute('href').replace('#report-', '');
                    if (exportUrls[key]) btn.setAttribute('href', exportUrls[key]);
                });
            });
        })();
    </script>
@endpush
