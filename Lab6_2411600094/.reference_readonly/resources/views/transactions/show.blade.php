{{-- LAB 6, PART 5: One transaction --}}
{{-- resources/views/transactions/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Transaction #' . $transaction->id)

@section('content')
    <h2 class="mb-4">Transaction #{{ $transaction->id }}</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header">Transaction Details</div>
                <div class="card-body">
                    <p class="mb-2"><strong>Type:</strong>
                        <span class="badge text-bg-{{ $transaction->type_color }}">{{ $transaction->type_label }}</span>
                    </p>
                    <p class="mb-2"><strong>Quantity:</strong>
                        <span class="fw-semibold {{ $transaction->signed_quantity > 0 ? 'text-success' : 'text-danger' }}">{{ $transaction->signed_quantity > 0 ? '+' : '' }}{{ $transaction->signed_quantity }}</span>
                    </p>
                    <p class="mb-2"><strong>Stock:</strong> {{ $transaction->quantity_before ?? '-' }} → {{ $transaction->quantity_after ?? '-' }}</p>
                    <p class="mb-2"><strong>Reference:</strong> {{ $transaction->reference ?? '-' }}</p>
                    <p class="mb-2"><strong>Reason:</strong> {{ $transaction->reason ?? '-' }}</p>
                    <p class="mb-2"><strong>Notes:</strong> {{ $transaction->notes ?? '-' }}</p>
                    <p class="mb-0"><strong>Date:</strong> {{ $transaction->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header">Product Information</div>
                <div class="card-body">
                    @if ($transaction->product)
                        <p class="mb-2"><strong>Name:</strong>
                            <a href="{{ route('products.show', $transaction->product) }}" class="text-decoration-none">{{ $transaction->product->name }}</a>
                        </p>
                        <p class="mb-2"><strong>SKU:</strong> {{ $transaction->product->sku }}</p>
                        <p class="mb-2"><strong>Category:</strong> {{ $transaction->product->category }}</p>
                        <p class="mb-2"><strong>Current Quantity:</strong> {{ $transaction->product->quantity }}</p>
                        <p class="mb-0"><strong>Status:</strong>
                            <span class="badge text-bg-{{ $transaction->product->stock_status_color }}">{{ $transaction->product->stock_status }}</span>
                        </p>
                    @else
                        <p class="text-muted mb-0">Product no longer exists.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header">User Information</div>
                <div class="card-body">
                    <p class="mb-2"><strong>Name:</strong> {{ $transaction->user->name ?? '-' }}</p>
                    <p class="mb-0"><strong>Email:</strong> {{ $transaction->user->email ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Back to List</a>
        @can('admin')
            <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" class="d-inline"
                onsubmit="return confirm('Delete this transaction and reverse its stock change?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Delete</button>
            </form>
        @endcan
    </div>
@endsection
