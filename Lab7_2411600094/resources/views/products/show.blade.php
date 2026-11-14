@extends('layouts.master')

@section('title', $product->name)

@section('content')

    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">{{ $product->name }}</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <form action="{{ route('products.destroy', $product) }}" method="POST"
                  onsubmit="return confirm('Delete {{ $product->name }}? Its stock movements will also be removed.');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger"><i class="bi bi-trash me-1"></i> Delete</button>
            </form>
        </div>
    </div>

    <div class="row">
        {{-- ===== Item detail ===== --}}
        <div class="col-lg-7 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Product Information</h5>
                    <span class="badge {{ $product->statusBadgeClass() }}">{{ $product->stockStatus() }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">SKU</dt>
                        <dd class="col-sm-8"><code>{{ $product->sku }}</code></dd>

                        <dt class="col-sm-4">Category</dt>
                        <dd class="col-sm-8">{{ $product->category->name }}</dd>

                        <dt class="col-sm-4">Brand</dt>
                        <dd class="col-sm-8">{{ $product->brand->name }}</dd>

                        <dt class="col-sm-4">Supplier</dt>
                        <dd class="col-sm-8">{{ $product->supplier ?? 'Not recorded' }}</dd>

                        <dt class="col-sm-4">Quantity in Stock</dt>
                        <dd class="col-sm-8 fw-bold {{ $product->needsReorder() ? 'text-danger' : 'text-success' }}">
                            {{ number_format($product->quantity) }} units
                        </dd>

                        <dt class="col-sm-4">Low Stock Level</dt>
                        <dd class="col-sm-8">{{ number_format($product->reorder_level) }} units</dd>

                        <dt class="col-sm-4">Unit Price</dt>
                        <dd class="col-sm-8">PHP {{ number_format((float) $product->unit_price, 2) }}</dd>

                        <dt class="col-sm-4">Stock Value</dt>
                        <dd class="col-sm-8">PHP {{ number_format($product->stockValue(), 2) }}</dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8">{{ $product->description ?: 'No description provided.' }}</dd>

                        <dt class="col-sm-4">Last Updated</dt>
                        <dd class="col-sm-8">{{ $product->updated_at->format('M j, Y g:i A') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- ===== Low stock prompt + alert ===== --}}
        <div class="col-lg-5 mb-4">
            @if($product->needsReorder())
                <div class="alert {{ $product->isOutOfStock() ? 'alert-danger' : 'alert-warning' }} shadow-hover">
                    <h5 class="alert-heading">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Low stock alert
                    </h5>
                    <p class="mb-2">
                        Stock is at or below the low stock level. Suggested order:
                        <strong>{{ number_format($product->unitQuantityMissing()) }} units</strong>
                        (about PHP {{ number_format($product->unitQuantityMissing() * (float) $product->unit_price, 2) }}).
                    </p>
                    <hr>
                    <p class="mb-0 small">
                        Supplier on file: {{ $product->supplier ?? 'none recorded' }}.
                    </p>
                </div>
            @else
                <div class="alert alert-success shadow-hover">
                    <h5 class="alert-heading"><i class="bi bi-check-circle-fill me-1"></i>Healthy stock level</h5>
                    <p class="mb-0">{{ number_format($product->quantity - $product->reorder_level) }} unit(s) above the reorder point.</p>
                </div>
            @endif

            <div class="card shadow-hover">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Stock summary</h6>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Total movements recorded</span><span class="fw-bold">{{ $product->transactions()->count() }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Units received</span>
                        <span class="fw-bold text-success">{{ number_format($product->transactions()->where('type', 'in')->sum('quantity')) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Units sold</span>
                        <span class="fw-bold text-danger">{{ number_format($product->transactions()->where('type', 'out')->sum('quantity')) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Net of adjustments</span>
                        <span class="fw-bold">{{ number_format((int) $product->transactions()->adjustments()->sum('quantity')) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ===== Part 9 Step 1: full transaction history with running balance ===== --}}
    <div class="card shadow-hover">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Transaction History</h5>
            <form method="GET" action="{{ route('products.show', $product) }}" class="d-flex align-items-end gap-2">
                <div>
                    <label class="form-label small mb-1 text-white">From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div>
                    <label class="form-label small mb-1 text-white">To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <button class="btn btn-sm btn-light text-nowrap" type="submit">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                @if (request('date_from') || request('date_to'))
                    <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-light text-nowrap">Reset</a>
                @endif
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0 data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Balance after</th>
                            <th class="ps-4">Reference</th>
                            <th>Handled by</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-semibold">Opening balance (before the first movement shown)</td>
                            <td class="text-end fw-bold">{{ number_format($opening) }}</td>
                            <td colspan="2"></td>
                        </tr>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    <span class="badge {{ $transaction->typeBadgeClass() }}">{{ $transaction->typeLabel() }}</span>
                                    @if ($transaction->isAdjustment() && $transaction->reasonLabel())
                                        <div class="small text-muted">{{ $transaction->reasonLabel() }}</div>
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $transaction->signedQuantity() >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->signedQuantity() > 0 ? '+' : '' }}{{ number_format($transaction->signedQuantity()) }}
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($transaction->balance_after) }}</td>
                                <td class="ps-4 text-nowrap"><code>{{ $transaction->reference_number ?: 'n/a' }}</code></td>
                                <td>{{ $transaction->user->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No stock movements in this date range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions->isNotEmpty())
                <div class="d-flex flex-wrap gap-4 small text-muted mt-3">
                    <span>{{ number_format($transactions->count()) }} movement(s) shown</span>
                    <span>Received: <strong class="text-success">{{ number_format($transactions->sum(fn ($t) => max(0, $t->signedQuantity()))) }}</strong> unit(s)</span>
                    <span>Issued / reduced: <strong class="text-danger">{{ number_format(abs($transactions->sum(fn ($t) => min(0, $t->signedQuantity())))) }}</strong> unit(s)</span>
                    <span>Closing balance: <strong>{{ number_format($transactions->last()->balance_after) }}</strong> unit(s)</span>
                </div>
            @endif
        </div>
    </div>

@endsection
