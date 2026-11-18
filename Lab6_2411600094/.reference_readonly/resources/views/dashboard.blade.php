@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="container-fluid">

    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">

        <h2>
            {{ $greeting }}, {{ auth()->user()->name }}!
        </h2>
        <div class="text-muted small mb-2">Last updated: <span id="last-updated">just now</span> <span id="poll-status" class="text-danger small"></span></div>

    </div>


    <!-- Stock Warning -->
    @if ($stats['low_stock_count'] > 0 || $stats['out_of_stock_count'] > 0)

        <div
            id="stockAlert"
            class="alert alert-warning alert-dismissible fade show"
            role="alert"
        >

            <strong>Stock Alert:</strong>

            {{ $stats['low_stock_count'] }} product(s) are low on stock and
            {{ $stats['out_of_stock_count'] }} product(s) are out of stock.

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    @endif


    <!-- Statistics Cards -->
    <div class="row mb-4">

        <!-- Total Products -->
        <div class="col-md-3 mb-3">

            <div class="card stat-card shadow-hover text-center">

                <div class="card-body">

                    <h5 class="card-title text-muted">
                        Total Products
                    </h5>

                    <h2 class="card-text fw-bold text-primary-custom">
                        <span data-live-stat="total_products">{{ $stats['total_products'] }}</span>
                    </h2>

                </div>

            </div>

        </div>


        <!-- Low Stock -->
        <div class="col-md-3 mb-3">

            <div class="card stat-card shadow-hover text-center">

                <div class="card-body">

                    <h5 class="card-title text-muted">
                        Low Stock
                    </h5>

                    <h2 class="card-text fw-bold text-warning">
                        <span data-live-stat="low_stock">{{ $stats['low_stock_count'] }}</span>
                    </h2>

                </div>

            </div>

        </div>


        <!-- Out of Stock -->
        <div class="col-md-3 mb-3">

            <div class="card stat-card shadow-hover text-center">

                <div class="card-body">

                    <h5 class="card-title text-muted">
                        Out of Stock
                    </h5>

                    <h2 class="card-text fw-bold text-danger">
                        <span data-live-stat="out_of_stock">{{ $stats['out_of_stock_count'] }}</span>
                    </h2>

                </div>

            </div>

        </div>


        <!-- Inventory Value -->
        <div class="col-md-3 mb-3">

            <div class="card stat-card shadow-hover text-center">

                <div class="card-body">

                    <h5 class="card-title text-muted">
                        Inventory Value
                    </h5>

                    <h2 class="card-text fw-bold text-success">
                        ₱<span data-live-stat="inventory_value" data-decimals="2">{{ number_format($stats['inventory_value'], 2) }}</span>
                    </h2>

                </div>

            </div>

        </div>

    </div>


    <!-- Charts -->
    <div class="row mb-4">

        <div class="col-lg-6 col-md-6 mb-3">
            <div class="card shadow-hover h-100">
                <div class="card-header">
                    <h6 class="mb-0">Quantity by Category</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="categoryChart" aria-label="Quantity by category chart" role="img"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 mb-3">
            <div class="card shadow-hover h-100">
                <div class="card-header">
                    <h6 class="mb-0">Stock Status Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart" aria-label="Stock status distribution chart" role="img"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 mb-3">
            <div class="card shadow-hover h-100">
                <div class="card-header">
                    <h6 class="mb-0">Top Products by Stock Value</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="topProductsChart" aria-label="Top products by stock value chart" role="img"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 mb-3">
            <div class="card shadow-hover h-100">
                <div class="card-header">
                    <h6 class="mb-0">Inventory Value by Category</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="valueChart" aria-label="Inventory value by category chart" role="img"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <!-- Recent Products -->
    <div class="card mb-4">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

            <h5 class="mb-0">
                Recent Products
            </h5>

            <a
                href="{{ route('products.create') }}"
                class="btn btn-primary btn-sm"
            >
                + Add Product
            </a>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead>

                        <tr class="border-accent">

                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($recentProducts as $product)

                            <tr>

                                <td>
                                    {{ $product->sku }}
                                </td>

                                <td>
                                    <a href="{{ route('products.show', $product) }}">
                                        {{ $product->name }}
                                    </a>
                                </td>

                                <td>
                                    {{ $product->category }}
                                </td>

                                <td>
                                    {{ $product->quantity }}
                                </td>

                                <td>
                                    ₱{{ number_format($product->unit_price, 2) }}
                                </td>

                                <td>

                                    @php

                                        $badgeClass = match($product->status()) {

                                            'In Stock' => 'bg-success',

                                            'Low Stock' => 'bg-warning text-dark',

                                            default => 'bg-danger',

                                        };

                                    @endphp


                                    <span class="badge status-badge {{ $badgeClass }}">

                                        {{ $product->status() }}

                                    </span>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="6"
                                    class="text-center text-muted"
                                >

                                    No products available.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- Stock by Brand and Category -->
    <div class="row">

        <div class="col-md-6 mb-4">

            <div class="card h-100">

                <div class="card-header">
                    <h5 class="mb-0">Stock by Brand</h5>
                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-striped table-hover align-middle mb-0">

                            <thead>
                                <tr class="border-accent">
                                    <th>Brand</th>
                                    <th>Products</th>
                                    <th>Qty</th>
                                    <th>Stock Value</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($brandSummary as $row)
                                    <tr>
                                        <td>{{ $row->brand_name }}</td>
                                        <td>{{ $row->product_count }}</td>
                                        <td>{{ $row->total_quantity }}</td>
                                        <td>₱{{ number_format($row->stock_value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No brands yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-6 mb-4">

            <div class="card h-100">

                <div class="card-header">
                    <h5 class="mb-0">Stock by Category</h5>
                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-striped table-hover align-middle mb-0">

                            <thead>
                                <tr class="border-accent">
                                    <th>Category</th>
                                    <th>Products</th>
                                    <th>Qty</th>
                                    <th>Stock Value</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($categorySummary as $row)
                                    <tr>
                                        <td>{{ $row->category }}</td>
                                        <td>{{ $row->product_count }}</td>
                                        <td>{{ $row->total_quantity }}</td>
                                        <td>₱{{ number_format($row->stock_value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No categories yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Low-Stock Alerts -->
    <div class="card mb-4">

        <div class="card-header">

            <h5 class="mb-0">
                Low-Stock Alerts
            </h5>

        </div>


        <div class="card-body">

            @forelse ($lowStockAlerts as $product)

                <div class="d-flex justify-content-between align-items-center border-bottom py-2">

                    <span>
                        <a href="{{ route('products.show', $product) }}">
                            {{ $product->name }}
                        </a>
                        <small class="text-muted">
                            ({{ $product->sku }})
                        </small>
                    </span>

                    <span>

                        <strong>
                            {{ $product->quantity }}
                        </strong>

                        <small class="text-muted">
                            / reorder at {{ $product->reorder_level }}
                        </small>

                        <span class="badge status-badge ms-2 {{ $product->isOutOfStock() ? 'bg-danger' : 'bg-warning text-dark' }}">
                            {{ $product->status() }}
                        </span>

                    </span>

                </div>

            @empty

                <p class="text-muted mb-0">
                    All products are well stocked.
                </p>

            @endforelse

        </div>

    </div>


    {{-- LAB 6: Recent transactions (refreshed every 30 seconds by public/js/dashboard-poll.js) --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Transactions</h5>
            <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody id="recent-tx-body">
                        @forelse ($recentTransactions as $t)
                            <tr>
                                <td>{{ $t->created_at->format('M d, h:i A') }}</td>
                                <td>{{ $t->product?->name }}</td>
                                <td><span class="badge text-bg-{{ $t->type_color }}">{{ $t->type_label }}</span></td>
                                <td class="fw-semibold {{ $t->signed_quantity > 0 ? 'text-success' : 'text-danger' }}">{{ $t->signed_quantity > 0 ? '+' : '' }}{{ $t->signed_quantity }}</td>
                                <td>{{ $t->user?->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
    <!-- Chart.js (same version as Lab 4) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/charts.js') }}"></script>
    <script>
        dashboardCharts.renderAll(
            @json($categorySummary),
            @json($statusStats),
            @json($topProducts)
        );
    </script>
    <script src="{{ asset('js/dashboard-poll.js') }}"></script>
@endpush
