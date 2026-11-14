@csrf

<div class="row g-3">
    <div class="col-md-4">
        <label for="code" class="form-label fw-semibold">Course Code <span class="text-danger">*</span></label>
        <input type="text" name="code" id="code"
               class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $course->code ?? '') }}"
               placeholder="e.g., WST101" required>
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-8">
        <label for="name" class="form-label fw-semibold">Course Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $course->name ?? '') }}"
               placeholder="e.g., Web Systems and Technologies" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-12">
        <label for="instructor" class="form-label fw-semibold">Instructor <span class="text-danger">*</span></label>
        <input type="text" name="instructor" id="instructor"
               class="form-control @error('instructor') is-invalid @enderror"
               value="{{ old('instructor', $course->instructor ?? '') }}"
               placeholder="e.g., Prof. Santos" required>
        @error('instructor') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Course</button>
    <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>