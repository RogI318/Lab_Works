@extends('layouts.master')

@section('title', 'Academic Progress Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Academic Progress Report</h2>
        <p class="text-muted mb-0">Academic progress monitoring and intervention performance.</p>
    </div>
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Reports
    </a>
</div>

{{-- KPI cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Students</div>
                <div class="fs-2 fw-bold">{{ $totalStudents }}</div>
                <div class="small text-muted">Average GPA {{ number_format($avgGpa, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Heavy Backlog Students</div>
                <div class="fs-2 fw-bold text-danger">{{ $heavyBacklogCount }}</div>
                <div class="small text-muted">Average attendance {{ number_format($avgAttendance, 1) }}%</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Intervention Approval Rate</div>
                <div class="fs-2 fw-bold text-success">{{ $approvalRate }}%</div>
                <div class="small text-muted">{{ $totalInterventions }} total interventions</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Completion Rate</div>
                <div class="fs-2 fw-bold text-primary">{{ $completionRate }}%</div>
                <div class="small text-muted">
                    Avg. approval time:
                    {{ $avgApprovalHours !== null ? $avgApprovalHours . ' hrs' : 'N/A' }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Charts --}}
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">Interventions by Status</div>
            <div class="card-body">
                <canvas id="statusChart" height="230"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">Students by Academic Standing</div>
            <div class="card-body">
                <canvas id="standingChart" height="230"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">Pending Assignments by Program</div>
            <div class="card-body">
                <canvas id="programChart" height="230"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Program summary --}}
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">Program Summary</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Program</th>
                                <th class="text-end">Students</th>
                                <th class="text-end">Avg GPA</th>
                                <th class="text-end">Avg Attendance</th>
                                <th class="text-end">Pending</th>
                                <th class="text-end">Heavy Backlog</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($programs as $p)
                                <tr>
                                    <td>{{ $p->program }}</td>
                                    <td class="text-end">{{ $p->students }}</td>
                                    <td class="text-end">{{ number_format($p->avg_gpa, 2) }}</td>
                                    <td class="text-end">{{ number_format($p->avg_attendance, 1) }}%</td>
                                    <td class="text-end">{{ $p->pending }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $p->heavy > 0 ? 'danger' : 'success' }}">{{ $p->heavy }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No student data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Top backlog --}}
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">Highest Assignment Backlogs</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th class="text-end">Pending</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topBacklog as $s)
                                <tr>
                                    <td>
                                        {{ $s->name }}
                                        <br><small class="text-muted">{{ $s->student_id }}</small>
                                    </td>
                                    <td class="text-end">{{ $s->pending_assignments }}</td>
                                    <td>
                                        <span class="badge bg-{{ $s->backlog_color }}">{{ $s->backlog_status }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No student data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const statusLabels = @json(collect($statusData)->keys()->map(fn ($s) => ucfirst(str_replace('_', ' ', $s)))->values());
    const statusValues = @json(collect($statusData)->values());

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusValues,
                backgroundColor: ['#ffc107', '#0d6efd', '#0dcaf0', '#198754', '#dc3545']
            }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });

    const standingColors = {
        'Good Standing': '#198754',
        'At Risk': '#ffc107',
        'Probation': '#dc3545'
    };
    const standingLabels = @json($standingData->keys()->values());
    const standingValues = @json($standingData->values());

    new Chart(document.getElementById('standingChart'), {
        type: 'doughnut',
        data: {
            labels: standingLabels,
            datasets: [{
                data: standingValues,
                backgroundColor: standingLabels.map(label => standingColors[label] || '#6c757d')
            }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('programChart'), {
        type: 'bar',
        data: {
            labels: @json($programs->pluck('program')->values()),
            datasets: [{
                label: 'Pending assignments',
                data: @json($programs->pluck('pending')->map(fn ($v) => (int) $v)->values()),
                backgroundColor: '#0d6efd'
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
</script>
@endsection