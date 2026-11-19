@extends('layouts.master')
@section('title', 'Suppliers')
@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-0">Suppliers</h2><small class="text-muted">Manage supplier contacts and product
                assignments.</small>
        </div>
        <div class="btn-toolbar gap-2"><button class="btn btn-primary" data-bs-toggle="modal"
                data-bs-target="#addSupplier"><i class="bi bi-plus-circle me-1"></i>Add Supplier</button></div>
    </div>
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Filter Suppliers</h5><a href="{{ route('suppliers.index') }}"
                class="btn btn-outline-light btn-sm">Reset filters</a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('suppliers.index') }}" class="row g-3 align-items-end">
                <div class="col-md-6"><label class="form-label fw-semibold">Search</label><input name="search"
                        class="form-control" value="{{ request('search') }}" placeholder="Supplier, contact, or email">
                </div>
                <div class="col-md-4"><label class="form-label fw-semibold">Status</label><select name="status"
                        class="form-select">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select></div>
                <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i
                            class="bi bi-search me-1"></i>Filter</button></div>
            </form>
        </div>
    </div>
    <div class="card shadow-hover mb-4">
        <div
            class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">All Suppliers</h5><span class="badge bg-light text-dark">{{ $suppliers->total() }}
                supplier{{ $suppliers->total() !== 1 ? 's' : '' }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive supplier-table-wrap">
                <table class="table table-striped table-hover align-middle data-table supplier-table">
                    <thead>
                        <tr>
                            <th style="width:70px">ID</th>
                            <th>Supplier</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th class="text-center">Lead Time</th>
                            <th>Status</th>
                            <th class="text-end">Products</th>
                            <th style="width:190px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                            <tr>
                                <td class="text-muted">#{{ $supplier->id }}</td>
                                <td class="fw-semibold">{{ $supplier->name }}<small
                                        class="d-block text-muted">{{ $supplier->address ?: 'Not provided' }}</small></td>
                                <td>{{ $supplier->contact_person ?: 'Not provided' }}</td>
                                <td>{{ $supplier->phone ?: 'No phone' }}</td>
                                <td>{{ $supplier->email ?: 'No email' }}</td>
                                <td class="text-center">{{ $supplier->lead_time_days ?? 0 }} days</td>
                                <td><span
                                        class="badge {{ $supplier->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="text-end">{{ $supplier->products_count }}</td>
                                <td class="text-nowrap"><a class="btn btn-sm btn-info text-white"
                                        href="{{ route('suppliers.show', $supplier) }}" title="View"><i
                                            class="bi bi-eye"></i></a> <button class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal" data-bs-target="#editSupplier{{ $supplier->id }}" title="Edit"><i
                                            class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('suppliers.status', $supplier) }}" class="d-inline">
                                        @csrf @method('PATCH')<button
                                            class="btn btn-sm rounded-pill {{ $supplier->is_active ? 'btn-success' : 'btn-secondary' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</button>
                                    </form> @if(!$supplier->is_active)
                                        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="d-inline"
                                            onsubmit="return confirm('Delete this inactive supplier?')">@csrf
                                            @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" {{ $supplier->products_count ? 'disabled' : '' }}><i class="bi bi-trash"></i></button>
                                    </form>@endif
                                </td>
                            </tr>
                            <div class="modal fade" id="editSupplier{{ $supplier->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" action="{{ route('suppliers.update', $supplier) }}"
                                        class="modal-content">@csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Supplier</h5><button class="btn-close"
                                                data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            @foreach(['name' => 'Supplier name', 'contact_person' => 'Contact person', 'email' => 'Email', 'phone' => 'Phone'] as $field => $label)
                                                <div class="mb-3"><label class="form-label">{{ $label }}</label><input
                                                        name="{{ $field }}" class="form-control" value="{{ $supplier->$field }}" {{ $field === 'name' ? 'required' : '' }} @if($field === 'phone') type="tel"
                                                            inputmode="tel"
                                                            pattern="(09[0-9]{2}[- ]?[0-9]{3}[- ]?[0-9]{4}|\\+639[0-9]{2}[- ]?[0-9]{3}[- ]?[0-9]{4})"
                                                            title="Use a Philippine mobile number such as 0917-555-0100"
                                                        placeholder="0917-555-0100" @elseif($field === 'email') type="email" @endif>
                                            </div>@endforeach
                                            <div class="mb-3"><label class="form-label">Lead time (days)</label><input
                                                    type="number" name="lead_time_days" class="form-control" min="0" max="3650"
                                                    value="{{ $supplier->lead_time_days ?? 0 }}" required></div>
                                            <div class="mb-3"><label class="form-label">Status</label><select
                                                    class="form-select" name="is_active">
                                                    <option value="1" @selected($supplier->is_active)>Active</option>
                                                    <option value="0" @selected(!$supplier->is_active)>Inactive</option>
                                                </select></div>
                                            <div class="mb-3"><label class="form-label">Address</label><textarea name="address"
                                                    class="form-control">{{ $supplier->address }}</textarea></div>
                                            <div><label class="form-label">Notes</label><textarea name="notes"
                                                    class="form-control">{{ $supplier->notes }}</textarea></div>
                                        </div>
                                        <div class="modal-footer"><button class="btn btn-primary">Save changes</button></div>
                                    </form>
                                </div>
                            </div>
                        @empty<tr>
                            <td colspan="8">
                                <div class="empty-state"><i class="bi bi-inbox fs-1 d-block"></i>No suppliers match your
                                    filters.</div>
                            </td>
                        </tr>@endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $suppliers->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
    <div class="modal fade" id="addSupplier" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('suppliers.store') }}" class="modal-content">@csrf<div
                    class="modal-header">
                    <h5 class="modal-title">Add Supplier</h5><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @foreach(['name' => 'Supplier name', 'contact_person' => 'Contact person', 'email' => 'Email', 'phone' => 'Phone'] as $field => $label)
                        <div class="mb-3"><label class="form-label">{{ $label }}</label><input name="{{ $field }}"
                                class="form-control" {{ $field === 'name' ? 'required' : '' }} @if($field === 'phone') type="tel"
                                    inputmode="tel"
                                    pattern="(09[0-9]{2}[- ]?[0-9]{3}[- ]?[0-9]{4}|\\+639[0-9]{2}[- ]?[0-9]{3}[- ]?[0-9]{4})"
                                    title="Use a Philippine mobile number such as 0917-555-0100" placeholder="0917-555-0100"
                    @elseif($field === 'email') type="email" @endif></div>@endforeach
                    <div class="mb-3"><label class="form-label">Lead time (days)</label><input type="number"
                            name="lead_time_days" class="form-control" min="0" max="3650" value="0" required></div>
                    <div class="form-text mb-3">Phone format: Philippine mobile number, e.g. 09171234567 or +639171234567.
                    </div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea name="address"
                            class="form-control"></textarea></div>
                    <div><label class="form-label">Notes</label><textarea name="notes" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Add Supplier</button></div>
            </form>
        </div>
    </div>
@endsection
@push('styles')
    <style>
        .supplier-table {
            min-width: 1050px;
        }

        .supplier-table th,
        .supplier-table td {
            vertical-align: middle;
        }

        .supplier-table td:nth-child(2) {
            min-width: 170px;
        }

        @media (max-width: 767.98px) {
            .supplier-table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .supplier-table {
                min-width: 900px;
            }
        }
    </style>
@endpush