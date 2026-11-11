@extends('layouts.master')

@section('title', 'Transactions')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Inventory Transactions</h2>
        <div class="btn-toolbar gap-2">
            <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> New Transaction
            </a>
            @if (Auth::user()->is_admin)
                {{-- Part 8: the cycle-count form opens right here, no separate page. --}}
                <button type="button" class="btn btn-outline-primary"
                        data-bs-toggle="modal" data-bs-target="#adjustment-add">
                    <i class="bi bi-clipboard-check me-1"></i> Stock Adjustment
                </button>
            @endif
        </div>
    </div>

    {{-- ===== Filter Panel (Part 3: product / type / date range) ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Filter Transactions</h5>
            <a href="{{ route('transactions.index') }}" class="btn btn-outline-light btn-sm">Reset filters</a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('transactions.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Product</label>
                    <select name="product_id" class="form-select">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="in" @selected(request('type') === 'in')>Stock In</option>
                        <option value="out" @selected(request('type') === 'out')>Stock Out</option>
                        <option value="adjustment" @selected(request('type') === 'adjustment')>Adjustment</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-funnel me-1"></i> Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Transaction table ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Stock Movements</h5>
            <span class="badge bg-light text-dark">{{ $transactions->total() }} movement{{ $transactions->total() !== 1 ? 's' : '' }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle data-table">
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th class="text-end">Quantity</th>
                            <th>Reference Number</th>
                            <th>User</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td class="text-nowrap">{{ $transaction->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    <a href="{{ route('products.show', $transaction->product) }}" class="fw-semibold text-decoration-none">
                                        {{ $transaction->product?->name ?? '— deleted product —' }}
                                    </a>
                                </td>
                                <td><span class="badge {{ $transaction->typeBadgeClass() }}">{{ $transaction->typeLabel() }}</span></td>
                                <td class="text-end fw-bold {{ $transaction->signedQuantity() >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->signedQuantity() >= 0 ? '+' : '' }}{{ $transaction->signedQuantity() }}
                                </td>
                                <td><code>{{ $transaction->reference_number ?: 'n/a' }}</code></td>
                                <td>{{ $transaction->user?->name ?? 'system' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    @if(auth()->user()->is_admin)
                                        <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" class="d-inline"
                                              onsubmit="return confirm('Delete transaction #{{ $transaction->id }}? This reverses its stock impact.');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <p class="text-muted mb-0">No transactions match your filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

    @if (Auth::user()->is_admin)
        @include('partials.adjustment-modal')
    @endif

@endsection
