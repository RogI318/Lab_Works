{{-- LAB 6, PART 5: Stock In / Stock Out form --}}
{{-- resources/views/transactions/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Record Transaction')

@section('content')
    <h2 class="mb-4">Record Transaction</h2>

    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('transactions.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Product</label>
                        <select name="product_id" id="product_id" class="form-select" required>
                            <option value="">Select a product</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}" data-stock="{{ $p->quantity }}"
                                    {{ old('product_id', $selected) == $p->id ? 'selected' : '' }}>
                                    {{ $p->sku }} - {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="stockInfo" class="form-text"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label d-block">Type</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type" id="type_in" value="in" {{ old('type', 'in') === 'in' ? 'checked' : '' }}>
                            <label class="form-check-label" for="type_in">Stock In</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type" id="type_out" value="out" {{ old('type') === 'out' ? 'checked' : '' }}>
                            <label class="form-check-label" for="type_out">Stock Out</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="quantity" min="1" class="form-control" value="{{ old('quantity') }}" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Reference Number (optional)</label>
                        <input type="text" name="reference" class="form-control" placeholder="e.g. PO-1001" value="{{ old('reference') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Transaction</button>
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Show the current stock and limit a stock out to what we have
const sel = document.getElementById('product_id');
const qty = document.getElementById('quantity');
const info = document.getElementById('stockInfo');

function updateStock() {
    const stock = sel.selectedOptions[0]?.dataset.stock;
    const isOut = document.querySelector('input[name=type]:checked')?.value === 'out';

    info.textContent = stock !== undefined ? `Current stock: ${stock}` : '';

    if (isOut && stock !== undefined) {
        qty.max = stock;
    } else {
        qty.removeAttribute('max');
    }
}

sel.addEventListener('change', updateStock);
document.querySelectorAll('input[name=type]').forEach(r => r.addEventListener('change', updateStock));
updateStock();
</script>
@endpush
