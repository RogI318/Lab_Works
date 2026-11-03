@extends('layouts.master')

@section('title', 'Assignments')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Assignment Transactions</h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('assignments.adjust') }}" class="btn btn-outline-primary">
                <i class="bi bi-sliders me-1"></i> Adjust Count
            </a>
            <a href="{{ route('assignments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Record Transaction
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Filter Transactions</h5></div>
        <div class="card-body">
            <form method="GET" action="{{ route('assignments.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All Students</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ (string) request('student_id') === (string) $s->id ? 'selected' : '' }}>
                                {{ $s->name }} ({{ $s->student_id }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Transactions</h5>
            <span class="badge bg-light text-dark">{{ $transactions->total() }} records</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="rosterTable">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Reference</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $t)
                            <tr>
                                <td>{{ $t->created_at->format('M d, Y h:i A') }}</td>
                                <td>
                                    @if($t->student)
                                        <a href="{{ route('students.show', $t->student) }}">{{ $t->student->name }}</a>
                                    @else — @endif
                                </td>
                                <td><span class="badge {{ $t->typeBadgeClass() }}">{{ $t->type_label }}</span></td>
                                {{-- + means backlog went up (red), - means it went down (green) --}}
                                <td class="fw-bold {{ $t->quantity > 0 ? 'text-danger' : 'text-success' }}">{{ $t->quantity_label }}</td>
                                <td>{{ $t->reference_number ?? '—' }}</td>
                                <td>{{ $t->user->name ?? '—' }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('assignments.show', $t) }}" class="btn btn-sm btn-info text-white" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(Auth::user()->is_admin)
                                        <form action="{{ route('assignments.destroy', $t) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this transaction? The student\'s pending count will be reversed.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-inbox fs-1 d-block text-muted"></i>
                                    <p class="text-muted mb-0">No transactions found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

@endsection