{{-- Lab 6 Part 8 Step 2 — stock adjustment (cycle count) form, opened from the
     Transactions page button. Params come from InventoryTransactionController:
     $products (id, name, sku, quantity, reorder_level) and $reasons. --}}
@php
    $modalKey = 'adjustment-add';
    $opened   = old('modal_key') === $modalKey;
    $preselect = (int) ($opened ? old('product_id') : request('adjust'));
@endphp

<div class="modal fade" id="{{ $modalKey }}" tabindex="-1" aria-labelledby="{{ $modalKey }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">

            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="{{ $modalKey }}Label">
                    <i class="bi bi-clipboard-check me-1"></i> Stock Adjustment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('adjustments.store') }}">
                @csrf
                {{-- Identifies which form was submitted, so it can reopen on failure. --}}
                <input type="hidden" name="modal_key" value="{{ $modalKey }}">

                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label for="adjust_product_id" class="form-label fw-semibold">
                                    Product <span class="text-danger">*</span>
                                </label>
                                <select name="product_id" id="adjust_product_id"
                                        class="form-select @if($opened) @error('product_id') is-invalid @enderror @endif" required>
                                    <option value=""></option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" @selected($preselect === $product->id)>
                                            {{ $product->name }} ({{ $product->sku }})
                                        </option>
                                    @endforeach
                                </select>
                                @if($opened)
                                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @endif
                            </div>

                            {{-- read-only, always loaded over AJAX --}}
                            <div class="mb-3">
                                <label for="adjust_system_quantity" class="form-label fw-semibold">Current System Quantity</label>
                                <div class="input-group">
                                    <input type="text" id="adjust_system_quantity" class="form-control bg-body-tertiary" readonly
                                           placeholder="select a product first">
                                    <span class="input-group-text">unit(s)</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="mb-3">
                                <label for="adjust_physical_count" class="form-label fw-semibold">
                                    Physical Count <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" name="physical_count" id="adjust_physical_count"
                                           class="form-control @if($opened) @error('physical_count') is-invalid @enderror @endif"
                                           min="0" max="1000000" step="1"
                                           value="{{ $opened ? old('physical_count') : '' }}" required>
                                    <span class="input-group-text">unit(s)</span>
                                </div>
                                @if($opened)
                                    @error('physical_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @endif
                            </div>

                            <div class="alert alert-secondary d-flex justify-content-between align-items-center py-2 mb-0">
                                <span class="fw-semibold small">Difference (physical − system)</span>
                                <span id="adjust_diff_preview" class="fs-5 fw-bold text-secondary">—</span>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="mb-3">
                                <label for="adjust_reason" class="form-label fw-semibold">
                                    Reason for Adjustment <span class="text-danger">*</span>
                                </label>
                                <select name="reason" id="adjust_reason"
                                        class="form-select @if($opened) @error('reason') is-invalid @enderror @endif" required>
                                    <option value="">-- choose a reason --</option>
                                    @foreach($reasons as $key => $label)
                                        <option value="{{ $key }}" @selected($opened && old('reason') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if($opened)
                                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @endif
                            </div>
                        </div>

                        <div class="col-md-7">
                            <div class="mb-0">
                                <label for="adjust_notes" class="form-label fw-semibold">
                                    Notes <span class="text-danger @if(! $opened || (old('reason') ?? '') !== 'other') d-none @endif"
                                            id="adjust_notes_required">*</span>
                                </label>
                                <textarea name="notes" id="adjust_notes" rows="2" maxlength="1000"
                                          class="form-control @if($opened) @error('notes') is-invalid @enderror @endif"
                                          placeholder="Counted by / date / explanation (required for “Other”)">{{ $opened ? old('notes') : '' }}</textarea>
                                @if($opened)
                                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="adjust_submit" disabled>
                        <i class="bi bi-check-circle me-1"></i> Record Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container .select2-selection--single {
            height: auto;
            padding: 8px 12px;
            border: 1px solid var(--theme-light-accent);
            border-radius: 8px;
        }
        .select2-container--default .select2-selection--single:focus { border-color: var(--theme-primary); }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Part 8 Step 2: the system quantity is AJAX-loaded and the difference is
        // previewed before submit, so the posted numbers are never trusted for it.
        const adjustBaseUrl = @json(url('adjustments'));
        const adjustSelect = document.getElementById('adjust_product_id');
        const adjustSystem = document.getElementById('adjust_system_quantity');
        const adjustPhysical = document.getElementById('adjust_physical_count');
        const adjustReason = document.getElementById('adjust_reason');
        const adjustNotesMark = document.getElementById('adjust_notes_required');
        const adjustDiff = document.getElementById('adjust_diff_preview');
        const adjustSubmit = document.getElementById('adjust_submit');

        new jQuery(adjustSelect).select2({
            theme: 'default',
            width: '100%',
            dropdownParent: document.getElementById('{{ $modalKey }}'),
            placeholder: '',
            allowClear: false,
        });

        let adjustSystemQuantity = null;
        let adjustReorderLevel = null;

        function setAdjustDifference() {
            if (adjustSystemQuantity === null || adjustPhysical.value === '') {
                adjustDiff.textContent = '—';
                adjustDiff.className = 'fs-5 fw-bold text-secondary';
                adjustSubmit.disabled = true;
                return;
            }

            const difference = (+adjustPhysical.value) - adjustSystemQuantity;

            if (difference === 0) {
                adjustDiff.textContent = '0 (nothing to adjust)';
                adjustDiff.className = 'fs-5 fw-bold text-secondary';
                adjustSubmit.disabled = true;
                return;
            }

            let text = (difference > 0 ? '+' : '') + difference;
            if (adjustReorderLevel !== null && +adjustPhysical.value <= adjustReorderLevel) {
                text += ' · at/below reorder level';
            }

            adjustDiff.textContent = text;
            adjustDiff.className = 'fs-5 fw-bold ' + (difference > 0 ? 'text-success' : 'text-danger');
            adjustSubmit.disabled = false;
        }

        async function loadAdjustSystemQuantity(id) {
            adjustSystemQuantity = null;
            adjustReorderLevel = null;
            adjustSystem.value = '';
            setAdjustDifference();

            if (!id) {
                adjustSystem.placeholder = 'select a product first';
                return;
            }

            adjustSystem.placeholder = 'loading…';

            try {
                const response = await fetch(`${adjustBaseUrl}/${id}/quantity`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);

                const data = (await response.json()).data;
                adjustSystemQuantity = data.quantity;
                adjustReorderLevel = data.reorder_level;
                adjustSystem.value = `${data.quantity} unit(s) · reorder at ${data.reorder_level} · ${data.stock_status}`;
            } catch (error) {
                adjustSystem.placeholder = 'could not load — reopen the form';
                adjustSubmit.disabled = true;
            }

            setAdjustDifference();
        }

        adjustSelect.addEventListener('change', () => loadAdjustSystemQuantity(adjustSelect.value));
        adjustPhysical.addEventListener('input', setAdjustDifference);
        adjustReason.addEventListener('change', () => {
            adjustNotesMark.classList.toggle('d-none', adjustReason.value !== 'other');
        });

        const adjustModalEl = document.getElementById('{{ $modalKey }}');
        function openAdjustModal() {
            new bootstrap.Modal(adjustModalEl).show();
        }

        // The modal is opened from a button, so it has to re-show itself after a
        // failed submit, and when another page asked for a specific product.
        @if($opened && $errors->any())
            document.addEventListener('DOMContentLoaded', openAdjustModal);
        @elseif($preselect)
            document.addEventListener('DOMContentLoaded', function () {
                openAdjustModal();
                loadAdjustSystemQuantity(adjustSelect.value);
            });
        @endif

        adjustModalEl.addEventListener('shown.bs.modal', function () {
            if (adjustSelect.value && adjustSystemQuantity === null) {
                loadAdjustSystemQuantity(adjustSelect.value);
            }
            setAdjustDifference();
        });
    </script>
@endpush
