@extends('layouts.master')

@section('title', 'Dashboard')

@section('content')

    <div class="pb-2 mb-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 id="greeting" class="fw-bold mb-0">Inventory Dashboard</h2>
            {{-- Part 7: real-time status line, updated by public/js/dashboard-realtime.js --}}
            <small class="text-muted"><i class="bi bi-broadcast"></i> Live &middot; Last updated:
                <span id="lastUpdated">page load</span> <span id="liveStatus"></span></small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Product
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Reports
            </a>
        </div>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="row row-cols-2 row-cols-lg-5 g-3 mb-4">
        <div class="col mb-3">
            <div class="card stat-card text-center shadow-hover border-primary-custom">
                <div class="card-body">
                    <h5 class="card-title text-muted">PRODUCTS</h5>
                    <h2 id="statProducts" class="card-text fw-bold text-primary">{{ number_format($totalProducts) }}</h2>
                    <small>Items in the catalogue</small>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card stat-card text-center shadow-hover border-accent">
                <div class="card-body">
                    <h5 class="card-title text-muted">UNITS ON HAND</h5>
                    <h2 id="statUnits" class="card-text fw-bold text-primary-custom">{{ number_format($totalUnits) }}</h2>
                    <small>Across every category</small>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card stat-card text-center shadow-hover border-warning-custom">
                <div class="card-body">
                    <h5 class="card-title text-muted">LOW STOCK</h5>
                    <h2 id="statLowStock" class="card-text fw-bold text-warning">{{ number_format($lowStockCount) }}</h2>
                    <small>At or below the low stock level</small>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card stat-card text-center shadow-hover border-danger-custom">
                <div class="card-body">
                    <h5 class="card-title text-muted">OUT OF STOCK</h5>
                    <h2 id="statOutOfStock" class="card-text fw-bold text-danger">{{ number_format($outOfStockCount) }}</h2>
                    <small>Nothing left to issue</small>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card stat-card text-center shadow-hover border-success-custom">
                <div class="card-body">
                    <h5 class="card-title text-muted">INVENTORY VALUE</h5>
                    <h2 id="statValue" class="card-text fw-bold text-success">{{ number_format($inventoryValue, 2) }}</h2>
                    <small>PHP (quantity x unit price)</small>
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

        {{-- ===== Chart 1: status distribution ===== --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-light"><h5 class="mb-0">Stock Status Distribution</h5></div>
                <div class="card-body" style="height: 300px;"><canvas id="statusChart"></canvas></div>
            </div>
        </div>

        {{-- ===== Chart 2: units by category ===== --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-light"><h5 class="mb-0">Units on Hand per Category</h5></div>
                <div class="card-body" style="height: 300px;"><canvas id="categoryChart"></canvas></div>
            </div>
        </div>

        {{-- ===== Chart 3: value by category ===== --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-light"><h5 class="mb-0">Inventory Value per Category (PHP)</h5></div>
                <div class="card-body" style="height: 300px;"><canvas id="valueChart"></canvas></div>
            </div>
        </div>

        {{-- ===== Chart 4: 7-day movement ===== --}}
        <div class="col-12 mb-4">
            <div class="card shadow-hover">
                <div class="card-header bg-light"><h5 class="mb-0">Stock In vs Stock Out - Last 7 Days</h5></div>
                <div class="card-body" style="height: 280px;"><canvas id="movementChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- ===== Recently added products ===== --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">Recently Added Products</h5>
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

        {{-- ===== Latest movements ===== --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Latest Stock Movements</h5>
                    <a href="{{ route('reports.index') }}" class="btn btn-light btn-sm">Inventory report</a>
                </div>
                <div class="card-body activity-scroll">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr><th>When</th><th>Product</th><th>Type</th><th class="text-end">Qty</th><th>By</th></tr>
                        </thead>
                        <tbody id="recentMovesBody">
                            @forelse($recentMoves as $transaction)
                                <tr>
                                    <td class="small text-nowrap">{{ $transaction->created_at->diffForHumans() }}</td>
                                    <td>{{ $transaction->product->name }}</td>
                                    <td><span class="badge {{ $transaction->typeBadgeClass() }}">{{ $transaction->typeLabel() }}</span></td>
                                    <td class="text-end fw-bold">{{ $transaction->signedQuantity() > 0 ? '+' : '' }}{{ $transaction->quantity }}</td>
                                    <td class="small">{{ $transaction->user->name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No movements recorded yet.</td></tr>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

        const palette = {
            primary: '#FB6F92',
            secondary: '#FF8FAB',
            accent: '#FFB3C6',
            light: '#FFC2D1',
            success: '#28a745',
            warning: '#f3a712',
            danger: '#dc3545',
            text: '#5a3d4a',
        };

        Chart.defaults.color = palette.text;
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";

        const gridless = { grid: { display: false } };

        // ---------- Chart 1: stock status ----------
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: @json(array_keys($statusDist)),
                datasets: [{
                    data: @json(array_values($statusDist)),
                    backgroundColor: [palette.success, palette.warning, palette.danger],
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });

        // ---------- Chart 2: units per category ----------
        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: @json($unitsByCategory->pluck('name')),
                datasets: [{
                    label: 'Units on hand',
                    data: @json($unitsByCategory->pluck('products_sum_quantity')->map(fn ($v) => (int) $v)),
                    backgroundColor: palette.secondary,
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true }, x: gridless },
            },
        });

        // ---------- Chart 3: value per category ----------
        new Chart(document.getElementById('valueChart'), {
            type: 'bar',
            data: {
                labels: @json($valueByCategory->pluck('category')),
                datasets: [{
                    label: 'Stock value (PHP)',
                    data: @json($valueByCategory->pluck('stock_value')->map(fn ($v) => round((float) $v, 2))),
                    backgroundColor: palette.primary,
                    borderRadius: 6,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true }, y: gridless },
            },
        });

        // ---------- Chart 4: 7-day movement ----------
        new Chart(document.getElementById('movementChart'), {
            type: 'line',
            data: {
                labels: @json($movementDays->pluck('label')),
                datasets: [
                    {
                        label: 'Stock In',
                        data: @json($movementDays->pluck('stock_in')),
                        borderColor: palette.success,
                        backgroundColor: 'rgba(40, 167, 69, 0.15)',
                        tension: 0.35,
                        fill: true,
                    },
                    {
                        label: 'Stock Out',
                        data: @json($movementDays->pluck('stock_out')),
                        borderColor: palette.danger,
                        backgroundColor: 'rgba(220, 53, 69, 0.12)',
                        tension: 0.35,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true }, x: gridless },
            },
        });
    </script>

    {{-- Lab 6 Part 7 Step 3: 30-second AJAX polling (separate file, module pattern) --}}
    <script>
        window.LAB6_REALTIME = {
            intervalMs: 30000,
            endpoints: {
                stats:     @json(url('/api/dashboard/stats')),
                lowStock:  @json(url('/api/products/low-stock')),
                recent:    @json(url('/api/transactions/recent')),
            },
        };
    </script>
    <script src="{{ asset('js/dashboard-realtime.js') }}"></script>
@endpush
