{{-- Shared by suppliers/create.blade.php and suppliers/edit.blade.php (Lab 7 Part 1 Step 6).
     Params: $supplier (model or null) --}}
@php
    // old() first, then the saved record, then the field's default — the same
    // precedence the Lab 5 product form uses. $supplier is null on create.
    $value = fn (string $field, $default = '') => old($field, $supplier?->{$field} ?? $default);
@endphp

<form method="POST" action="{{ isset($supplier) ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
    @csrf
    @isset($supplier)@method('PUT')@endisset

    <div class="row g-3">
        {{-- ===== Identity ===== --}}
        <div class="col-md-6">
            <label for="name" class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ $value('name') }}" placeholder="e.g. Metro Lumber Supply">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label for="contact_person" class="form-label fw-semibold">Contact Person <span class="text-danger">*</span></label>
            <input type="text" name="contact_person" id="contact_person"
                   class="form-control @error('contact_person') is-invalid @enderror"
                   value="{{ $value('contact_person') }}" placeholder="e.g. Rogelio Mendoza">
            @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- ===== Contact details ===== --}}
        <div class="col-md-6">
            <label for="email" class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ $value('email') }}" placeholder="e.g. sales@metrolumbersupply.ph">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">Purchase orders are sent here. Must be unique.</small>
        </div>

        <div class="col-md-6">
            <label for="phone" class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ $value('phone') }}" placeholder="e.g. 0917-555-2841 or (02) 8724-1188">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label for="address" class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
            <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror"
                      placeholder="Street, barangay, city, province and zip code">{{ $value('address') }}</textarea>
            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- ===== Commercial terms ===== --}}
        <div class="col-md-4">
            <label for="payment_terms" class="form-label fw-semibold">Payment Terms <span class="text-danger">*</span></label>
            <select name="payment_terms" id="payment_terms" class="form-select @error('payment_terms') is-invalid @enderror">
                @foreach(\App\Models\Supplier::PAYMENT_TERMS as $term)
                    <option value="{{ $term }}" @selected($value('payment_terms', 'Net 30') === $term)>{{ $term }}</option>
                @endforeach
            </select>
            @error('payment_terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label for="lead_time_days" class="form-label fw-semibold">Lead Time (days) <span class="text-danger">*</span></label>
            <input type="number" name="lead_time_days" id="lead_time_days" min="0" max="365" step="1"
                   class="form-control @error('lead_time_days') is-invalid @enderror"
                   value="{{ $value('lead_time_days', 7) }}">
            @error('lead_time_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">How long delivery usually takes.</small>
        </div>

        <div class="col-md-4">
            <label for="minimum_order_amount" class="form-label fw-semibold">Minimum Order (PHP) <span class="text-danger">*</span></label>
            <input type="number" name="minimum_order_amount" id="minimum_order_amount" min="0" step="0.01"
                   class="form-control @error('minimum_order_amount') is-invalid @enderror"
                   value="{{ $value('minimum_order_amount', '0.00') }}">
            @error('minimum_order_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">Enter 0 when the supplier has no order floor.</small>
        </div>

        <div class="col-12">
            <label for="notes" class="form-label fw-semibold">Notes</label>
            <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror"
                      placeholder="Delivery days, truck restrictions, return policy...">{{ $value('notes') }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- ===== Availability ===== --}}
        <div class="col-12">
            {{-- An unchecked box posts nothing, which would silently keep the
                 old value (and default new ones to active). The hidden 0 makes
                 "off" an explicit answer. --}}
            <input type="hidden" name="is_active" value="0">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1"
                       @checked(old('is_active', $supplier?->is_active ?? true))>
                <label class="form-check-label fw-semibold" for="is_active">
                    Active <span class="text-muted small">(inactive suppliers are hidden from the product dropdown)</span>
                </label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-{{ isset($supplier) ? 'save' : 'plus-circle' }} me-1"></i>
            {{ isset($supplier) ? 'Update Supplier' : 'Save Supplier' }}
        </button>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
