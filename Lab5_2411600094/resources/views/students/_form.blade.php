@csrf

<div class="row g-3">
    {{-- Student ID --}}
    <div class="col-md-4">
        <label for="student_id" class="form-label fw-semibold">Student ID <span class="text-danger">*</span></label>
        <input type="text" name="student_id" id="student_id"
               class="form-control @error('student_id') is-invalid @enderror"
               value="{{ old('student_id', $student->student_id ?? '') }}"
               placeholder="e.g., 2026-00131" required>
        @error('student_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Name --}}
    <div class="col-md-8">
        <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $student->name ?? '') }}"
               placeholder="e.g., Juan Dela Cruz" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Program --}}
    <div class="col-md-6">
        <label for="program" class="form-label fw-semibold">Program <span class="text-danger">*</span></label>
        @php $selected = old('program', $student->program ?? ''); @endphp
        <select name="program" id="program" class="form-select @error('program') is-invalid @enderror" required>
            <option value="">-- Select Program --</option>
            @foreach(\App\Models\Student::PROGRAMS as $program)
                <option value="{{ $program }}" {{ $selected === $program ? 'selected' : '' }}>{{ $program }}</option>
            @endforeach
        </select>
        @error('program') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Year Level --}}
    <div class="col-md-3">
        <label for="year_level" class="form-label fw-semibold">Year Level <span class="text-danger">*</span></label>
        <input type="number" name="year_level" id="year_level" min="1" max="5"
               class="form-control @error('year_level') is-invalid @enderror"
               value="{{ old('year_level', $student->year_level ?? 1) }}" required>
        @error('year_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Units --}}
    <div class="col-md-3">
        <label for="units" class="form-label fw-semibold">Units <span class="text-danger">*</span></label>
        <input type="number" name="units" id="units" min="0" max="30"
               class="form-control @error('units') is-invalid @enderror"
               value="{{ old('units', $student->units ?? 18) }}" required>
        @error('units') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- GPA --}}
    <div class="col-md-4">
        <label for="gpa" class="form-label fw-semibold">GPA <span class="text-danger">*</span></label>
        <input type="number" step="0.01" name="gpa" id="gpa" min="0" max="4"
               class="form-control @error('gpa') is-invalid @enderror"
               value="{{ old('gpa', $student->gpa ?? '') }}" placeholder="0.00 – 4.00" required>
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Status is auto-computed: ≥ 2.50 Good Standing · 1.75–2.49 At Risk · &lt; 1.75 Probation
        </small>
        @error('gpa') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Attendance --}}
    <div class="col-md-4">
        <label for="attendance" class="form-label fw-semibold">Attendance (%) <span class="text-danger">*</span></label>
        <input type="number" name="attendance" id="attendance" min="0" max="100"
               class="form-control @error('attendance') is-invalid @enderror"
               value="{{ old('attendance', $student->attendance ?? 0) }}" required>
        @error('attendance') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Pending Assignments --}}
    <div class="col-md-4">
        <label for="pending_assignments" class="form-label fw-semibold">Pending Assignments <span class="text-danger">*</span></label>
        <input type="number" name="pending_assignments" id="pending_assignments" min="0" max="20"
               class="form-control @error('pending_assignments') is-invalid @enderror"
               value="{{ old('pending_assignments', $student->pending_assignments ?? 0) }}" required>
        @error('pending_assignments') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Student</button>
    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>