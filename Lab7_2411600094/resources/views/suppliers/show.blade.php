@extends('layouts.master')

@section('title', $supplier->name)

@section('content')

    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">{{ $supplier->name }}</h2>
        <div class="d-flex gap-2">
            @can('update', $supplier)
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            @can('delete', $supplier)
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#supplier-delete">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
            @endcan
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- ===== Status banner for a retired record ===== --}}
    @unless ($supplier->is_active)
        <div class="alert alert-warning shadow-hover d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-pause-circle me-1"></i> This supplier is <strong>inactive</strong> and no longer
                offered in the product dropdown.</span>
            @can('update', $supplier)
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-dark">Activate again</a>
            @endcan
        </div>
    @endunless

    <div class="row">
        {{-- ===== Left: the supplier card ===== --}}
        <div class="col-lg-5 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-building me-1"></i> Supplier Profile</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            <span class="badge {{ $supplier->statusBadgeClass() }}">
                                {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>

                        <dt class="col-sm-5">Contact Person</dt>
                        <dd class="col-sm-7">{{ $supplier->contact_person }}</dd>

                        <dt class="col-sm-5">E-mail</dt>
                        <dd class="col-sm-7">
                            <a href="mailto:{{ $supplier->email }}" class="text-break">{{ $supplier->email }}</a>
                        </dd>

                        <dt class="col-sm-5">Phone</dt>
                        <dd class="col-sm-7">{{ $supplier->phone }}</dd>

                        <dt class="col-sm-5">Address</dt>
                        <dd class="col-sm-7">{{ $supplier->address }}</dd>

                        <dt class="col-sm-5">Payment Terms</dt>
                        <dd class="col-sm-7"><span class="badge bg-secondary">{{ $supplier->payment_terms }}</span></dd>

                        <dt class="col-sm-5">Lead Time</dt>
                        <dd class="col-sm-7">{{ $supplier->leadTimeLabel() }}</dd>

                        <dt class="col-sm-5">Minimum Order</dt>
                        <dd class="col-sm-7">
                            {{ (float) $supplier->minimum_order_amount > 0
                                ? 'PHP ' . number_format((float) $supplier->minimum_order_amount, 2)
                                : 'No minimum' }}
                        </dd>

                        <dt class="col-sm-5">Full Contact</dt>
                        <dd class="col-sm-7 small">{{ $supplier->full_contact }}</dd>

                        <dt class="col-sm-5">Notes</dt>
                        <dd class="col-sm-7">{{ $supplier->notes ?: '—' }}</dd>

                        <dt class="col-sm-5">On File Since</dt>
                        <dd class="col-sm-7">{{ $supplier->created_at->format('M d, Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- ===== Right: performance + activity ===== --}}
        <div class="col-lg-7 mb-4">
            {{-- ===== Lab 7 Part 6 Step 1: the measured scorecard =====
                 Every number here is one aggregated query for the whole
                 supplier table, cached under the key the purchase order
                 observer clears — see SupplierPerformanceService. --}}
            <div class="card shadow-hover mb-4">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="bi bi-speedometer2 me-1"></i> Performance</h5>
                    <a href="{{ route('reports.supplier-performance') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-graph-up me-1"></i> Full report
                    </a>
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 h-100">
                                <div class="fs-3 fw-bold">{{ $supplier->products_count }}</div>
                                <div class="small text-muted">Products supplied</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 h-100">
                                <div class="fs-3 fw-bold">{{ $metrics['total_orders'] ?? 0 }}</div>
                                <div class="small text-muted">Orders placed</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 h-100">
                                <div class="fs-3 fw-bold">
                                    {{ ($metrics['on_time_percentage'] ?? null) === null ? '—' : number_format($metrics['on_time_percentage'], 1).' %' }}
                                </div>
                                <div class="small text-muted">On-time delivery</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 h-100">
                                <div class="fs-3 fw-bold">
                                    {{ ($metrics['order_accuracy_percentage'] ?? null) === null ? '—' : number_format($metrics['order_accuracy_percentage'], 1).' %' }}
                                </div>
                                <div class="small text-muted">Order accuracy</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 h-100">
                                <div class="fs-3 fw-bold">
                                    {{ ($metrics['average_delivery_days'] ?? null) === null ? '—' : number_format($metrics['average_delivery_days'], 1) }}
                                </div>
                                <div class="small text-muted">Avg days, quoted {{ $supplier->leadTimeLabel() }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 h-100">
                                <div class="fs-5 fw-bold pt-2">
                                    {{ $metrics === null ? '—' : \App\Services\SupplierPerformanceService::varianceLabel($metrics['lead_time_variance_days']) }}
                                </div>
                                <div class="small text-muted">Against the quote</div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="stat-card p-3 h-100 text-start">
                                <div class="small text-muted">Committed on {{ $metrics['total_orders'] ?? 0 }} placed order(s)</div>
                                <div class="fs-4 fw-bold">PHP {{ number_format((float) ($metrics['total_order_value'] ?? 0), 2) }}</div>
                            </div>
                        </div>
                    </div>

                    @if ($metrics === null || $metrics['received_orders'] === 0)
                        <p class="small text-muted mb-0 mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Nothing has been received from this supplier yet, so there is no delivery record to
                            score. On-time rate, accuracy and lead-time variance appear once an order closes.
                        </p>
                    @else
                        <p class="small text-muted mb-0 mt-3">
                            <i class="bi bi-clipboard-data me-1"></i>
                            Scored on {{ $metrics['received_orders'] }} received order(s)
                            ({{ $metrics['on_time_orders'] }} on time) and {{ $metrics['lines_received'] }} ordered
                            line(s) ({{ $metrics['lines_exact'] }} complete and exact).
                            Orders still on the road are counted in the spend but not against the vendor.
                            @unless ($metrics['is_active'])
                                <span class="badge bg-secondary ms-1">Retired supplier</span>
                            @endunless
                        </p>
                    @endif
                </div>
            </div>

            {{-- Polymorphic activity log (activity_logs) — the same stream Part 2
                 uses for a purchase order's approval history. --}}
            <div class="card shadow-hover">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> Recent Activity</h5>
                    <span class="badge bg-light text-dark">{{ $activities->count() }} entries</span>
                </div>
                <div class="card-body">
                    @forelse ($activities as $entry)
                        <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
                            <div>
                                <span class="badge {{ $entry->actionBadgeClass() }} me-1">{{ $entry->actionLabel() }}</span>
                                <span class="small">{{ $entry->description }}</span>
                                <div class="small text-muted">
                                    by {{ $entry->user?->name ?? 'the system' }} &middot;
                                    {{ $entry->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No activity recorded for this supplier yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Recent purchase orders ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-bag me-1"></i> Recent Purchase Orders</h5>
            <span class="badge bg-light text-dark">last 10</span>
        </div>
        <div class="card-body">
            @if ($orders->isEmpty())
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    No purchase orders have been raised against this supplier yet.
                </p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle data-table">
                        <thead>
                            <tr>
                                <th>Reference</th><th>Status</th><th class="text-end">Lines</th>
                                <th class="text-end">Total</th>
                                <th>Expected</th><th>Ordered</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Lab 7 Part 2: the real order, linked and badged. --}}
                            @foreach ($orders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('purchase-orders.show', $order) }}"
                                           class="fw-bold text-decoration-none">{{ $order->reference_number }}</a>
                                    </td>
                                    <td><span class="badge {{ $order->badgeClass() }}">{{ $order->statusLabel() }}</span></td>
                                    <td class="text-end">{{ $order->items_count }}</td>
                                    <td class="text-end">PHP {{ number_format((float) $order->total, 2) }}</td>
                                    <td>{{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</td>
                                    <td>{{ $order->order_date->format('M d, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ===== Products supplied ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-box-seam me-1"></i> Products Supplied</h5>
            <span class="badge bg-light text-dark">{{ $agreements->count() }} price agreement(s)</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle data-table">
                    <thead>
                        <tr>
                            <th>SKU</th><th>Product</th><th>Category</th><th>On-hand Qty</th>
                            <th>Their SKU</th><th class="text-end">Unit Cost</th>
                            <th class="text-end">Min. Qty</th><th class="text-end">Pack Size</th><th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agreements as $product)
                            @php
                                // 'preferred' is the primary vendor; anything else
                                // is an alternate quoted on the price agreement.
                                $isPrimary = $product->pivot->is_preferred;
                            @endphp
                            <tr>
                                <td><strong>{{ $product->sku }}</strong></td>
                                <td>
                                    <a href="{{ route('products.show', $product) }}" class="text-decoration-none">
                                        {{ $product->name }}
                                    </a>
                                </td>
                                <td class="small">{{ $product->category->name }}</td>
                                <td class="text-end {{ $product->needsReorder() ? 'fw-bold text-danger' : '' }}">
                                    {{ number_format($product->quantity) }}
                                </td>
                                <td class="small">{{ $product->pivot->supplier_sku ?: '—' }}</td>
                                <td class="text-end">PHP {{ number_format((float) $product->pivot->unit_cost, 2) }}</td>
                                <td class="text-end">{{ number_format($product->pivot->minimum_order_quantity) }}</td>
                                <td class="text-end">{{ number_format($product->pivot->package_size) }}</td>
                                <td>
                                    <span class="badge {{ $isPrimary ? 'bg-success' : 'bg-info text-dark' }}">
                                        {{ $isPrimary ? 'Preferred' : 'Alternate' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block"></i>
                                    No price agreements recorded for this supplier.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== Delete confirmation ===== --}}
    @can('delete', $supplier)
        <div class="modal fade" id="supplier-delete" tabindex="-1" aria-labelledby="supplier-deleteLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="supplier-deleteLabel">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Delete Supplier
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">
                            <p class="mb-0">
                                Retire <strong>{{ $supplier->name }}</strong>? Its
                                {{ $supplier->products_count }} product(s) stay in the inventory and an admin can
                                restore this record later.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Yes, delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

@endsection
