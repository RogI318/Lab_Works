{{--
    Shared by sales-orders/create and sales-orders/edit.
    Needs $productOptions and (when editing) $order.
--}}
@php
    $existingLines = isset($order)
        ? $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => (float) $item->unit_price,
        ])->values()->all()
        : [];

    $lines = old('items', $existingLines);
    $lines = is_array($lines) ? array_values($lines) : [];
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row mb-3 g-3">
    <div class="col-12 col-md-4">
        <label class="form-label">Customer Name</label>
        <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $order->customer_name ?? '') }}" required>
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Customer Email</label>
        <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email', $order->customer_email ?? '') }}">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Customer Phone</label>
        <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone', $order->customer_phone ?? '') }}">
    </div>
    <div class="col-12 col-md-2">
        <label class="form-label">Order Date</label>
        <input type="date" name="order_date" class="form-control" value="{{ old('order_date', isset($order) ? $order->order_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
    </div>
</div>

<h5 class="mt-4">Line items</h5>
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th style="min-width: 220px;">Product</th>
                <th style="width: 120px;">Quantity</th>
                <th style="width: 150px;">Unit Price (₱)</th>
                <th class="text-end" style="width: 140px;">Line Total</th>
                <th style="width: 60px;"></th>
            </tr>
        </thead>
        <tbody id="itemsBody"></tbody>
    </table>
</div>
<button type="button" id="addLine" class="btn btn-outline-secondary btn-sm mb-3">+ Add line</button>

<div class="row justify-content-end mb-3">
    <div class="col-12 col-md-4">
        <div class="d-flex justify-content-between fs-5"><span>Total</span><strong id="totalText">₱0.00</strong></div>
    </div>
</div>

<div class="mb-4">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $order->notes ?? '') }}</textarea>
</div>

<script>
    (function () {
        var products = @json($productOptions);
        var lines = @json($lines);
        var body = document.getElementById('itemsBody');
        var nextIndex = 0;

        function money(n) {
            return '₱' + (Number(n) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function findProduct(id) {
            for (var i = 0; i < products.length; i++) {
                if (String(products[i].id) === String(id)) { return products[i]; }
            }
            return null;
        }

        function recalc() {
            var total = 0;
            body.querySelectorAll('tr').forEach(function (row) {
                var qty = parseFloat(row.querySelector('.qty').value) || 0;
                var price = parseFloat(row.querySelector('.price').value) || 0;
                var line = qty * price;
                row.querySelector('.line-total').textContent = money(line);
                total += line;

                var product = findProduct(row.querySelector('.product').value);
                var hint = row.querySelector('.stock-hint');
                if (product) {
                    hint.textContent = 'In stock: ' + product.stock;
                    hint.className = 'stock-hint small ' + (qty > product.stock ? 'text-danger' : 'text-muted');
                    if (qty > product.stock) { hint.textContent += ' (not enough)'; }
                } else {
                    hint.textContent = '';
                }
            });
            document.getElementById('totalText').textContent = money(total);
        }

        function addLine(line) {
            line = line || {};
            var index = nextIndex++;
            var row = document.createElement('tr');

            var tdProduct = document.createElement('td');
            var select = document.createElement('select');
            select.name = 'items[' + index + '][product_id]';
            select.className = 'form-select product';
            select.required = true;
            var empty = document.createElement('option');
            empty.value = '';
            empty.textContent = '— Choose product —';
            select.appendChild(empty);
            products.forEach(function (p) {
                var opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name + ' (' + p.sku + ')';
                if (String(line.product_id) === String(p.id)) { opt.selected = true; }
                select.appendChild(opt);
            });
            var hint = document.createElement('div');
            hint.className = 'stock-hint small text-muted';
            tdProduct.appendChild(select);
            tdProduct.appendChild(hint);

            var tdQty = document.createElement('td');
            var qty = document.createElement('input');
            qty.type = 'number';
            qty.min = '1';
            qty.step = '1';
            qty.name = 'items[' + index + '][quantity]';
            qty.className = 'form-control qty';
            qty.required = true;
            qty.value = line.quantity || 1;
            tdQty.appendChild(qty);

            var tdPrice = document.createElement('td');
            var price = document.createElement('input');
            price.type = 'number';
            price.min = '0';
            price.step = '0.01';
            price.name = 'items[' + index + '][unit_price]';
            price.className = 'form-control price';
            price.required = true;
            price.value = (line.unit_price !== undefined && line.unit_price !== null) ? line.unit_price : '';
            tdPrice.appendChild(price);

            var tdTotal = document.createElement('td');
            tdTotal.className = 'text-end line-total';

            var tdRemove = document.createElement('td');
            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-outline-danger';
            remove.textContent = '✕';
            remove.addEventListener('click', function () { row.remove(); recalc(); });
            tdRemove.appendChild(remove);

            row.appendChild(tdProduct);
            row.appendChild(tdQty);
            row.appendChild(tdPrice);
            row.appendChild(tdTotal);
            row.appendChild(tdRemove);
            body.appendChild(row);

            // choosing a product fills its selling price
            select.addEventListener('change', function () {
                var product = findProduct(select.value);
                if (product) { price.value = product.price; }
                recalc();
            });
            qty.addEventListener('input', recalc);
            price.addEventListener('input', recalc);
            recalc();
        }

        document.getElementById('addLine').addEventListener('click', function () { addLine(); });

        if (lines.length) {
            lines.forEach(addLine);
        } else {
            addLine();
        }
    })();
</script>