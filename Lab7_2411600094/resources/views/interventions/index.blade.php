@extends('layouts.master')

@section('title', 'Academic Interventions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Academic Interventions</h2>
        <p class="text-muted mb-0">Manage student academic support and intervention requests.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('interventions.export', request()->query()) }}" class="btn btn-outline-success">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
        <a href="{{ route('interventions.export.pdf', request()->query()) }}" class="btn btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
        <a href="{{ route('interventions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> New Intervention
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('interventions.index') }}" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control" placeholder="Reference, type, student name or ID">
            </div>

            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach(['pending', 'approved', 'rejected', 'in_progress', 'completed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Priority</label>
                <select name="priority" class="form-select">
                    <option value="">All priorities</option>
                    @foreach(['Low', 'Medium', 'High', 'Critical'] as $priority)
                        <option value="{{ $priority }}" @selected(request('priority') === $priority)>
                            {{ $priority }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Created from</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Created to</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Records</label>
                <select name="archived" class="form-select">
                    <option value="">Active records</option>
                    <option value="only" @selected(request('archived') === 'only')>Archived (deleted) only</option>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a href="{{ route('interventions.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Intervention Records ({{ $interventions->total() }})</h5>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Student</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($interventions as $intervention)
                        <tr>
                            <td>{{ $intervention->reference_number }}</td>
                            <td>
                                {{ $intervention->student->name ?? 'Student unavailable' }}
                                <br>
                                <small class="text-muted">
                                    {{ $intervention->student->student_id ?? '' }}
                                </small>
                            </td>
                            <td>{{ $intervention->intervention_type }}</td>
                            <td>
                                <span class="badge bg-{{ $intervention->priorityColor }}">
                                    {{ $intervention->priority }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ ucfirst(str_replace('_', ' ', $intervention->status)) }}
                                </span>
                            </td>
                            <td>{{ $intervention->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($intervention->trashed())
                                    <form method="POST"
                                          action="{{ route('interventions.restore', $intervention->id) }}"
                                          class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            Restore
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('interventions.show', $intervention) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No intervention records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $interventions->links() }}
    </div>
</div>
@endsection