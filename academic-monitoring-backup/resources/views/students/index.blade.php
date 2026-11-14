@extends('layouts.master')

@section('title', 'Student Roster')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Student Roster</h2>
        <a href="{{ route('students.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add Student
        </a>
    </div>

    {{-- ===== Filter Panel (same as Lab 4, now handled by the server) ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <h5 class="mb-0">Filter Students</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('students.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Name or Student ID..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Program</label>
                    <select name="program" class="form-select">
                        <option value="">All Programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program }}" {{ request('program') === $program ? 'selected' : '' }}>{{ $program }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Academic Standing</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(['Good Standing', 'At Risk', 'Probation'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-semibold">Min GPA</label>
                    <input type="number" name="min_gpa" class="form-control" min="0" max="4" step="0.1"
                           placeholder="0.00" value="{{ request('min_gpa') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-semibold">Max GPA</label>
                    <input type="number" name="max_gpa" class="form-control" min="0" max="4" step="0.1"
                           placeholder="4.00" value="{{ request('max_gpa') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search"></i></button>
                    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Roster ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">All Students</h5>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-light text-dark">
                    {{ $students->total() }} student{{ $students->total() !== 1 ? 's' : '' }}
                </span>
                <a href="{{ route('students.export', request()->query()) }}" class="btn btn-success btn-sm">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="rosterTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Program</th>
                            <th>Year</th>
                            <th>Units</th>
                            <th>GPA</th>
                            <th>Attendance</th>
                            <th>Pending</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td><strong>{{ $student->student_id }}</strong></td>
                                <td>{{ $student->name }}</td>
                                <td>{{ $student->program }}</td>
                                <td>{{ $student->year_level }}</td>
                                <td>{{ $student->units }}</td>
                                <td class="{{ $student->gpaColorClass() }} fw-bold">{{ number_format($student->gpa, 2) }}</td>
                                <td>{{ $student->attendance }}%</td>
                                <td>{{ $student->pending_assignments }}</td>
                                <td><span class="badge {{ $student->statusBadgeClass() }}">{{ $student->status }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-info text-white" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('students.edit', $student) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('students.destroy', $student) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this student? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="bi bi-inbox fs-1 d-block text-muted"></i>
                                    <p class="text-muted mb-0">No students match your filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $students->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

@endsection