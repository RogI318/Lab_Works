{{--
    Lab 7 Part 5 Step 2: what the reorder engine wants to buy next.
    $reorder is the cached top-six slice of ReorderService::shortItems();
    "suggested" is the quantity that engine would order, already rounded to the
    supplier's package size and minimum.
--}}
<div class="card shadow-hover h-100" id="widget-reorder-alerts" data-widget="reorder-alerts">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-arrow-repeat me-1"></i>Reorder Alerts</h5>
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-light btn-sm">Raise order</a>
    </div>
    <div class="card-body activity-scroll">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-end">On hand</th>
                    <th class="text-end">Reorder pt</th>
                    <th class="text-end">Suggested</th>
                    <th>Supplier</th>
                </tr>
            </thead>
            <tbody data-widget-body>
                @forelse ($reorder['rows'] as $item)
                    <tr>
                        <td>
                            <a href="{{ $item['url'] }}" class="text-decoration-none">{{ $item['name'] }}</a>
                            <br><small class="text-muted"><code>{{ $item['sku'] }}</code></small>
                        </td>
                        <td class="text-end {{ $item['on_hand'] <= 0 ? 'text-danger fw-bold' : '' }}">{{ $item['on_hand'] }}</td>
                        <td class="text-end text-muted">{{ $item['point'] }}</td>
                        <td class="text-end">
                            @if ($item['suggested'] > 0)
                                <span class="badge bg-primary">{{ $item['suggested'] }}</span>
                            @else
                                <span class="badge bg-secondary">covered</span>
                            @endif
                        </td>
                        <td class="small">{{ $item['supplier'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            <i class="bi bi-check2-circle me-1"></i>Every item is above its reorder point.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
