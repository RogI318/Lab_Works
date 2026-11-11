{{-- Brand / Category table with inline Edit and Delete pop-ups.
     Params: $items (paginated or plain collection, withCount('products') applied),
             $module ('brand'|'category') --}}
@php
    $label  = ucfirst($module);
    $plural = $module === 'category' ? 'categories' : 'brands';
@endphp

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th style="width: 70px;">ID</th>
                <th>{{ $label }} Name</th>
                <th>Description</th>
                <th class="text-end">Products</th>
                <th class="text-end">Units on Hand</th>
                <th style="width: 150px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td class="text-muted">#{{ $item->id }}</td>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td class="small text-muted">{{ $item->description ?: '—' }}</td>
                    <td class="text-end">
                        <span class="badge {{ $item->products_count ? 'bg-primary' : 'bg-secondary' }}">
                            {{ $item->products_count }} product{{ $item->products_count === 1 ? '' : 's' }}
                        </span>
                    </td>
                    <td class="text-end">{{ number_format((int) ($item->products_sum_quantity ?? 0)) }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('products.index', [$module . '_id' => $item->id]) }}"
                           class="btn btn-sm btn-info text-white" title="View products">
                            <i class="bi bi-box-seam"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-warning" title="Edit"
                                data-bs-toggle="modal" data-bs-target="#{{ $module }}-edit-{{ $item->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" title="Delete"
                                data-bs-toggle="modal" data-bs-target="#{{ $module }}-delete-{{ $item->id }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="bi bi-inbox fs-1 d-block"></i>
                            No {{ $plural }} recorded yet.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- The pop-ups sit outside the table so the HTML stays valid. --}}
@foreach($items as $item)
    @include('partials.item-modal', ['item' => $item, 'module' => $module])
    @include('partials.item-delete-modal', ['item' => $item, 'module' => $module])
@endforeach