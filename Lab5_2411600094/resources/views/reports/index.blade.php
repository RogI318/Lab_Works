@extends('layouts.master')

@section('title', 'Inventory Reports')

@section('content')

    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-0">Inventory Reports</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.export') }}" class="btn btn-success">
                <i class="bi bi-download me-1"></i>Download CSV
            </a>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Inventory
            </a>
        </div>
    </div>

    {{-- ===== Headline figures ===== --}}
    <div class="row mb-4">
        @foreach([
            ['Total Products', number_format($totals['products']), 'border-primary-custom'],
            ['Units on Hand', number_format($totals['units']), 'border-accent'],
            ['Inventory Value (PHP)', number_format($totals['value'], 2), 'border-success-custom'],
            ['Low Stock Items', number_format($totals['low_stock']), 'border-warning-custom'],
            ['Out of Stock Items', number_format($totals['out_of_stock']), 'border-danger-custom'],
            ['Units Issued', number_format($totals['stock_out_units']), 'border-accent'],
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

    <div class="row">
        {{-- ===== Valuation by category ===== --}}
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

        {{-- ===== Movement totals ===== --}}
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
                            <span>Units issued (all time)</span>
                            <span class="text-danger fw-bold">-{{ number_format($totals['stock_out_units']) }}</span>
                        </li>
                        <li class="d-flex justify-content-between border-bottom py-2">
                            <span>Net units on hand</span>
                            <span class="fw-bold">{{ number_format($totals['units']) }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-2">
                            <span>Items at or below reorder level</span>
                            <span class="fw-bold text-warning">{{ number_format($totals['low_stock']) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- ===== Brands ===== --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-light"><h5 class="mb-0">Valuation by Brand</h5></div>
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

        {{-- ===== Suppliers ===== --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-light"><h5 class="mb-0">Spend Exposure by Supplier</h5></div>
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

        {{-- ===== Reorder list ===== --}}
        <div class="col-12 mb-4">
            <div class="card shadow-hover">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Items to Reorder</h5>
                    <span class="badge bg-light text-dark">{{ $lowStock->count() + $outOfStock->count() }} flagged</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0 data-table">
                            <thead>
                                <tr>
                                    <th>SKU</th><th>Item</th><th>Category</th><th>Supplier</th>
                                    <th class="text-end">On Hand</th><th class="text-end">Reorder Level</th>
                                    <th class="text-end">Suggested Qty</th><th class="text-end">Est. Cost (PHP)</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($outOfStock->concat($lowStock) as $product)
                                    <tr>
                                        <td><code>{{ $product->sku }}</code></td>
                                        <td><a href="{{ route('products.show', $product) }}" class="text-decoration-none">{{ $product->name }}</a></td>
                                        <td class="small">{{ $product->category->name }}</td>
                                        <td class="small">{{ $product->supplier ?: 'not recorded' }}</td>
                                        <td class="text-end">{{ $product->quantity }}</td>
                                        <td class="text-end">{{ $product->reorder_level }}</td>
                                        <td class="text-end fw-bold">{{ $product->unitQuantityMissing() }}</td>
                                        <td class="text-end">{{ number_format($product->unitQuantityMissing() * (float) $product->unit_price, 2) }}</td>
                                        <td><span class="badge {{ $product->statusBadgeClass() }}">{{ $product->stockStatus() }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-success py-4">
                                        <i class="bi bi-check-circle me-1"></i>Every item is above its reorder level.
                                    </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
