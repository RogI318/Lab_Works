@extends('layouts.master')

@section('title', 'Reports')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Reports</h2>
        <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
    </div>

    {{-- ===== 1. Assignment Movement Report ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Assignment Movement Report</h5></div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-6 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Generate</button>
                    <a href="{{ route('reports.export', ['date_from' => $from, 'date_to' => $to]) }}" class="btn btn-success">
                        <i class="bi bi-download me-1"></i> Export CSV
                    </a>
                </div>
            </form>

            <div class="row text-center mb-3">
                <div class="col-md-3"><div class="border-accent py-2"><small class="text-muted d-block">Assigned</small><strong class="fs-5">{{ $assigned }}</strong></div></div>
                <div class="col-md-3"><div class="border-success-custom py-2"><small class="text-muted d-block">Submitted</small><strong class="fs-5">{{ $submitted }}</strong></div></div>
                <div class="col-md-3"><div class="border-primary-custom py-2"><small class="text-muted d-block">Adjustments (net)</small><strong class="fs-5">{{ $adjusted }}</strong></div></div>
                <div class="col-md-3"><div class="border-accent py-2"><small class="text-muted d-block">Net change</small><strong class="fs-5">{{ $net > 0 ? '+' : '' }}{{ $net }}</strong></div></div>
            </div>

            <div class="table-responsive activity-scroll">
                <table class="table table-striped table-hover align-middle mb-0" id="rosterTable">
                    <thead><tr><th>Date</th><th>Student</th><th>Type</th><th>Qty</th><th>Reference</th><th>By</th></tr></thead>
                    <tbody>
                        @forelse($movements as $t)
                            <tr>
                                <td>{{ $t->created_at->format('M d, Y h:i A') }}</td>
                                <td>{{ $t->student->name ?? '—' }}</td>
                                <td><span class="badge {{ $t->typeBadgeClass() }}">{{ $t->type_label }}</span></td>
                                <td class="fw-bold">{{ $t->quantity_label }}</td>
                                <td>{{ $t->reference_number ?? '—' }}</td>
                                <td>{{ $t->user->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No movements in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== 2. Heavy Backlog Report ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Heavy Backlog Report (≥ {{ \App\Models\Student::BACKLOG_LIMIT }} pending)</h5>
            <span class="badge bg-light text-dark">{{ $heavy->count() }} students</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="rosterTable">
                    <thead><tr><th>Student ID</th><th>Name</th><th>Program</th><th>GPA</th><th>Attendance</th><th>Pending</th><th>Standing</th></tr></thead>
                    <tbody>
                        @forelse($heavy as $s)
                            <tr>
                                <td><strong>{{ $s->student_id }}</strong></td>
                                <td><a href="{{ route('students.show', $s) }}">{{ $s->name }}</a></td>
                                <td>{{ $s->program }}</td>
                                <td class="{{ $s->gpaColorClass() }} fw-bold">{{ number_format($s->gpa, 2) }}</td>
                                <td>{{ $s->attendance }}%</td>
                                <td class="fw-bold text-danger">{{ $s->pending_assignments }}</td>
                                <td><span class="badge {{ $s->statusBadgeClass() }}">{{ $s->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">No students with a heavy backlog.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== 3. Academic Performance Summary ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Academic Performance Summary</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="rosterTable">
                    <thead><tr><th>Program</th><th>Students</th><th>Avg GPA</th><th>Avg Attendance</th><th>Pending Assignments</th></tr></thead>
                    <tbody>
                        @foreach($summary as $row)
                            <tr>
                                <td><strong>{{ $row->program }}</strong></td>
                                <td>{{ $row->students }}</td>
                                <td>{{ number_format($row->avg_gpa, 2) }}</td>
                                <td>{{ round($row->avg_attendance) }}%</td>
                                <td>{{ $row->pending }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Overall</td>
                            <td>{{ $overall['students'] }}</td>
                            <td>{{ number_format($overall['avg_gpa'], 2) }}</td>
                            <td>{{ round($overall['avg_attendance']) }}%</td>
                            <td>{{ $overall['pending'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

@endsection