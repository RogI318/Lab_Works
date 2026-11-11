@extends('layouts.master')

@section('title', 'Transaction #' . $transaction->id)

@section('content')

    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Transaction #{{ $transaction->id }}</h2>
        <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to list
        </a>
    </div>

    <div class="row g-4">

        {{-- Movement details --}}
        <div class="col-lg-5">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-arrow-left-right me-1"></i> Movement</h5>
                    <span class="badge {{ $transaction->typeBadgeClass() }}">{{ $transaction->typeLabel() }}</span>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt>Date / Time</dt>
                        <dd>{{ $transaction->created_at->format('F j, Y \a\t g:i A') }}</dd>

                        <dt>Quantity</dt>
                        <dd class="fw-bold fs-5 {{ $transaction->signedQuantity() >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $transaction->signedQuantity() >= 0 ? '+' : '' }}{{ $transaction->signedQuantity() }} unit(s)
                        </dd>

                        @if ($transaction->isAdjustment())
                            <dt>Reason</dt>
                            <dd>{{ $transaction->reasonLabel() }}</dd>
                        @endif

                        <dt>Reference Number</dt>
                        <dd><code>{{ $transaction->reference_number ?: 'n/a' }}</code></dd>

                        <dt>Notes</dt>
                        <dd>{{ $transaction->notes ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Product --}}
        <div class="col-lg-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-box-seam me-1"></i> Product</h5>
                </div>
                <div class="card-body">
                    @if($transaction->product)
                        <h5 class="mb-1">
                            <a href="{{ route('products.show', $transaction->product) }}"
                               class="text-decoration-none">{{ $transaction->product->name }}</a>
                        </h5>
                        <p class="text-muted mb-3"><code>{{ $transaction->product->sku }}</code>
                            &middot; {{ $transaction->product->category?->name }}
                            &middot; {{ $transaction->product->brand?->name }}</p>
                        <dl class="mb-0">
                            <dt>Quantity on hand</dt>
                            <dd class="fw-bold">{{ $transaction->product->quantity }}</dd>
                            <dt>Status</dt>
                            <dd>
                                <span class="badge {{ $transaction->product->statusBadgeClass() }}">
                                    {{ $transaction->product->stock_status }}
                                </span>
                            </dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">The product was deleted; its ledger rows went with it.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- User accountability --}}
        <div class="col-lg-3">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-1"></i> Recorded by</h5>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt>Name</dt>
                        <dd>{{ $transaction->user?->name ?? 'system (no user on record)' }}</dd>
                        @if($transaction->user)
                            <dt>Email</dt>
                            <dd class="text-break">{{ $transaction->user->email }}</dd>
                            <dt>Role</dt>
                            <dd>
                                <span class="badge {{ $transaction->user->is_admin ? 'text-bg-primary' : 'text-bg-secondary' }}">
                                    {{ $transaction->user->is_admin ? 'Admin' : 'Staff' }}
                                </span>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if(auth()->user()->is_admin)
        <div class="card shadow-hover mt-4 border-danger">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-muted">
                    <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                    Deleting reverses this movement's effect on stock (audit correction, admin only).
                </span>
                <form method="POST" action="{{ route('transactions.destroy', $transaction) }}"
                      onsubmit="return confirm('Delete transaction #{{ $transaction->id }} and reverse its stock impact?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger" type="submit">
                        <i class="bi bi-trash me-1"></i> Delete &amp; reverse
                    </button>
                </form>
            </div>
        </div>
    @endif

@endsection
