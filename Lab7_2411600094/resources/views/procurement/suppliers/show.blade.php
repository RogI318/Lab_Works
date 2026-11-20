@extends('layouts.master')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>{{ $supplier->name }}
        @if ($supplier->is_active)
            <span class="badge bg-success fs-6">Active</span>
        @else
            <span class="badge bg-secondary fs-6">Inactive</span>
        @endif
    </h2>
    <div class="d-flex gap-2">
        <a href="{{ route('procurement.suppliers.index') }}" class="btn btn-outline-secondary">← Back</a>
        @can('admin')
            <a href="{{ route('procurement.suppliers.edit', $supplier) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @can('create', \App\Models\PurchaseOrder::class)
            <a href="{{ route('procurement.purchase-orders.create') }}" class="btn" style="background-color: var(--theme-accent); color: white;">+ New Purchase Order</a>
        @endcan
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">Supplier details</h5>
                <div class="row mb-2"><div class="col-5 text-muted">Contact person</div><div class="col-7">{{ $supplier->contact_person }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Email</div><div class="col-7">{{ $supplier->email }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Phone</div><div class="col-7">{{ $supplier->phone }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Address</div><div class="col-7">{{ $supplier->address ?: '—' }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Payment terms</div><div class="col-7">{{ $supplier->payment_terms }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Lead time</div><div class="col-7">{{ $supplier->lead_time_days }} day(s)</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Minimum order</div><div class="col-7">₱{{ number_format((float) $supplier->minimum_order_amount, 2) }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">Performance</h5>
                <div class="row text-center g-2">
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['total_orders'] }}</div>
                        <div class="small text-muted">Orders placed</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['delivered_orders'] }}</div>
                        <div class="small text-muted">Delivered</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['on_time_rate'] !== null ? $metrics['on_time_rate'] . '%' : '—' }}</div>
                        <div class="small text-muted">On time</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['accuracy_rate'] !== null ? $metrics['accuracy_rate'] . '%' : '—' }}</div>
                        <div class="small text-muted">Accuracy</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['avg_actual_days'] ?? '—' }}</div>
                        <div class="small text-muted">Avg days</div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="fs-4 fw-bold">{{ $metrics['avg_quoted_days'] ?? '—' }}</div>
                        <div class="small text-muted">Quoted days</div>
                    </div>
                </div>
                <div class="small text-muted mt-3">
                    Total value of placed orders: <strong>₱{{ number_format($metrics['total_value'], 2) }}</strong>.
                    "Avg days" is the real time from order to delivery; "Quoted days" is what the supplier promised.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">Products supplied</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>SKU</th><th>Product</th><th class="text-end">Supplier price</th></tr></thead>
                        <tbody>
                            @forelse ($supplier->suppliedProducts as $product)
                                <tr>
                                    <td>{{ $product->sku }}</td>
                                    <td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                                    <td class="text-end">₱{{ number_format((float) ($product->pivot->supplier_price ?? $product->unit_price), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">No products linked yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @can('admin')
                    <form method="POST" action="{{ route('procurement.suppliers.products.update', $supplier) }}" class="mt-3">
                        @csrf
                        @method('PUT')
                        <div id="supplierProducts">
                            @forelse ($supplier->suppliedProducts as $index => $product)
                                <div class="row g-2 align-items-end mb-2 supplier-product-row">
                                    <div class="col-md-5">
                                        <label class="form-label">Product</label>
                                        <select name="products[{{ $index }}][id]" class="form-select" required>
                                            <option value="">Choose product</option>
                                            @foreach ($products as $option)
                                                <option value="{{ $option->id }}" @selected($option->id === $product->id)>{{ $option->sku }} — {{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Supplier price</label>
                                        <input type="number" min="0" step="0.01" name="products[{{ $index }}][supplier_price]" class="form-control" value="{{ $product->pivot->supplier_price }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Supplier SKU</label>
                                        <input type="text" maxlength="50" name="products[{{ $index }}][supplier_sku]" class="form-control" value="{{ $product->pivot->supplier_sku }}">
                                    </div>
                                    <div class="col-md-1 form-check">
                                        <input type="hidden" name="products[{{ $index }}][is_preferred]" value="0">
                                        <input type="checkbox" name="products[{{ $index }}][is_preferred]" value="1" class="form-check-input" @checked($product->pivot->is_preferred)>
                                        <label class="form-check-label small">Preferred</label>
                                    </div>
                                </div>
                            @empty
                                <div class="row g-2 align-items-end mb-2 supplier-product-row">
                                    <div class="col-md-5">
                                        <label class="form-label">Product</label>
                                        <select name="products[0][id]" class="form-select">
                                            <option value="">Choose product</option>
                                            @foreach ($products as $option)
                                                <option value="{{ $option->id }}">{{ $option->sku }} — {{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3"><label class="form-label">Supplier price</label><input type="number" min="0" step="0.01" name="products[0][supplier_price]" class="form-control"></div>
                                    <div class="col-md-3"><label class="form-label">Supplier SKU</label><input type="text" maxlength="50" name="products[0][supplier_sku]" class="form-control"></div>
                                    <div class="col-md-1 form-check"><input type="hidden" name="products[0][is_preferred]" value="0"><input type="checkbox" name="products[0][is_preferred]" value="1" class="form-check-input"><label class="form-check-label small">Preferred</label></div>
                                </div>
                            @endforelse
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="addSupplierProduct">Add product</button>
                            <button type="submit" class="btn btn-sm btn-primary">Save product links</button>
                        </div>
                        <template id="supplierProductTemplate">
                            <div class="row g-2 align-items-end mb-2 supplier-product-row">
                                <div class="col-md-5">
                                    <label class="form-label">Product</label>
                                    <select data-name="id" class="form-select">
                                        <option value="">Choose product</option>
                                        @foreach ($products as $option)
                                            <option value="{{ $option->id }}">{{ $option->sku }} — {{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3"><label class="form-label">Supplier price</label><input data-name="supplier_price" type="number" min="0" step="0.01" class="form-control"></div>
                                <div class="col-md-3"><label class="form-label">Supplier SKU</label><input data-name="supplier_sku" type="text" maxlength="50" class="form-control"></div>
                                <div class="col-md-1 form-check"><input data-name="is_preferred" type="hidden" value="0"><input data-name="is_preferred" type="checkbox" value="1" class="form-check-input"><label class="form-check-label small">Preferred</label></div>
                            </div>
                        </template>
                        <script>
                            document.getElementById('addSupplierProduct')?.addEventListener('click', () => {
                                const container = document.getElementById('supplierProducts');
                                const template = document.getElementById('supplierProductTemplate');
                                const row = template.content.firstElementChild.cloneNode(true);
                                const index = container.querySelectorAll('.supplier-product-row').length;
                                row.querySelectorAll('[data-name]').forEach((input) => {
                                    input.name = `products[${index}][${input.dataset.name}]`;
                                });
                                container.appendChild(row);
                            });
                        </script>
                    </form>
                @endcan
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">Recent purchase orders</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Reference</th><th>Date</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td><a href="{{ route('procurement.purchase-orders.show', $order) }}">{{ $order->reference_number }}</a></td>
                                    <td>{{ $order->order_date->format('M d, Y') }}</td>
                                    <td><span class="badge bg-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span></td>
                                    <td class="text-end">₱{{ number_format((float) $order->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No purchase orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card stat-card">
    <div class="card-body">
        <h5 class="card-title">Notes</h5>
        @include('procurement.partials.notes', ['noteable' => $supplier, 'noteableType' => 'supplier'])
    </div>
</div>

@endsection
