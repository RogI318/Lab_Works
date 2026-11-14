@extends('layouts.master')

@section('title', 'Adjust Pending Count')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Adjust Pending Assignments</h2>
        <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Recount &amp; Reconcile</h5></div>
        <div class="card-body">
            <p class="text-muted">
                Use this when the instructor's actual count does not match the system
                (extension granted, excused tasks, a recount, etc.). The system calculates the
                difference and records an <strong>adjustment</strong> transaction.
            </p>

            <form action="{{ route('assignments.adjust.store') }}" method="POST">
                @csrf
                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="student_id" class="form-label fw-semibold">Student <span class="text-danger">*</span></label>
                        <select name="student_id" id="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                            <option value="">-- Select Student --</option>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}" {{ (string) old('student_id') === (string) $s->id ? 'selected' : '' }}>
                                    {{ $s->name }} ({{ $s->student_id }})
                                </option>
                            @endforeach
                        </select>
                        @error('student_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">System Count</label>
                        <input type="number" id="systemCount" class="form-control" readonly placeholder="—">
                    </div>

                    <div class="col-md-3">
                        <label for="physical_count" class="form-label fw-semibold">Actual Count <span class="text-danger">*</span></label>
                        <input type="number" name="physical_count" id="physical_count" min="0"
                               max="{{ \App\Models\Student::MAX_PENDING }}"
                               class="form-control @error('physical_count') is-invalid @enderror"
                               value="{{ old('physical_count') }}" required>
                        @error('physical_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small id="diffHint" class="text-muted"></small>
                    </div>

                    <div class="col-md-6">
                        <label for="reason" class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <select name="reason" id="reason" class="form-select @error('reason') is-invalid @enderror" required>
                            <option value="">-- Select Reason --</option>
                            @foreach($reasons as $r)
                                <option value="{{ $r }}" {{ old('reason') === $r ? 'selected' : '' }}>{{ $r }}</option>
                            @endforeach
                        </select>
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="notes" class="form-label fw-semibold">Notes</label>
                        <input type="text" name="notes" id="notes" class="form-control" value="{{ old('notes') }}"
                               placeholder="Optional details">
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Record Adjustment</button>
                    <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const backlogUrl = "{{ url('/api/students') }}";
        const sel      = document.getElementById('student_id');
        const sysInput = document.getElementById('systemCount');
        const physInput = document.getElementById('physical_count');
        const diffHint = document.getElementById('diffHint');

        function showDiff() {
            if (sysInput.value === '' || physInput.value === '') { diffHint.textContent = ''; return; }
            const diff = parseInt(physInput.value, 10) - parseInt(sysInput.value, 10);
            diffHint.textContent = diff === 0 ? 'No difference.' : 'Difference: ' + (diff > 0 ? '+' : '') + diff;
        }

        async function loadSystemCount() {
            if (!sel.value) { sysInput.value = ''; showDiff(); return; }
            try {
                const res = await fetch(`${backlogUrl}/${sel.value}/backlog`, {
                    headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
                });
                const d = await res.json();
                sysInput.value = d.pending_assignments;
            } catch (e) {
                sysInput.value = '';
            }
            showDiff();
        }

        sel.addEventListener('change', loadSystemCount);
        physInput.addEventListener('input', showDiff);
        loadSystemCount();
    </script>

@endsection