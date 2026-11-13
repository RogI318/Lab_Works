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
                  onsubmit="return confirm('Delete {{ $product->name }}?');">
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
                    <p class="mb-0">{{ number_format($product->quantity - $product->reorder_level) }} unit(s) above the low stock level.</p>
                </div>
            @endif

        </div>
    </div>

@endsection