@extends('layouts.master')

@section('title', 'New Transaction')

@section('content')

    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Record Stock Movement</h2>
        <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card shadow-hover">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-arrow-left-right me-1"></i> New Transaction</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('transactions.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="product_id" class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                            <select name="product_id" id="product_id"
                                    class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value=""></option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                        {{ $product->name }} ({{ $product->sku }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            {{-- Live stock hint filled by JavaScript --}}
                            <div id="stockHint" class="form-text"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Transaction Type <span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="type" id="type_in"
                                           value="in" @checked(old('type', 'in') === 'in')>
                                    <label class="form-check-label" for="type_in">
                                        <span class="badge bg-success">+ Stock In</span>
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="type" id="type_out"
                                           value="out" @checked(old('type') === 'out')>
                                    <label class="form-check-label" for="type_out">
                                        <span class="badge bg-danger">&minus; Stock Out</span>
                                    </label>
                                </div>
                            </div>
                            @error('type') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="quantity" class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror"
                                   min="1" max="1000000" step="1" value="{{ old('quantity', 1) }}" required>
                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div id="quantityHint" class="form-text"></div>
                        </div>

                        <div class="mb-3">
                            <label for="reference_number" class="form-label fw-semibold">Reference Number</label>
                            <input type="text" name="reference_number" id="reference_number"
                                   class="form-control @error('reference_number') is-invalid @enderror"
                                   maxlength="255" placeholder="PO / SO / INV number (optional)" value="{{ old('reference_number') }}">
                            @error('reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror"
                                      rows="3" maxlength="1000" placeholder="Context for the audit trail (optional)">{{ old('notes') }}</textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-1"></i> Record Transaction
                            </button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    {{-- Select2 for the searchable product dropdown (Part 5 Step 3) --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container .select2-selection--single {
            height: auto;
            padding: 8px 12px;
            border: 1px solid var(--theme-light-accent);
            border-radius: 8px;
        }
        .select2-container--default .select2-selection--single:focus { border-color: var(--theme-primary); }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Part 5 Step 3: show current stock, cap stock-out quantity at what exists.
        const stockData = @json($products->keyBy('id'));
        const productSelect = document.getElementById('product_id');
        const quantityInput = document.getElementById('quantity');
        const stockHint = document.getElementById('stockHint');
        const quantityHint = document.getElementById('quantityHint');

        new jQuery(productSelect).select2({
            theme: 'default', width: '100%', placeholder: '', allowClear: false
        });

        function refreshStockInfo() {
            const product = stockData[productSelect.value];
            const isOut = document.querySelector('input[name="type"]:checked')?.value === 'out';

            if (!product) {
                stockHint.textContent = '';
                quantityInput.removeAttribute('max');
                quantityHint.textContent = '';
                return;
            }

            stockHint.innerHTML = `Current stock: <strong>${product.quantity}</strong> unit(s)`
                + ` &middot; reorder level: ${product.reorder_level}`
                + ` &middot; status: <span class="text-${product.stock_status_color}">${product.stock_status}</span>`;

            if (isOut) {
                quantityInput.max = product.quantity;
                quantityHint.textContent = `Stock out is capped at the ${product.quantity} unit(s) available.`;
                quantityHint.className = 'form-text text-danger fw-semibold';
                if (+quantityInput.value > product.quantity) quantityInput.value = product.quantity;
            } else {
                quantityInput.removeAttribute('max');
                quantityHint.textContent = 'Stock in can add any positive amount.';
                quantityHint.className = 'form-text';
            }
        }

        productSelect.addEventListener('change', refreshStockInfo);
        document.querySelectorAll('input[name="type"]').forEach(r => r.addEventListener('change', refreshStockInfo));
        refreshStockInfo();
    </script>
@endpush
