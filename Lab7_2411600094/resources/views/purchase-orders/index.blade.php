@extends('layouts.master')

@section('title', 'Purchase Orders')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Purchase Orders</h2>
        <div class="btn-toolbar gap-2">
            @can('create', App\Models\PurchaseOrder::class)
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> New Purchase Order
                </a>
            @endcan
        </div>
    </div>

    {{-- ===== Filter panel (Lab 7 Part 7 Step 3: the shared filter components) ===== --}}
    <x-filters.bar title="Find an Order" :action="route('purchase-orders.index')">
        <x-filters.text name="search" label="Reference" col="col-md-3"
                        placeholder="Reference number..." />

        <x-filters.select name="status" label="Status" col="col-md-2" placeholder="-- every status --"
                          :options="collect($statuses)->map(fn ($label, $value) => $label . ' (' . ($counts[$value] ?? 0) . ')')" />

        <x-filters.select name="supplier" label="Supplier" col="col-md-2" placeholder="-- every supplier --"
                          :options="$suppliers->mapWithKeys(fn ($s) => [$s->id => $s->name . ($s->trashed() ? ' (deleted)' : '')])" />

        <x-filters.select name="created_by" label="Raised by" col="col-md-2" placeholder="-- anyone --"
                          :options="$users->pluck('name', 'id')" />

        <x-filters.date-range label="Ordered between" col="col-md-3" />

        <x-filters.text name="min_amount" label="Total from (PHP)" col="col-md-2"
                        type="number" step="0.01" />

        <x-filters.text name="max_amount" label="Total up to (PHP)" col="col-md-2"
                        type="number" step="0.01" />
    </x-filters.bar>

    {{-- ===== Order table ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Orders</h5>
            <span class="badge bg-light text-dark">{{ $orders->total() }} of {{ $total }} shown</span>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Supplier</th>
                            <th class="text-end">Lines</th>
                            <th>Ordered</th>
                            <th>Expected</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th>Raised by</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td class="text-nowrap">
                                    <a href="{{ route('purchase-orders.show', $order) }}" class="fw-bold text-decoration-none">
                                        {{ $order->reference_number }}
                                    </a>
                                    @if ($order->is_auto_generated)
                                        <span class="badge bg-info text-dark ms-1" title="Written by the reorder engine">auto</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('suppliers.show', $order->supplier) }}" class="text-decoration-none">
                                        {{ $order->supplier->name }}
                                    </a>
                                    @unless ($order->supplier->is_active)
                                        <span class="badge bg-secondary ms-1">{{ $order->supplier->trashed() ? 'Deleted' : 'Inactive' }}</span>
                                    @endunless
                                </td>
                                <td class="text-end">{{ $order->items_count }}</td>
                                <td class="text-nowrap">{{ $order->order_date->format('M j, Y') }}</td>
                                <td class="text-nowrap">{{ $order->expected_delivery_date?->format('M j, Y') ?? '—' }}</td>
                                <td class="text-end text-nowrap">PHP {{ number_format((float) $order->total, 2) }}</td>
                                <td><span class="badge {{ $order->badgeClass() }}">{{ $order->statusLabel() }}</span></td>
                                <td class="small">{{ $order->creator?->name ?? 'System' }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('purchase-orders.show', $order) }}"
                                       class="btn btn-sm btn-info text-white" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('update', $order)
                                        <a href="{{ route('purchase-orders.edit', $order) }}"
                                           class="btn btn-sm btn-warning" title="Edit draft">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $order)
                                        <button type="button" class="btn btn-sm btn-danger" title="Delete draft"
                                                data-bs-toggle="modal" data-bs-target="#po-delete-{{ $order->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="bi bi-receipt fs-1 d-block text-muted"></i>
                                    <p class="text-muted mb-0">No purchase orders match these filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $orders->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

    {{-- ===== Delete confirmations (drafts only, admin only) ===== --}}
    @foreach ($orders as $order)
        @can('delete', $order)
            @php $modalKey = "po-delete-{$order->id}"; @endphp
            <div class="modal fade" id="{{ $modalKey }}" tabindex="-1"
                 aria-labelledby="{{ $modalKey }}Label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="{{ $modalKey }}Label">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Delete Draft
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="{{ route('purchase-orders.destroy', $order) }}">
                            @csrf
                            @method('DELETE')
                            <div class="modal-body">
                                <p class="mb-2">
                                    Delete <strong>{{ $order->reference_number }}</strong>
                                    ({{ $order->supplier->name }}, PHP {{ number_format((float) $order->total, 2) }})?
                                </p>
                                <p class="small text-muted mb-0">
                                    Only a draft can be deleted, and nothing has been ordered from the supplier
                                    yet. The row is flagged as deleted rather than erased, so the reference
                                    number stays reserved.
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
    @endforeach

@endsection
