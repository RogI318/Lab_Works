@extends('layouts.app')

@section('title', $product->name)

@section('content')

<div class="row">

    <!-- Product Details -->
    <div class="col-lg-7 mb-4">

        <div class="bg-white p-4 rounded shadow-sm h-100">

            <h2>{{ $product->name }}</h2>

            <p class="text-muted">
                {{ $product->sku }} — {{ $product->category }}
            </p>

            <table class="table">

                <tr>
                    <th>Status</th>
                    <td>

                        @php
                            $badgeClass = match ($product->status()) {
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

                <tr>
                    <th>Brand</th>
                    <td>{{ $product->brand ?? '—' }}</td>
                </tr>

                <tr>
                    <th>Quantity</th>
                    <td>{{ $product->quantity }}</td>
                </tr>

                <tr>
                    <th>Reorder Level</th>
                    <td>{{ $product->reorder_level }}</td>
                </tr>

                <tr>
                    <th>Unit Price</th>
                    <td>₱{{ number_format($product->unit_price, 2) }}</td>
                </tr>

                <tr>
                    <th>Stock Value</th>
                    <td>₱{{ number_format($product->stockValue(), 2) }}</td>
                </tr>

                <tr>
                    <th>Supplier</th>
                    <td>{{ $product->supplier ?? '—' }}</td>
                </tr>

                <tr>
                    <th>Description</th>
                    <td>{{ $product->description ?? '—' }}</td>
                </tr>

            </table>

            <div class="d-flex gap-2">

                <a
                    href="{{ route('products.edit', $product) }}"
                    class="btn btn-secondary"
                >
                    Edit
                </a>

                @if($product->quantity === 0)
                    <form
                        method="POST"
                        action="{{ route('products.deactivate', $product) }}"
                        onsubmit="return confirm('Mark this product as inactive? It will be hidden from the system.');"
                    >
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-secondary">
                            Mark Inactive
                        </button>
                    </form>
                @endif

                <a
                    href="{{ route('products.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Back to list
                </a>

            </div>

        </div>

    </div>


    <!-- Stock In / Stock Out -->
    <div class="col-lg-5 mb-4">

        <div class="bg-white p-4 rounded shadow-sm h-100">

            <h5 class="mb-3">Stock In / Stock Out</h5>

            <form
                method="POST"
                action="{{ route('products.transactions.store', $product) }}"
            >
                @csrf

                <div class="mb-3">
                    <label class="form-label">Type</label>

                    <select name="type" class="form-select" required>
                        <option value="in" {{ old('type') == 'in' ? 'selected' : '' }}>
                            Stock In
                        </option>
                        <option value="out" {{ old('type') == 'out' ? 'selected' : '' }}>
                            Stock Out
                        </option>
                    </select>

                    @error('type')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Quantity</label>

                    <input
                        type="number"
                        name="quantity"
                        class="form-control"
                        min="1"
                        value="{{ old('quantity') }}"
                        required
                    >

                    @error('quantity')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Reference (optional)</label>

                    <input
                        type="text"
                        name="reference"
                        class="form-control"
                        placeholder="e.g. PO-1001 or Invoice #25"
                        value="{{ old('reference') }}"
                    >

                    @error('reference')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <button class="btn btn-primary">
                    Save Transaction
                </button>

            </form>

        </div>

    </div>

</div>


<!-- LAB 6: date range filter for the history -->
<form method="GET" action="{{ route('products.show', $product) }}" class="card card-body py-2 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label small mb-0">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
        </div>
        <div class="col-auto">
            <label class="form-label small mb-0">To</label>
            <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-primary btn-sm">Filter history</button>
            <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
    </div>
</form>

<!-- Transaction History -->
<div class="card mb-4">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h5 class="mb-0">
            Transaction History
        </h5>

        <span class="badge bg-secondary">
            {{ $transactions->count() }} record(s)
        </span>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle">

                <thead>

                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reference</th>
                        <th>Balance After</th>
                        <th>User</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse ($transactions as $transaction)

                        <tr>

                            <td>
                                {{ $transaction->created_at->format('M d, Y h:i A') }}
                            </td>

                            <td>
                                @if ($transaction->type === 'in')
                                    <span class="badge status-badge bg-success">Stock In</span>
                                @elseif ($transaction->type === 'adjustment')
<span class="badge bg-secondary">Adjustment</span>
@else
                                    <span class="badge status-badge bg-danger">Stock Out</span>
                                @endif
                            </td>

                            <td>
                                {{ $transaction->signed_quantity > 0 ? '+' : '' }}{{ $transaction->signed_quantity }}
                            </td>

                            <td>
                                {{ $transaction->reference ?? '—' }}
                            </td>

                            <td>{{ $transaction->balance_after }}</td>

                            <td>
                                {{ $transaction->user->name ?? 'N/A' }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No transactions yet.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
