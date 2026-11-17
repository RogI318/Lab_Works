@extends('layouts.master')

@section('title', 'Dashboard')

@section('content')

    <div class="pb-2 mb-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 id="greeting" class="fw-bold mb-0">Inventory Dashboard</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Product
            </a>
        </div>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-center shadow-hover border-primary-custom">
                <div class="card-body">
                    <h5 class="card-title text-muted">TOTAL PRODUCTS</h5>
                    <h2 class="card-text fw-bold text-primary">{{ number_format($totalProducts) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-center shadow-hover border-warning-custom">
                <div class="card-body">
                    <h5 class="card-title text-muted">LOW STOCK</h5>
                    <h2 class="card-text fw-bold text-warning">{{ number_format($lowStockCount) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-center shadow-hover border-danger-custom">
                <div class="card-body">
                    <h5 class="card-title text-muted">OUT OF STOCK</h5>
                    <h2 class="card-text fw-bold text-danger">{{ number_format($outOfStockCount) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-center shadow-hover border-success-custom">
                <div class="card-body">
                    <h5 class="card-title text-muted">INVENTORY VALUE</h5>
                    <h2 class="card-text fw-bold text-success">{{ number_format($inventoryValue, 2) }}</h2>
                </div>
            </div>
        </div>
    </div>


    {{-- ===== CHARTS (Part 7, Step 1: charts / visualizations) ===== --}}
    <div class="row mb-2">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-1"></i>Stock Status</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart me-1"></i>Products per Category</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px;">
                        <canvas id="categoryCountChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-cash-stack me-1"></i>Inventory Value by Category</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px;">
                        <canvas id="categoryValueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-graph-down me-1"></i>Lowest Stock vs Reorder Level</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px;">
                        <canvas id="stockLevelChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- ===== Alert center ===== --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-bell me-1"></i>Low Stock Alerts</h5>
                    <a href="{{ route('products.index', ['status' => 'Low Stock']) }}" class="btn btn-light btn-sm">View all</a>
                </div>
                <div class="card-body">
                    <div id="alertsList">
                        @forelse($lowStockItems as $product)
                            <div class="alert alert-warning d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><a href="{{ route('products.show', $product) }}" class="text-decoration-none">{{ $product->name }}</a></strong>
                                    <br><small>{{ $product->category->name }} - low stock level {{ $product->reorder_level }}</small>
                                </div>
                                <span class="badge bg-dark">{{ $product->quantity }} left</span>
                            </div>
                        @empty
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check-circle me-1"></i>No items are running low on stock.
                            </div>
                        @endforelse

                        @forelse($outOfStockItems as $product)
                            <div class="alert alert-danger d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><a href="{{ route('products.show', $product) }}" class="text-decoration-none">{{ $product->name }}</a></strong>
                                    <br><small>{{ $product->category->name }} - supplier: {{ $product->supplier ?: 'not recorded' }}</small>
                                </div>
                                <span class="badge bg-dark">0 left</span>
                            </div>
                        @empty
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Recent products ===== --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">Recent Products</h5>
                </div>
                <div class="card-body activity-scroll">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr><th>SKU</th><th>Product</th><th>Category</th><th class="text-end">Qty</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentProducts as $product)
                                <tr>
                                    <td><code>{{ $product->sku }}</code></td>
                                    <td><a href="{{ route('products.show', $product) }}" class="text-decoration-none">{{ $product->name }}</a></td>
                                    <td class="small">{{ $product->category->name }}</td>
                                    <td class="text-end">{{ $product->quantity }}</td>
                                    <td><span class="badge {{ $product->statusBadgeClass() }}">{{ $product->stockStatus() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No products yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Brands and Categories, with their product counts ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-award me-1"></i>Brands</h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark">{{ $brands->count() }} brands</span>
                <a href="{{ route('brands.index') }}" class="btn btn-light btn-sm">Manage</a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.item-table', ['items' => $brands, 'module' => 'brand'])
        </div>
    </div>

    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-tags me-1"></i>Categories</h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark">{{ $categories->count() }} categories</span>
                <a href="{{ route('categories.index') }}" class="btn btn-light btn-sm">Manage</a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.item-table', ['items' => $categories, 'module' => 'category'])
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        // Time-based greeting (same as Lab 4)
        (function () {
            const h = new Date().getHours();
            let tod = 'Good Evening';
            if (h >= 5 && h < 12)       tod = 'Good Morning';
            else if (h >= 12 && h < 17) tod = 'Good Afternoon';
            else if (h >= 17 && h < 21) tod = 'Good Evening';
            else                        tod = 'Good Night';
            document.getElementById('greeting').textContent = `${tod}, ` + @json(Auth::user()->name) + '!';
        })();

        // ---- Charts: data comes from DashboardController (real database values) ----
        const statusData        = @json($statusChart);
        const categoryCountData = @json($categoryCountChart);
        const categoryValueData = @json($categoryValueChart);
        const stockLevelData    = @json($stockLevelChart);

        // Lab 4 pink theme + status colours
        const themePrimary = '#FB6F92';
        const palette = ['#FB6F92', '#FF8FAB', '#FFB3C6', '#FFC2D1', '#e5527a', '#c9184a', '#ff758f', '#ffccd5'];
        const textColor = '#5a3d4a';

        Chart.defaults.color = textColor;
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";

        // 1) Stock status (doughnut)
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusData.labels,
                datasets: [{
                    data: statusData.data,
                    backgroundColor: ['#28a745', '#f3a712', '#dc3545'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // 2) Products per category (bar)
        new Chart(document.getElementById('categoryCountChart'), {
            type: 'bar',
            data: {
                labels: categoryCountData.labels,
                datasets: [{
                    label: 'Products',
                    data: categoryCountData.data,
                    backgroundColor: themePrimary,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        // 3) Inventory value by category (doughnut)
        new Chart(document.getElementById('categoryValueChart'), {
            type: 'doughnut',
            data: {
                labels: categoryValueData.labels,
                datasets: [{
                    data: categoryValueData.data,
                    backgroundColor: palette,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ' ' + ctx.label + ': ' +
                                Number(ctx.parsed).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                        }
                    }
                }
            }
        });

        // 4) Lowest stock vs reorder level (grouped bar)
        new Chart(document.getElementById('stockLevelChart'), {
            type: 'bar',
            data: {
                labels: stockLevelData.labels,
                datasets: [
                    {
                        label: 'Quantity on hand',
                        data: stockLevelData.quantity,
                        backgroundColor: themePrimary,
                        borderRadius: 6
                    },
                    {
                        label: 'Reorder level',
                        data: stockLevelData.reorder,
                        backgroundColor: '#FFC2D1',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    </script>
@endpush