@extends('layouts.master')
@section('title', $direction === 'purchase' ? 'New Purchase Order' : 'New Sales Order')
@section('content')
    <div class="pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">New {{ $direction === 'purchase' ? 'Purchase' : 'Sales' }} Order</h2>
        <p class="text-muted mb-0">Creating an order does not change stock. Process it when goods arrive or are shipped.</p>
    </div>
    <form method="POST" action="{{ route('orders.store') }}">@csrf<input type="hidden" name="direction"
            value="{{ $direction }}">
        <div class="card shadow-hover mb-3">
            <div class="card-body row g-3">
                @if($direction === 'purchase')
                    <div class="col-md-6"><label class="form-label" for="supplier_id">Supplier</label>
                        <div class="input-group"><select class="form-select @error('supplier_id') is-invalid @enderror"
                                name="supplier_id" id="supplier_id" required>
                                <option value="">Choose supplier</option>@foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                {{ $supplier->name }}</option>@endforeach
                            </select><button type="button" class="btn btn-outline-primary" title="Add supplier"
                                data-bs-toggle="modal" data-bs-target="#quickAddSupplier"><i class="bi bi-plus-lg"></i></button>
                        </div>@error('supplier_id')
                        <div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                @else
                    <div class="col-md-6"><label class="form-label" for="contact_name">Customer</label><input
                            class="form-control" name="contact_name" id="contact_name" required
                            value="{{ old('contact_name') }}">@error('contact_name')
                            <div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                @endif
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Order items</h5><button type="button" class="btn btn-sm btn-outline-primary"
                            id="addOrderItem"><i class="bi bi-plus-lg me-1"></i>Add item</button>
                    </div>
                    <div id="orderItems" class="d-grid gap-3">
                        @foreach(old('items', [['product_id' => '', 'quantity' => '', 'unit_price' => '']]) as $index => $oldItem)
                            <div class="order-item border rounded p-3">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4"><label class="form-label">Product</label><select class="form-select"
                                            data-name="product_id" name="items[{{ $index }}][product_id]" required>
                                            <option value="">Choose product</option>@foreach($products as $p)
                                                <option value="{{ $p->id }}" data-stock="{{ $p->quantity }}"
                                                    @selected(($oldItem['product_id'] ?? '') == $p->id)>{{ $p->name }} ({{ $p->sku }})
                                            — {{ $p->quantity }} in stock</option>@endforeach
                                        </select>@error("items.$index.product_id")
                                        <div class="text-danger small">{{ $message }}</div>@enderror<div
                                            class="small mt-1 stock-availability text-muted"></div>
                                    </div>
                                    <div class="col-md-2"><label class="form-label">Quantity</label><input class="form-control"
                                            type="number" min="1" max="1000000" data-name="quantity"
                                            name="items[{{ $index }}][quantity]" value="{{ $oldItem['quantity'] ?? '' }}"
                                            required>@error("items.$index.quantity")
                                            <div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-2"><label class="form-label">Unit price (PHP)</label><input
                                            class="form-control" type="number" min="0" step="0.01" data-name="unit_price"
                                            name="items[{{ $index }}][unit_price]"
                                            value="{{ $products->firstWhere('id', $oldItem['product_id'] ?? null)?->unit_price }}"
                                            readonly></div>
                                    <div class="col-md-3"><span class="form-label d-block">Subtotal</span><strong
                                            class="line-subtotal">PHP
                                            {{ number_format((float) ($products->firstWhere('id', $oldItem['product_id'] ?? null)?->unit_price ?? 0) * (int) ($oldItem['quantity'] ?? 0), 2) }}</strong>
                                    </div>
                                    <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-order-item"
                                            title="Remove item" aria-label="Remove item" {{ count(old('items', [])) < 2 ? 'disabled' : '' }}><i class="bi bi-trash"></i></button></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-12 text-end fs-5">Order total: <strong id="orderTotal">PHP 0.00</strong></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes"
                        rows="2">{{ old('notes') }}</textarea></div>
            </div>
        </div><button class="btn btn-primary" id="saveOrderButton">Save Order</button> <a class="btn btn-outline-secondary"
            href="{{ route($direction === 'purchase' ? 'purchase-orders.index' : 'sales-orders.index') }}">Cancel</a>
    </form>
    <template id="orderItemTemplate">
        <div class="order-item border rounded p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label">Product</label><select class="form-select"
                        data-name="product_id" required>
                        <option value="">Choose product</option>@foreach($products as $p)
                            <option value="{{ $p->id }}" data-stock="{{ $p->quantity }}">{{ $p->name }} ({{ $p->sku }}) —
                        {{ $p->quantity }} in stock</option>@endforeach
                    </select>
                    <div class="small mt-1 stock-availability text-muted"></div>
                </div>
                <div class="col-md-2"><label class="form-label">Quantity</label><input class="form-control" type="number"
                        min="1" max="1000000" data-name="quantity" required></div>
                <div class="col-md-2"><label class="form-label">Unit price (PHP)</label><input class="form-control"
                        type="number" min="0" step="0.01" data-name="unit_price" readonly></div>
                <div class="col-md-3"><span class="form-label d-block">Subtotal</span><strong class="line-subtotal">PHP
                        0.00</strong></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-order-item"
                        title="Remove item" aria-label="Remove item"><i class="bi bi-trash"></i></button></div>
            </div>
        </div>
    </template>
    @push('scripts')
        <script>
            (() => {
                const list = document.getElementById('orderItems'), template = document.getElementById('orderItemTemplate'), add = document.getElementById('addOrderItem');
                const prices = @json($products->mapWithKeys(fn($product) => [$product->id => (float) $product->unit_price]));
                const isSale = @json($direction === 'sale');
                const money = value => 'PHP ' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                function updateTotals() { let total = 0, blocked = false; list.querySelectorAll('.order-item').forEach(row => { const select = row.querySelector('[data-name="product_id"]'), id = select.value, price = Number(prices[id] || 0), qtyInput = row.querySelector('[data-name="quantity"]'), qty = Number(qtyInput.value || 0), stock = Number(select.selectedOptions[0]?.dataset.stock || 0), help = row.querySelector('.stock-availability'); row.querySelector('[data-name="unit_price"]').value = id ? price.toFixed(2) : ''; row.querySelector('.line-subtotal').textContent = money(price * qty); if (isSale && id) { const outOfStock = stock === 0, overStock = qty > stock; qtyInput.max = stock; if (outOfStock) { qtyInput.value = ''; qtyInput.disabled = true; help.textContent = 'Out of stock — cannot process sale.'; help.className = 'small mt-1 stock-availability text-danger fw-semibold'; } else { qtyInput.disabled = false; help.textContent = overStock ? 'Quantity exceeds available stock.' : `Available: ${stock} unit(s).`; help.className = 'small mt-1 stock-availability ' + (overStock ? 'text-danger fw-semibold' : 'text-muted'); } const invalid = outOfStock || overStock; qtyInput.classList.toggle('is-invalid', invalid); blocked ||= invalid; } else { qtyInput.disabled = false; qtyInput.removeAttribute('max'); help.textContent = ''; qtyInput.classList.remove('is-invalid'); } total += price * qty; }); document.getElementById('orderTotal').textContent = money(total); const save = document.getElementById('saveOrderButton'); if (isSale) save.disabled = blocked; }
                function reindex() { [...list.querySelectorAll('.order-item')].forEach((row, i) => row.querySelectorAll('[data-name]').forEach(field => { field.name = `items[${i}][${field.dataset.name}]`; })); list.querySelectorAll('.remove-order-item').forEach(button => button.disabled = list.querySelectorAll('.order-item').length === 1); updateTotals(); }
                add.addEventListener('click', () => { const fragment = template.content.cloneNode(true); list.appendChild(fragment); reindex(); });
                list.addEventListener('click', event => { const button = event.target.closest('.remove-order-item'); if (button && list.querySelectorAll('.order-item').length > 1) { button.closest('.order-item').remove(); reindex(); } });
                list.addEventListener('input', event => { if (event.target.matches('[data-name="quantity"]')) updateTotals(); });
                list.addEventListener('change', event => { if (event.target.matches('[data-name="product_id"]')) updateTotals(); });
                reindex();
            })();
    </script>@endpush
    @if($direction === 'purchase')
        <div class="modal fade" id="quickAddSupplier" tabindex="-1" aria-labelledby="quickAddSupplierLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="quickSupplierForm" class="modal-content" method="POST" action="{{ route('suppliers.quick-store') }}">
                    @csrf<div class="modal-header">
                        <h5 class="modal-title" id="quickAddSupplierLabel">Add Supplier</h5><button type="button"
                            class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="quickSupplierError" class="alert alert-danger d-none"></div>
                        <div class="mb-3"><label class="form-label" for="quick_supplier_name">Supplier name *</label><input
                                class="form-control" id="quick_supplier_name" name="name" required maxlength="255"></div>
                        <div class="mb-3"><label class="form-label" for="quick_supplier_contact">Contact person</label><input
                                class="form-control" id="quick_supplier_contact" name="contact_person" maxlength="255"></div>
                        <div class="mb-3"><label class="form-label" for="quick_supplier_email">Email</label><input
                                class="form-control" id="quick_supplier_email" name="email" type="email" maxlength="255"></div>
                        <div><label class="form-label" for="quick_supplier_phone">Phone</label><input class="form-control"
                                id="quick_supplier_phone" name="phone" type="tel" placeholder="0917-555-0100"></div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-outline-secondary" type="button"
                            data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save
                            Supplier</button></div>
                </form>
            </div>
        </div>
        @push('scripts')
            <script>
                document.getElementById('quickSupplierForm').addEventListener('submit', async function (event) {
                    event.preventDefault(); const form = this, errorBox = document.getElementById('quickSupplierError'), submit = form.querySelector('[type="submit"]');
                    errorBox.classList.add('d-none'); submit.disabled = true;
                    try {
                        const response = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form) }); const data = await response.json();
                        if (!response.ok) { const messages = Object.values(data.errors || {}).flat(); throw new Error(messages.join(' ') || 'Could not save supplier.'); }
                        const option = new Option(data.name, data.id, true, true); document.getElementById('supplier_id').add(option); bootstrap.Modal.getOrCreateInstance(document.getElementById('quickAddSupplier')).hide(); form.reset();
                    } catch (error) { errorBox.textContent = error.message; errorBox.classList.remove('d-none'); } finally { submit.disabled = false; }
                });
        </script>@endpush
    @endif
@endsection