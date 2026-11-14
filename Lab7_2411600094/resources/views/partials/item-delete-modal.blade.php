{{-- Delete confirmation pop-up for a Brand or Category.
     Params: $module ('brand'|'category'), $item (model with products_count loaded) --}}
@php
    $plural   = $module === 'category' ? 'categories' : 'brands';
    $label    = ucfirst($module);
    $count    = (int) ($item->products_count ?? $item->products()->count());
    $modalKey = "{$module}-delete-{$item->id}";
@endphp

<div class="modal fade" id="{{ $modalKey }}" tabindex="-1" aria-labelledby="{{ $modalKey }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="{{ $modalKey }}Label">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Delete {{ $label }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            @if($count > 0)
                {{-- Blocked: the friendly explanation replaces the confirm button. --}}
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>{{ $item->name }}</strong> is still used by
                        {{ $count }} product{{ $count === 1 ? '' : 's' }}, so it cannot be deleted.
                        <div class="small mt-2 mb-0">
                            Open those products and assign a different {{ $label }} first.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('products.index', [$module . '_id' => $item->id]) }}" class="btn btn-outline-primary">
                        <i class="bi bi-box-seam me-1"></i> View the {{ $label }}'s products
                    </a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            @else
                <form method="POST" action="{{ route("{$plural}.destroy", $item) }}">
                    @csrf
                    @method('DELETE')

                    <div class="modal-body">
                        <p class="mb-0">
                            Delete <strong>{{ $item->name }}</strong> from the inventory?
                            No products are assigned to it, so nothing else is affected.
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Yes, delete
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
