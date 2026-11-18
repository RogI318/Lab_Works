{{-- LAB 6, PART 9: Reports --}}
{{-- resources/views/reports/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <h2 class="mb-4">Reports</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Stock Movement Report --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Stock Movement Report</span>
            <a href="{{ route('reports.export', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label small">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">Apply</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reference</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $t)
                            <tr>
                                <td>{{ $t->created_at->format('M d, Y h:i A') }}</td>
                                <td>{{ $t->product?->sku }}</td>
                                <td>{{ $t->product?->name }}</td>
                                <td><span class="badge text-bg-{{ $t->type_color }}">{{ $t->type_label }}</span></td>
                                <td class="fw-semibold {{ $t->signed_quantity > 0 ? 'text-success' : 'text-danger' }}">{{ $t->signed_quantity > 0 ? '+' : '' }}{{ $t->signed_quantity }}</td>
                                <td>{{ $t->reference ?? '-' }}</td>
                                <td>{{ $t->user?->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No movements in this date range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Low Stock Report --}}
    <div class="card mb-4">
        <div class="card-header">Low Stock Report</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Reorder Level</th>
                            <th>Supplier</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowStock as $product)
                            <tr>
                                <td>{{ $product->sku }}</td>
                                <td><a href="{{ route('products.show', $product) }}" class="text-decoration-none">{{ $product->name }}</a></td>
                                <td>{{ $product->category }}</td>
                                <td>{{ $product->quantity }}</td>
                                <td>{{ $product->reorder_level }}</td>
                                <td>{{ $product->supplier ?? '-' }}</td>
                                <td><span class="badge text-bg-{{ $product->stock_status_color }}">{{ $product->stock_status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No low stock items.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Inventory Valuation Report --}}
    <div class="card mb-4">
        <div class="card-header">Inventory Valuation Report</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Total Quantity</th>
                            <th>Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($valuation as $row)
                            <tr>
                                <td>{{ $row->category }}</td>
                                <td>{{ $row->total_quantity }}</td>
                                <td>₱{{ number_format($row->total_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No products yet.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end">Grand Total</td>
                            <td>₱{{ number_format($grandTotal, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
