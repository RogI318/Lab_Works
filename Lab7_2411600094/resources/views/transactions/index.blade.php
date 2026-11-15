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

    {{-- ===== Filter Panel (Lab 6 Part 3 filters + Lab 7 Part 7: user, reference) ===== --}}
    <x-filters.bar title="Filter Transactions" :action="route('transactions.index')" submit="Apply">
        <x-filters.select name="product_id" label="Product" col="col-md-3"
                          placeholder="All Products"
                          :options="$products->mapWithKeys(fn ($p) => [$p->id => $p->name . ' (' . $p->sku . ')'])" />

        <x-filters.select name="type" label="Type" col="col-md-2" placeholder="All Types"
                          :options="[
                            App\Models\InventoryTransaction::TYPE_IN  => 'Stock In',
                            App\Models\InventoryTransaction::TYPE_OUT => 'Stock Out',
                            App\Models\InventoryTransaction::TYPE_ADJUSTMENT => 'Adjustment',
                          ]" />

        <x-filters.select name="user_id" label="Recorded by" col="col-md-2"
                          placeholder="Anyone" :options="$users->pluck('name', 'id')" />

        <x-filters.text name="reference" label="Reference" col="col-md-2"
                        placeholder="Note, invoice or PO..." />

        <x-filters.date-range label="Moved between" col="col-md-3"
                              fromName="date_from" toName="date_to" />
    </x-filters.bar>

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
                            <th class="ps-4">Reference Number</th>
                            <th>User</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td class="text-nowrap">{{ $transaction->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    {{-- A soft-deleted product still owns its ledger rows, so the movement
                                         names it but no longer links to it (its page is gone from public view). --}}
                                    @if ($transaction->product?->trashed())
                                        <span class="fw-semibold">{{ $transaction->product->name }}</span>
                                        <span class="badge bg-secondary ms-1">Deleted</span>
                                    @else
                                        <a href="{{ route('products.show', $transaction->product) }}" class="fw-semibold text-decoration-none">
                                            {{ $transaction->product?->name ?? '— deleted product —' }}
                                        </a>
                                    @endif
                                </td>
                                <td><span class="badge {{ $transaction->typeBadgeClass() }}">{{ $transaction->typeLabel() }}</span></td>
                                <td class="text-end fw-bold {{ $transaction->signedQuantity() >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->signedQuantity() >= 0 ? '+' : '' }}{{ $transaction->signedQuantity() }}
                                </td>
                                <td class="ps-4 text-nowrap"><code>{{ $transaction->reference_number ?: 'n/a' }}</code></td>
                                <td>{{ $transaction->user?->name ?? 'system' }}</td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    @if(auth()->user()->is_admin)
                                        <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" class="d-inline ms-1"
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
