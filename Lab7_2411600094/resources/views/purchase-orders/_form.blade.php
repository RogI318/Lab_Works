@php
    /**
     * Lab 7 Part 2 Step 6: one partial for create and edit.
     *
     * $order     PurchaseOrder (unsaved on create)
     * $suppliers active Supplier collection
     * $taxRate   float, from config('procurement.tax_rate')
     * $mode      'create' | 'edit'
     *
     * Line rows are keyed by an arbitrary index (the item id when editing, a
     * timestamp for a new row) so adding and removing never has to renumber
     * the inputs; PHP still receives them as one items[] array.
     */
    $lines = $order->exists ? $order->items : collect();
@endphp

<form method="POST"
      action="{{ $mode === 'edit' ? route('purchase-orders.update', $order) : route('purchase-orders.store') }}"
      id="po-form">
    @csrf
    @if ($mode === 'edit')
        @method('PUT')
    @endif

    {{-- Which submit button was clicked decides draft vs pending. --}}
    <input type="hidden" name="action" id="po-action" value="save">

    {{-- ===== Header ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clipboard-plus me-1"></i>Order Details</h5>
            @if ($order->exists)
                <span class="badge bg-light text-dark">{{ $order->reference_number }}</span>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="supplier_id" class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                        <option value="">-- choose a supplier --</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}"
                                    @selected((int) old('supplier_id', $order->supplier_id) === $supplier->id)>
                                {{ $supplier->name }}
                                ({{ $supplier->leadTimeLabel() }} · {{ $supplier->payment_terms }}
                                {{ (float) $supplier->minimum_order_amount > 0
                                    ? ' · min PHP ' . number_format((float) $supplier->minimum_order_amount, 0) : '' }})
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div class="form-text" id="po-supplier-hint">
                        Pick a supplier first — the product list below follows it.
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="order_date" class="form-label fw-semibold">Order Date <span class="text-danger">*</span></label>
                    <input type="date" name="order_date" id="order_date"
                           class="form-control @error('order_date') is-invalid @enderror"
                           value="{{ old('order_date', $order->order_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
                    @error('order_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label for="expected_delivery_date" class="form-label fw-semibold">Expected Delivery</label>
                    <input type="date" name="expected_delivery_date" id="expected_delivery_date"
                           class="form-control @error('expected_delivery_date') is-invalid @enderror"
                           value="{{ old('expected_delivery_date', $order->expected_delivery_date?->format('Y-m-d')) }}">
                    @error('expected_delivery_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="notes" class="form-label fw-semibold">Internal Notes</label>
                    <textarea name="notes" id="notes" rows="2"
                              class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $order->notes) }}</textarea>
                    @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="terms" class="form-label fw-semibold">Terms Sent to the Supplier</label>
                    <textarea name="terms" id="terms" rows="2"
                              class="form-control @error('terms') is-invalid @enderror">{{ old('terms', $order->terms) }}</textarea>
                    @error('terms') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Line items ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="bi bi-list-ol me-1"></i>Products Ordered</h5>
            <button type="button" class="btn btn-outline-light btn-sm" id="po-add-line">
                <i class="bi bi-plus-lg me-1"></i>Add line
            </button>
        </div>
        <div class="card-body">
            @error('items') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
            @if ($errors->has('items.*.product_id') || $errors->has('items.*.quantity') || $errors->has('items.*.unit_price'))
                <div class="alert alert-danger py-2 small mb-3">
                    One or more lines need attention:
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $message)
                            @if (str_starts_with($message, 'Choose a product')
                                || str_starts_with($message, 'The same product')
                                || str_starts_with($message, 'One of the lines')
                                || str_starts_with($message, 'Order ')
                                || str_starts_with($message, 'Every line')
                                || str_starts_with($message, 'A unit price'))
                                <li>{{ $message }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table align-middle data-table mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 260px;">Product</th>
                            <th class="text-end">On hand</th>
                            <th style="width: 120px;" class="text-end">Qty</th>
                            <th style="width: 150px;" class="text-end">Unit price</th>
                            <th class="text-end">Line total</th>
                            <th style="width: 56px;"></th>
                        </tr>
                    </thead>
                    <tbody id="po-lines">
                        @forelse ($lines as $line)
                            @include('purchase-orders._line', [
                                'index' => $line->id,
                                'line'  => $line,
                            ])
                        @empty
                            @include('purchase-orders._line', [
                                'index' => 0,
                                'line'  => null,
                            ])
                        @endforelse
                    </tbody>
                    <tfoot class="border-top border-2">
                        <tr>
                            <td colspan="4" class="text-end fw-semibold">Subtotal</td>
                            <td class="text-end fw-semibold" colspan="2" id="po-subtotal">PHP 0.00</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end text-muted" id="po-tax-label">
                                VAT {{ rtrim(rtrim(number_format($taxRate * 100, 2), '0'), '.') }} %
                            </td>
                            <td class="text-end text-muted" colspan="2" id="po-tax">PHP 0.00</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end fw-bold fs-5">Total</td>
                            <td class="text-end fw-bold fs-5 text-primary" colspan="2" id="po-total">PHP 0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="text-muted small mb-0 mt-3">
                The unit price defaults to this supplier's agreed cost and falls back to the catalogue
                price. Totals shown here are a preview — the order is saved with the server's own arithmetic.
            </p>
        </div>

        <div class="card-footer bg-white d-flex justify-content-end gap-2 flex-wrap">
            <a href="{{ $mode === 'edit' ? route('purchase-orders.show', $order) : route('purchase-orders.index') }}"
               class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-outline-primary" data-po-action="save">
                <i class="bi bi-file-earmark me-1"></i>Save as draft
            </button>
            <button type="submit" class="btn btn-primary" data-po-action="submit">
                <i class="bi bi-send-check me-1"></i>Save and submit for approval
            </button>
        </div>
    </div>
</form>

@push('scripts')
    {{-- One blank row, cloned by the Add-line button. Declared before the
         script because the script reads it while it runs. --}}
    <template id="po-line-template">@include('purchase-orders._line', [
        'index' => '__INDEX__',
        'line'  => null,
    ])</template>

    <script>
        // ---- Line-item editor (Lab 7 Part 2 Step 4) --------------------------
        const poForm         = document.getElementById('po-form');
        const poSupplier     = document.getElementById('supplier_id');
        const poLines        = document.getElementById('po-lines');
        const poAdd          = document.getElementById('po-add-line');
        const poActionField  = document.getElementById('po-action');
        const poHint         = document.getElementById('po-supplier-hint');
        const poTaxRate      = {{ (float) $taxRate }};
        const poLookupUrl    = @json(url('purchase-orders/suppliers'));
        const poRowTemplate  = document.getElementById('po-line-template').innerHTML;

        let poCatalogue = [];   // the selected supplier's products

        const money = (value) => 'PHP ' + value.toLocaleString('en-PH', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });

        /** Rebuilds every product dropdown from the loaded catalogue. */
        function poFillProductSelects() {
            poLines.querySelectorAll('select[name$="[product_id]"]').forEach((select) => {
                const chosen = select.value;
                const current = poCatalogue.find((p) => String(p.id) === chosen);

                select.innerHTML = '<option value="">-- choose a product --</option>' + poCatalogue.map((p) =>
                    `<option value="${p.id}"${String(p.id) === chosen ? ' selected' : ''}>` +
                    `${p.name} · ${p.sku}</option>`
                ).join('');

                // A line already on the order must survive even if the product
                // is not in this supplier's list (the vendor changed, say).
                if (chosen && !current && select.dataset.initial) {
                    select.insertAdjacentHTML('afterbegin',
                        `<option value="${chosen}" selected>${select.dataset.initial}</option>`);
                }
            });
        }

        function poRecalculate() {
            let subtotal = 0;

            poLines.querySelectorAll('tr').forEach((row) => {
                const product = row.querySelector('select[name$="[product_id]"]');
                const qty     = row.querySelector('input[name$="[quantity]"]');
                const price   = row.querySelector('input[name$="[unit_price]"]');
                const cell    = row.querySelector('.po-line-total');

                if (!product || !qty || !price || !cell) return;

                product.disabled = poCatalogue.length === 0 && !product.value;

                const line = (parseInt(qty.value || 0, 10) || 0) * (parseFloat(price.value || 0) || 0);
                subtotal += Math.round(line * 100) / 100;
                cell.textContent = money(line);
            });

            const tax = Math.round(subtotal * poTaxRate * 100) / 100;

            document.getElementById('po-subtotal').textContent = money(subtotal);
            document.getElementById('po-tax').textContent      = money(tax);
            document.getElementById('po-total').textContent    = money(subtotal + tax);
        }

        /** Loads the supplier's catalogue, then refreshes the dropdowns. */
        async function poLoadCatalogue(supplierId) {
            if (!supplierId) {
                poCatalogue = [];
                poHint.textContent = 'Pick a supplier first — the product list below follows it.';
                poFillProductSelects();
                poRecalculate();
                return;
            }

            poHint.textContent = 'Loading this supplier\'s products…';

            try {
                const response = await fetch(`${poLookupUrl}/${supplierId}/products`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) throw new Error('lookup failed');

                poCatalogue = (await response.json()).data;

                poHint.textContent = poCatalogue.length === 0
                    ? 'This supplier has no products on file yet — add them on the product form first.'
                    : `${poCatalogue.length} product(s) available from this supplier.`;
            } catch (error) {
                poCatalogue = [];
                poHint.textContent = 'The product lookup failed; reload the page and try again.';
            }

            poFillProductSelects();
            poLines.querySelectorAll('select[name$="[product_id]"]').forEach(poSyncLine);
            poRecalculate();
        }

        /** Prefills price and stock from the catalogue for one line. */
        function poSyncLine(select) {
            const row     = select.closest('tr');
            const product = poCatalogue.find((p) => String(p.id) === String(select.value));
            const stock   = row.querySelector('.po-stock');
            const price   = row.querySelector('input[name$="[unit_price]"]');

            if (!product) {
                stock.textContent = select.value ? '—' : '';
                return;
            }

            stock.textContent = product.stock.toLocaleString('en-PH');
            stock.className   = 'po-stock text-end small ' +
                (product.stock <= product.reorder_level ? 'text-danger fw-semibold' : 'text-muted');

            // Agreed purchase cost first, catalogue price as the fallback.
            if (price && (!price.value || price.dataset.autofilled === '1')) {
                price.value        = (product.unit_cost ?? product.unit_price).toFixed(2);
                price.dataset.autofilled = '1';
            }

            const qty = row.querySelector('input[name$="[quantity]"]');
            if (qty && product.package_size > 1) {
                qty.step = product.package_size;
                qty.title = `This supplier packs in multiples of ${product.package_size}.`;
            }
        }

        poSupplier.addEventListener('change', () => poLoadCatalogue(poSupplier.value));

        poLines.addEventListener('change', (event) => {
            if (event.target.matches('select[name$="[product_id]"]')) {
                event.target.dataset.autofilled = '';
                poSyncLine(event.target);
            }
            poRecalculate();
        });

        poLines.addEventListener('input', (event) => {
            if (event.target.matches('input[name$="[unit_price]"]')) {
                event.target.dataset.autofilled = '';
            }
            if (event.target.matches('input[name$="[quantity]"], input[name$="[unit_price]"]')) {
                poRecalculate();
            }
        });

        poAdd.addEventListener('click', () => {
            const row = document.createElement('tr');
            // A key outside the item ids already used, so names never collide.
            row.innerHTML = poRowTemplate.replace(/__INDEX__/g, Date.now());
            poLines.appendChild(row);

            poFillProductSelects();
            poRecalculate();
            row.querySelector('select[name$="[product_id]"]').focus();
        });

        poLines.addEventListener('click', (event) => {
            const button = event.target.closest('.po-remove');
            if (!button) return;

            if (poLines.querySelectorAll('tr').length === 1) {
                // Never leave an order with no line to fill in.
                const row = button.closest('tr');
                row.querySelector('select[name$="[product_id]"]').value = '';
                row.querySelector('input[name$="[quantity]"]').value = 1;
                row.querySelector('input[name$="[unit_price]"]').value = '';
                poRecalculate();
                return;
            }

            button.closest('tr').remove();
            poRecalculate();
        });

        poForm.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-po-action]');
            if (button) poActionField.value = button.dataset.poAction;
        });

        // Initial paint: an edit already has lines, so their totals must show.
        poFillProductSelects();
        poRecalculate();
        if (poSupplier.value) poLoadCatalogue(poSupplier.value);
    </script>
@endpush
