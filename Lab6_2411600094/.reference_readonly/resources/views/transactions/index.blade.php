{{-- LAB 6, PART 5: Transactions list with filters --}}
{{-- resources/views/transactions/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Transactions')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Transactions</h2>
        <a href="{{ route('transactions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Record Transaction
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('transactions.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Product</label>
                    <select name="product_id" class="form-select form-select-sm">
                        <option value="">All Products</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" {{ (string) request('product_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary btn-sm flex-fill">Apply</button>
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary btn-sm flex-fill">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Stock (before → after)</th>
                            <th>Reference</th>
                            <th>User</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $t)
                            <tr>
                                <td>{{ $t->created_at->format('M d, Y h:i A') }}</td>
                                <td>
                                    @if ($t->product)
                                        <a href="{{ route('products.show', $t->product) }}" class="text-decoration-none">{{ $t->product->name }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td><span class="badge text-bg-{{ $t->type_color }}">{{ $t->type_label }}</span></td>
                                <td class="fw-semibold {{ $t->signed_quantity > 0 ? 'text-success' : 'text-danger' }}">{{ $t->signed_quantity > 0 ? '+' : '' }}{{ $t->signed_quantity }}</td>
                                <td>{{ $t->quantity_before ?? '-' }} → {{ $t->quantity_after ?? '-' }}</td>
                                <td>{{ $t->reference ?? '-' }}</td>
                                <td>{{ $t->user->name ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('transactions.show', $t) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    @can('admin')
                                        <form method="POST" action="{{ route('transactions.destroy', $t) }}" class="d-inline"
                                            onsubmit="return confirm('Delete this transaction and reverse its stock change?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No transactions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $transactions->links('pagination::bootstrap-5') }}
    </div>
@endsection
