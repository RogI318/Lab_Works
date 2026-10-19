@extends('layouts.master')

@section('title', 'Dashboard')

@section('content')

    <div class="pb-2 mb-3 border-bottom">
        <h2 id="greeting" class="fw-bold">Dashboard</h2>
    </div>

    {{-- ===== STAT CARDS (same four as Lab 4) ===== --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-center shadow-hover border-accent">
                <div class="card-body">
                    <h5 class="card-title text-muted">📚 GPA</h5>
                    <h2 class="card-text fw-bold text-primary">{{ number_format($avgGpa, 2) }}</h2>
                    <small>Overall Grade Point Average</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-center shadow-hover border-primary-custom">
                <div class="card-body">
                    <h5 class="card-title text-muted">📖 COURSES</h5>
                    <h2 class="card-text fw-bold text-primary">{{ $courseCount }}</h2>
                    <small>Active Enrollments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-center shadow-hover border-success-custom">
                <div class="card-body">
                    <h5 class="card-title text-muted">📝 ASSIGNMENTS</h5>
                    <h2 class="card-text fw-bold text-success">{{ $pendingAssignments }}</h2>
                    <small>Pending Tasks</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-center shadow-hover border-accent">
                <div class="card-body">
                    <h5 class="card-title text-muted">✅ ATTENDANCE</h5>
                    <h2 class="card-text fw-bold text-warning">{{ $avgAttendance }}%</h2>
                    <small>This Semester</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ALERT CENTER ===== --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-hover">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Alert Center</h5>
                    <div>
                        <span class="badge bg-light text-dark">{{ $totalStudents }} students</span>
                        @if($atRiskCount + $probationCount > 0)
                            <span class="badge bg-warning text-dark">{{ $atRiskCount }} At Risk</span>
                            <span class="badge bg-danger">{{ $probationCount }} Probation</span>
                        @endif
                    </div>
                </div>
                <div class="card-body" id="alertsList">
                    @forelse($flaggedStudents as $student)
                        <div class="alert {{ $student->isOnProbation() ? 'bg-danger text-white' : 'bg-warning text-dark' }}
                                    alert-dismissible fade show d-flex align-items-center mb-2" role="alert">
                            <i class="bi {{ $student->isOnProbation() ? 'bi-exclamation-octagon-fill' : 'bi-exclamation-triangle-fill' }} me-3 fs-4"></i>
                            <div class="flex-grow-1">
                                <strong>{{ $student->status }}:</strong>
                                {{ $student->name }} ({{ $student->student_id }})
                                — GPA {{ number_format($student->gpa, 2) }},
                                Attendance {{ $student->attendance }}%,
                                {{ $student->pending_assignments }} pending
                            </div>
                            <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-light me-4">View</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @empty
                        <div class="text-center py-2">
                            <i class="bi bi-check-circle-fill text-success fs-1 d-block"></i>
                            <p class="text-muted mb-0">No at-risk students. Everything looks good!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ===== CHARTS ROW 1 ===== --}}
    <div class="row mb-4">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-hover">
                <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Quality Points by Program</h5></div>
                <div class="card-body" style="height: 300px;"><canvas id="qualityChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow-hover">
                <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Academic Standing Distribution</h5></div>
                <div class="card-body" style="height: 300px;"><canvas id="standingChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow-hover">
                <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Top Students by GPA</h5></div>
                <div class="card-body" style="height: 300px;"><canvas id="topStudentsChart"></canvas></div>
            </div>
        </div>
    </div>

    {{-- ===== CHART ROW 2 ===== --}}
    <div class="row mb-4">
        <div class="col-12 mb-4">
            <div class="card shadow-hover">
                <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Activity by Category</h5></div>
                <div class="card-body" style="height: 280px;"><canvas id="activityChart"></canvas></div>
            </div>
        </div>
    </div>

    {{-- ===== RECENT ACTIVITY ===== --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Activity</h5>
            <span class="badge bg-light text-dark">{{ $recentActivities->count() }} events</span>
        </div>
        <div class="card-body">
            <div class="table-responsive activity-scroll">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Student</th>
                            <th>Activity</th>
                            <th>Category</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentActivities as $a)
                            <tr>
                                <td>{{ $a->occurred_at->format('M d, Y h:i A') }}</td>
                                <td>{{ $a->student->name ?? '—' }}</td>
                                <td>{{ $a->activity }}</td>
                                <td><span class="badge {{ $a->categoryBadgeClass() }}">{{ $a->category }}</span></td>
                                <td><span class="badge {{ $a->statusBadgeClass() }}">{{ $a->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-3">
                                    <i class="bi bi-inbox fs-1 d-block text-muted"></i>
                                    <p class="text-muted mb-0">No recent activity.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== SCRIPTS ===== --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Time-based greeting (same as Lab 4)
        (function () {
            const h = new Date().getHours();
            let tod = 'Good Evening';
            if (h >= 5 && h < 12)       tod = 'Good Morning';
            else if (h >= 12 && h < 17) tod = 'Good Afternoon';
            else if (h >= 17 && h < 21) tod = 'Good Evening';
            else                        tod = 'Good Night';
            document.getElementById('greeting').textContent = `${tod}, ` + @json(Auth::user()->name) + '!';
        })();

        // Lab 4 palette
        const COLORS = {
            primary: '#FB6F92', secondary: '#FF8FAB', accent: '#FFB3C6', light: '#FFC2D1',
            good: '#28a745', warning: '#f3a712', danger: '#dc3545'
        };

        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 11 } } }
            }
        };

        // ---------- Chart 1: Quality Points (avg GPA) by Program ----------
        const gpaByProgram = @json($gpaByProgram);
        new Chart(document.getElementById('qualityChart'), {
            type: 'bar',
            data: {
                labels: gpaByProgram.map(d => d.program.replace('BS ', '')),
                datasets: [{
                    label: 'Avg GPA',
                    data: gpaByProgram.map(d => parseFloat(d.avg_gpa).toFixed(2)),
                    backgroundColor: COLORS.primary,
                    borderColor: '#fff', borderWidth: 1, borderRadius: 6
                }]
            },
            options: {
                ...baseOptions,
                scales: {
                    y: { beginAtZero: true, max: 4.0,
                         ticks: { stepSize: 0.5, callback: v => v.toFixed(1) },
                         title: { display: true, text: 'Average GPA' } },
                    x: { ticks: { maxRotation: 30, minRotation: 0 } }
                },
                plugins: { ...baseOptions.plugins, legend: { display: false } }
            }
        });

        // ---------- Chart 2: Academic Standing Distribution ----------
        const standingData = @json($standingDist);
        new Chart(document.getElementById('standingChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(standingData),
                datasets: [{
                    data: Object.values(standingData),
                    backgroundColor: [COLORS.good, COLORS.warning, COLORS.danger],
                    borderWidth: 2, borderColor: '#fff'
                }]
            },
            options: {
                ...baseOptions,
                cutout: '55%',
                plugins: {
                    ...baseOptions.plugins,
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return `${ctx.label}: ${ctx.parsed} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });

        // ---------- Chart 3: Top 5 Students by GPA ----------
        const barValueLabels = {
            id: 'barValueLabels',
            afterDatasetsDraw(chart) {
                const c = chart.ctx;
                const meta = chart.getDatasetMeta(0);
                const values = chart.data.datasets[0].data;
                c.save();
                c.fillStyle = '#fff';
                c.font = 'bold 12px sans-serif';
                c.textAlign = 'right';
                c.textBaseline = 'middle';
                meta.data.forEach((bar, i) => c.fillText(Number(values[i]).toFixed(2), bar.x - 8, bar.y));
                c.restore();
            }
        };

        const topStudents = @json($topStudents);
        new Chart(document.getElementById('topStudentsChart'), {
            type: 'bar',
            data: {
                labels: topStudents.map(s => s.name),
                datasets: [{
                    label: 'GPA',
                    data: topStudents.map(s => parseFloat(s.gpa)),
                    backgroundColor: COLORS.primary,
                    borderRadius: 6, borderColor: '#fff', borderWidth: 1
                }]
            },
            options: {
                ...baseOptions,
                indexAxis: 'y',
                scales: { x: { beginAtZero: true, max: 4.0,
                               ticks: { stepSize: 0.5, callback: v => v.toFixed(1) } } },
                plugins: { ...baseOptions.plugins, legend: { display: false } }
            },
            plugins: [barValueLabels]
        });

        // ---------- Chart 4: Activity by Category ----------
        const activityData = @json($activityByCategory);
        const activityPalette = {
            'Grades': '#FB6F92', 'Attendance': '#17a2b8', 'Enrollment': '#28a745', 'Advisory': '#f3a712'
        };
        new Chart(document.getElementById('activityChart'), {
            type: 'bar',
            data: {
                labels: activityData.map(d => d.category),
                datasets: [{
                    label: 'Events',
                    data: activityData.map(d => d.total),
                    backgroundColor: activityData.map(d => activityPalette[d.category] || COLORS.secondary),
                    borderColor: '#fff', borderWidth: 2, borderRadius: 8
                }]
            },
            options: {
                ...baseOptions,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 },
                               title: { display: true, text: 'Number of Events' } } },
                plugins: { ...baseOptions.plugins, legend: { display: false } }
            }
        });
    </script>

@endsection