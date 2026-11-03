
@csrf

<div class="mb-3">
    <label class="form-label">Student</label>
    <select name="student_id" class="form-select" required>
        <option value="">Select a student</option>
        @foreach($students as $student)
            <option value="{{ $student->id }}"
                @selected(old('student_id', $intervention->student_id ?? '') == $student->id)>
                {{ $student->student_id }} - {{ $student->name }}
            </option>
        @endforeach
    </select>
    @error('student_id')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Intervention Type</label>
    <select name="intervention_type" class="form-select" required>
        @foreach(['Academic Support', 'Tutoring', 'Counseling', 'Attendance Monitoring', 'Advising'] as $type)
            <option value="{{ $type }}"
                @selected(old('intervention_type', $intervention->intervention_type ?? 'Academic Support') == $type)>
                {{ $type }}
            </option>
        @endforeach
    </select>
    @error('intervention_type')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Priority</label>
    <select name="priority" class="form-select" required>
        @foreach(['Low', 'Medium', 'High', 'Critical'] as $priority)
            <option value="{{ $priority }}"
                @selected(old('priority', $intervention->priority ?? 'Medium') == $priority)>
                {{ $priority }}
            </option>
        @endforeach
    </select>
    @error('priority')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $intervention->description ?? '') }}</textarea>
    @error('description')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Action Plan</label>
    <textarea name="action_plan" class="form-control" rows="4">{{ old('action_plan', $intervention->action_plan ?? '') }}</textarea>
    @error('action_plan')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Scheduled Date</label>
    <input type="datetime-local" name="scheduled_at" class="form-control"
        value="{{ old('scheduled_at', isset($intervention) && $intervention->scheduled_at ? $intervention->scheduled_at->format('Y-m-d\TH:i') : '') }}">
    @error('scheduled_at')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-primary">
    {{ $buttonText }}
</button>

<a href="{{ route('interventions.index') }}" class="btn btn-outline-secondary">
    Cancel
</a>
