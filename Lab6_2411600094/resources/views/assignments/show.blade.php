@extends('layouts.master')

@section('title', 'Transaction #' . $transaction->id)

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold mb-0"><i class="bi bi-receipt me-2"></i>Transaction #{{ $transaction->id }}</h2>
        <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Transactions
        </a>
    </div>

    <div class="row">
        {{-- Transaction details --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-hover h-100">
                <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Transaction Details</h5></div>
                <div class="card-body">
                    <div class="border-accent ps-3 py-2 mb-2">
                        <small class="text-muted d-block">Type</small>
                        <span class="badge {{ $transaction->typeBadgeClass() }}">{{ $transaction->type_label }}</span>
                    </div>
                    <div class="border-primary-custom ps-3 py-2 mb-2">
                        <small class="text-muted d-block">Quantity</small>
                        <strong class="{{ $transaction->quantity > 0 ? 'text-danger' : 'text-success' }}">{{ $transaction->quantity_label }}</strong>
                    </div>
                    <div class="border-accent ps-3 py-2 mb-2">
                        <small class="text-muted d-block">Reference Number</small>
                        <strong>{{ $transaction->reference_number ?? '—' }}</strong>
                    </div>
                    @if($transaction->reason)
                        <div class="border-accent ps-3 py-2 mb-2">
                            <small class="text-muted d-block">Reason</small>
                            <strong>{{ $transaction->reason }}</strong>
                        </div>
                    @endif
                    <div class="border-success-custom ps-3 py-2 mb-2">
                        <small class="text-muted d-block">Notes</small>
                        <span>{{ $transaction->notes ?: 'No notes.' }}</span>
                    </div>
                    <div class="ps-3 py-2">
                        <small class="text-muted d-block">Recorded</small>
                        <span>{{ $transaction->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            {{-- Student --}}
            <div class="card shadow-hover mb-4">
                <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Student</h5></div>
                <div class="card-body">
                    @if($transaction->student)
                        <p class="mb-1"><strong>{{ $transaction->student->name }}</strong>
                            <span class="text-muted">({{ $transaction->student->student_id }})</span></p>
                        <p class="mb-1">{{ $transaction->student->program }}</p>
                        <p class="mb-2">
                            Pending now: <strong>{{ $transaction->student->pending_assignments }}</strong>
                            <span class="badge bg-{{ $transaction->student->backlog_color }} {{ $transaction->student->backlog_color === 'warning' ? 'text-dark' : '' }}">
                                {{ $transaction->student->backlog_status }}
                            </span>
                        </p>
                        <a href="{{ route('students.show', $transaction->student) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-person-badge me-1"></i> View Student
                        </a>
                    @else
                        <p class="text-muted mb-0">Student no longer exists.</p>
                    @endif
                </div>
            </div>

            {{-- Recorded by --}}
            <div class="card shadow-hover mb-4">
                <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Recorded By</h5></div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $transaction->user->name ?? 'Unknown user' }}</strong></p>
                    <p class="mb-0 text-muted">{{ $transaction->user->email ?? '' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-4">
        <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">Back to List</a>
        @if(Auth::user()->is_admin)
            <form action="{{ route('assignments.destroy', $transaction) }}" method="POST"
                  onsubmit="return confirm('Delete this transaction? The student\'s pending count will be reversed.');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger"><i class="bi bi-trash me-1"></i> Delete</button>
            </form>
        @endif
    </div>

@endsection