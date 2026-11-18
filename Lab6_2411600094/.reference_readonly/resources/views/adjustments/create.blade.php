{{-- LAB 6, PART 8: Stock adjustment form --}}
{{-- resources/views/adjustments/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Stock Adjustment')

@section('content')
    <h2 class="mb-4">Stock Adjustment</h2>

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

            <form method="POST" action="{{ route('adjustments.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Product</label>
                        <select name="product_id" id="product_id" class="form-select" required>
                            <option value="">Select a product</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}" {{ old('product_id', $selected) == $p->id ? 'selected' : '' }}>
                                    {{ $p->sku }} - {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">System Quantity</label>
                        <input type="text" id="system_qty" class="form-control" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Physical Count</label>
                        <input type="number" name="physical_count" min="0" class="form-control" value="{{ old('physical_count') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reason</label>
                        <select name="reason" class="form-select" required>
                            <option value="">Select a reason</option>
                            @foreach ($reasons as $reason)
                                <option value="{{ $reason }}" {{ old('reason') === $reason ? 'selected' : '' }}>{{ $reason }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Adjustment</button>
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Load the current system quantity from the API when a product is chosen
const sel = document.getElementById('product_id');
const box = document.getElementById('system_qty');

async function loadSystemQty() {
    if (!sel.value) { box.value = ''; return; }

    try {
        const res = await fetch(`/api/products/${sel.value}/stock`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        box.value = (await res.json()).data.quantity;
    } catch (e) {
        box.value = 'Could not load';
    }
}

sel.addEventListener('change', loadSystemQty);
loadSystemQty(); // also runs on page load (pre-selected product or old input)
</script>
@endpush
