{{-- Bootstrap 5 pop-up form shared by Brand and Category CRUD.
     Params: $module ('brand'|'category'), $item (model, or null to add),
             $source ('product-form' when opened by the "+" button), $productId --}}
@php
    $isEdit   = isset($item) && $item;
    $plural   = $module === 'category' ? 'categories' : 'brands';
    $label    = ucfirst($module);
    $modalKey = $module . ($isEdit ? "-edit-{$item->id}" : '-add');
    $action   = $isEdit ? route("{$plural}.update", $item) : route("{$plural}.store");

    // Only this modal's own submit may fill or flag these fields, because a product
    // form on the same page also posts a field called "name".
    $opened   = old('modal_key') === $modalKey;
    $name     = $opened ? old('name') : ($item->name ?? '');
    $desc     = $opened ? old('description') : ($item->description ?? '');
@endphp

<div class="modal fade" id="{{ $modalKey }}" tabindex="-1" aria-labelledby="{{ $modalKey }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">

            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="{{ $modalKey }}Label">
                    <i class="bi bi-{{ $isEdit ? 'pencil-square' : 'plus-circle' }} me-1"></i>
                    {{ $isEdit ? "Edit {$label}" : "Add New {$label}" }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ $action }}">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                {{-- Identifies which modal was submitted, so it can reopen on failure. --}}
                <input type="hidden" name="modal_key" value="{{ $modalKey }}">

                @if(($source ?? null) === 'product-form')
                    <input type="hidden" name="source" value="product-form">
                    <input type="hidden" name="product_id" value="{{ $productId ?? '' }}">
                @endif

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="{{ $modalKey }}-name" class="form-label fw-semibold">
                            {{ $label }} Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" id="{{ $modalKey }}-name"
                               class="form-control @if($opened) @error('name') is-invalid @enderror @endif"
                               value="{{ $name }}"
                               placeholder="e.g. {{ $module === 'brand' ? 'Stanley' : 'Plumbing' }}" required>
                        @if($opened)
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @endif
                    </div>

                    <div class="mb-0">
                        <label for="{{ $modalKey }}-description" class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="{{ $modalKey }}-description" rows="2"
                                  class="form-control @if($opened) @error('description') is-invalid @enderror @endif"
                                  placeholder="Optional note shown on the {{ $label }} list">{{ $desc }}</textarea>
                        @if($opened)
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @endif
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-{{ $isEdit ? 'save' : 'plus-circle' }} me-1"></i>
                        {{ $isEdit ? 'Save Changes' : 'Save ' . $label }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($opened && $errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('{{ $modalKey }}')).show();
        });
    </script>
@endif
