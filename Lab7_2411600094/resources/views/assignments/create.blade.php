@extends('layouts.master')

@section('title', 'Record Transaction')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Record Assignment Transaction</h2>
        <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Transaction Details</h5></div>
        <div class="card-body">
            <form action="{{ route('assignments.store') }}" method="POST">
                @csrf
                <div class="row g-3">

                    <div class="col-md-8">
                        <label for="student_id" class="form-label fw-semibold">Student <span class="text-danger">*</span></label>
                        <select name="student_id" id="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                            <option value="">-- Select Student --</option>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}"
                                    {{ (string) old('student_id', request('student')) === (string) $s->id ? 'selected' : '' }}>
                                    {{ $s->name }} ({{ $s->student_id }})
                                </option>
                            @endforeach
                        </select>
                        @error('student_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div id="currentInfo" class="form-text"></div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold d-block">Type <span class="text-danger">*</span></label>
                        @foreach($types as $key => $label)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="type" id="type_{{ $key }}"
                                       value="{{ $key }}" {{ old('type', 'assigned') === $key ? 'checked' : '' }}>
                                <label class="form-check-label" for="type_{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                        @error('type') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="quantity" class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity" min="1"
                               class="form-control @error('quantity') is-invalid @enderror"
                               value="{{ old('quantity', 1) }}" required>
                        <small id="qtyHint" class="text-muted"></small>
                        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-8">
                        <label for="reference_number" class="form-label fw-semibold">Reference Number</label>
                        <input type="text" name="reference_number" id="reference_number"
                               class="form-control @error('reference_number') is-invalid @enderror"
                               value="{{ old('reference_number') }}" placeholder="e.g., WST101-LAB5">
                        @error('reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="notes" class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" id="notes" rows="3"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="Optional details">{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Transaction</button>
                    <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const backlogUrl = "{{ url('/api/students') }}";
        const MAX_PENDING = {{ \App\Models\Student::MAX_PENDING }};
        const studentSel = document.getElementById('student_id');
        const qtyInput   = document.getElementById('quantity');
        const qtyHint    = document.getElementById('qtyHint');
        const info       = document.getElementById('currentInfo');
        let current = null;

        function applyLimits() {
            if (current === null) { qtyInput.removeAttribute('max'); qtyHint.textContent = ''; return; }
            const type = document.querySelector('input[name="type"]:checked')?.value;
            if (type === 'submitted') {
                qtyInput.max = current;
                qtyHint.textContent = 'Maximum you can submit: ' + current;
            } else {
                qtyInput.max = Math.max(MAX_PENDING - current, 0);
                qtyHint.textContent = 'Maximum you can assign: ' + Math.max(MAX_PENDING - current, 0);
            }
        }

        async function loadCurrent() {
            const id = studentSel.value;
            if (!id) { current = null; info.textContent = ''; applyLimits(); return; }
            try {
                const res = await fetch(`${backlogUrl}/${id}/backlog`, {
                    headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
                });
                const d = await res.json();
                current = d.pending_assignments;
                info.innerHTML = `Current pending: <strong>${d.pending_assignments}</strong> (${d.status}) · Heavy Backlog starts at ${d.limit}`;
            } catch (e) {
                current = null;
                info.textContent = 'Could not load the current count.';
            }
            applyLimits();
        }

        studentSel.addEventListener('change', loadCurrent);
        document.querySelectorAll('input[name="type"]').forEach(r => r.addEventListener('change', applyLimits));
        loadCurrent();
    </script>

@endsection