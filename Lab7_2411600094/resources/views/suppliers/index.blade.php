@extends('layouts.master')

@section('title', 'Suppliers')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Suppliers</h2>
        <div class="btn-toolbar gap-2">
            @can('manage-suppliers')
                <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Supplier
                </a>
            @endcan
        </div>
    </div>

    {{-- ===== Filter panel ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Find a Supplier</h5>
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-light btn-sm">Reset filters</a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('suppliers.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Company, contact person or e-mail..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        @foreach($statuses as $option)
                            <option value="{{ $option }}" @selected($status === $option)>
                                {{ ucfirst($option) }} ({{ $counts[$option] }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sort by</label>
                    <select name="sort" class="form-select">
                        <option value="name" @selected(request('sort') === 'name' || !request('sort'))>Company name (A-Z)</option>
                        <option value="lead_time" @selected(request('sort') === 'lead_time')>Lead time (fastest first)</option>
                        <option value="minimum_order" @selected(request('sort') === 'minimum_order')>Minimum order (highest first)</option>
                        <option value="newest" @selected(request('sort') === 'newest')>Recently added</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Search</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Supplier table ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">{{ ucfirst($status) === 'All' ? 'All Suppliers' : ucfirst($status) . ' Suppliers' }}</h5>
            <span class="badge bg-light text-dark">{{ $suppliers->total() }} supplier{{ $suppliers->total() !== 1 ? 's' : '' }}</span>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle data-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Contact Person</th>
                            <th>E-mail</th>
                            <th>Phone</th>
                            <th class="text-end">Lead Time</th>
                            <th>Terms</th>
                            <th class="text-end">Min. Order</th>
                            <th class="text-end">Products</th>
                            <th>Status</th>
                            <th style="width: 130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                            <tr>
                                <td>
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="fw-bold text-decoration-none">
                                        {{ $supplier->name }}
                                    </a>
                                </td>
                                <td>{{ $supplier->contact_person }}</td>
                                <td class="small text-break">{{ $supplier->email }}</td>
                                <td class="small text-nowrap">{{ $supplier->phone }}</td>
                                <td class="text-end">{{ $supplier->leadTimeLabel() }}</td>
                                <td><span class="badge bg-secondary">{{ $supplier->payment_terms }}</span></td>
                                <td class="text-end">
                                    {{ (float) $supplier->minimum_order_amount > 0
                                        ? 'PHP ' . number_format((float) $supplier->minimum_order_amount, 2)
                                        : 'None' }}
                                </td>
                                <td class="text-end">{{ $supplier->products_count }}</td>
                                <td>
                                    <span class="badge {{ $supplier->statusBadgeClass() }}">
                                        {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    @if ($supplier->trashed())
                                        <span class="badge bg-danger ms-1">Deleted</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if ($supplier->trashed())
                                        {{-- Lab 6 pattern: restore is admin only (SupplierPolicy::restore). --}}
                                        @can('restore', $supplier)
                                            <form action="{{ route('suppliers.restore', $supplier) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <button class="btn btn-sm btn-success" title="Restore">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    @else
                                        <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-info text-white" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @can('update', $supplier)
                                            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $supplier)
                                            <button type="button" class="btn btn-sm btn-danger" title="Delete"
                                                    data-bs-toggle="modal" data-bs-target="#supplier-delete-{{ $supplier->id }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="bi bi-truck fs-1 d-block text-muted"></i>
                                    <p class="text-muted mb-0">No suppliers match your filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $suppliers->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

    {{-- ===== Delete confirmations (same pop-up style as brands / categories) ===== --}}
    @foreach($suppliers as $supplier)
        @php $modalKey = "supplier-delete-{$supplier->id}"; @endphp
        <div class="modal fade" id="{{ $modalKey }}" tabindex="-1" aria-labelledby="{{ $modalKey }}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="{{ $modalKey }}Label">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Delete Supplier
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">
                            <p class="mb-2">
                                Retire <strong>{{ $supplier->name }}</strong>? The record is only flagged as
                                deleted, so its {{ $supplier->products_count }} product(s) and every purchase
                                order stay intact and an admin can restore it.
                            </p>
                            <p class="small text-muted mb-0">
                                Its e-mail stays reserved while the record is deleted, so add a new supplier
                                with the same address only after erasing this one for good.
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
    @endforeach

@endsection
