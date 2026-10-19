@extends('layouts.master')

@section('title', $student->name)

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold mb-0"><i class="bi bi-person-badge me-2"></i>{{ $student->name }}</h2>
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Roster
        </a>
    </div>

    {{-- Student profile --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Student Profile</h5>
            <span class="badge bg-light text-dark">{{ $student->student_id }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border-accent ps-3 py-2">
                        <small class="text-muted d-block">Student ID</small><strong>{{ $student->student_id }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border-accent ps-3 py-2">
                        <small class="text-muted d-block">Full Name</small><strong>{{ $student->name }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border-accent ps-3 py-2">
                        <small class="text-muted d-block">Program</small><strong>{{ $student->program }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border-accent ps-3 py-2">
                        <small class="text-muted d-block">Year Level</small><strong>Year {{ $student->year_level }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border-primary-custom ps-3 py-2">
                        <small class="text-muted d-block">Enrolled Units</small><strong>{{ $student->units }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border-success-custom ps-3 py-2">
                        <small class="text-muted d-block">GPA</small>
                        <strong class="{{ $student->gpaColorClass() }}">{{ number_format($student->gpa, 2) }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ps-3 py-2">
                        <small class="text-muted d-block">Attendance</small><strong>{{ $student->attendance }}%</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ps-3 py-2">
                        <small class="text-muted d-block">Pending
                            Assignments</small><strong>{{ $student->pending_assignments }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ps-3 py-2">
                        <small class="text-muted d-block">Academic Status</small>
                        <span class="badge {{ $student->statusBadgeClass() }}">{{ $student->status }}</span>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Status is auto-computed from GPA:
                <span class="badge bg-success ms-1">Good Standing</span> ≥ 2.50 ·
                <span class="badge bg-warning text-dark">At Risk</span> 1.75–2.49 ·
                <span class="badge bg-danger">Probation</span> &lt; 1.75
            </small>
        </div>
    </div>

    {{-- Action buttons --}}
    <div class="d-flex gap-2 mb-4">
        <a href="{{ route('students.edit', $student) }}" class="btn btn-warning">
            <i class="bi bi-pencil me-1"></i> Edit Student
        </a>
        <form action="{{ route('students.destroy', $student) }}" method="POST"
            onsubmit="return confirm('Delete this student? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger"><i class="bi bi-trash me-1"></i> Delete Student</button>
        </form>
    </div>

    {{-- ===== Assignment Transaction History (with running balance) ===== --}}
    <div class="card shadow-hover mb-4">
        <div
            class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Assignment History</h5>
            <div class="d-flex gap-2 align-items-center">
                <span
                    class="badge bg-{{ $student->backlog_color }} {{ $student->backlog_color === 'warning' ? 'text-dark' : '' }}">
                    {{ $student->backlog_status }} · {{ $student->pending_assignments }} pending
                </span>
                <a href="{{ route('assignments.create', ['student' => $student->id]) }}" class="btn btn-sm btn-light">
                    <i class="bi bi-plus-circle me-1"></i> Record
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('students.show', $student) }}" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('students.show', $student) }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive activity-scroll">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Reference</th>
                            <th>Reason / Notes</th>
                            <th>By</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $t)
                            <tr>
                                <td>{{ $t->created_at->format('M d, Y h:i A') }}</td>
                                <td><span class="badge {{ $t->typeBadgeClass() }}">{{ $t->type_label }}</span></td>
                                <td class="fw-bold {{ $t->quantity > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $t->quantity_label }}</td>
                                <td>{{ $t->reference_number ?? '—' }}</td>
                                <td>{{ $t->reason ?? $t->notes ?? '—' }}</td>
                                <td>{{ $t->user->name ?? '—' }}</td>
                                <td><strong>{{ $t->running_balance }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">No transactions in this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent activity of this student --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <h5 class="mb-0">Recent Activity</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Activity</th>
                            <th>Category</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $a)
                            <tr>
                                <td>{{ $a->occurred_at->format('M d, Y h:i A') }}</td>
                                <td>{{ $a->activity }}</td>
                                <td><span class="badge {{ $a->categoryBadgeClass() }}">{{ $a->category }}</span></td>
                                <td><span class="badge {{ $a->statusBadgeClass() }}">{{ $a->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No activity recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <small class="text-muted d-block mt-3">
                <i class="bi bi-clock-history me-1"></i>
                Created: {{ $student->created_at->format('M d, Y h:i A') }}
                &nbsp;|&nbsp;
                <i class="bi bi-arrow-repeat me-1"></i>
                Last updated: {{ $student->updated_at->format('M d, Y h:i A') }}
            </small>
        </div>
    </div>

@endsection