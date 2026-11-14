
@extends('layouts.master')

@section('title', 'Intervention Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Intervention Details</h2>
        <p class="text-muted mb-0">{{ $intervention->reference_number }}</p>
    </div>
    <a href="{{ route('interventions.index') }}" class="btn btn-outline-secondary">
        Back to List
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        Student and Intervention Information
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <strong>Student:</strong>
                <p>{{ $intervention->student->name ?? 'Unavailable' }}</p>
            </div>
            <div class="col-md-6">
                <strong>Student ID:</strong>
                <p>{{ $intervention->student->student_id ?? '—' }}</p>
            </div>
            <div class="col-md-6">
                <strong>Intervention Type:</strong>
                <p>{{ $intervention->intervention_type }}</p>
            </div>
            <div class="col-md-6">
                <strong>Priority:</strong>
                <p>{{ $intervention->priority }}</p>
            </div>
            <div class="col-md-6">
                <strong>Status:</strong>
                <p>{{ ucfirst(str_replace('_', ' ', $intervention->status)) }}</p>
            </div>
            <div class="col-md-6">
                <strong>Pending Assignments:</strong>
                <p>{{ $intervention->pending_assignments }}</p>
            </div>
            <div class="col-md-6">
                <strong>Created By:</strong>
                <p>{{ $intervention->creator->name ?? '—' }}</p>
            </div>
            <div class="col-md-6">
                <strong>Approved By:</strong>
                <p>{{ $intervention->approver->name ?? 'Not yet approved' }}</p>
            </div>
            <div class="col-md-6">
                <strong>Scheduled Date:</strong>
                <p>{{ $intervention->scheduled_at?->format('M d, Y h:i A') ?? 'Not scheduled' }}</p>
            </div>
            <div class="col-md-6">
                <strong>Approved Date:</strong>
                <p>{{ $intervention->approved_at?->format('M d, Y h:i A') ?? '—' }}</p>
            </div>
            <div class="col-12">
                <strong>Description:</strong>
                <p>{{ $intervention->description ?? 'No description provided.' }}</p>
            </div>
            <div class="col-12">
                <strong>Action Plan:</strong>
                <p>{{ $intervention->action_plan ?? 'No action plan provided.' }}</p>
            </div>

            @if($intervention->rejection_reason)
                <div class="col-12">
                    <strong>Rejection Reason:</strong>
                    <p class="text-danger">{{ $intervention->rejection_reason }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2">
    @can('update', $intervention)
        <a href="{{ route('interventions.edit', $intervention) }}" class="btn btn-warning">
            Edit
        </a>
    @endcan

    @can('approve', $intervention)
        <form action="{{ route('interventions.approve', $intervention) }}" method="POST">
            @csrf
            <button class="btn btn-success" onclick="return confirm('Approve this intervention?')">
                Approve
            </button>
        </form>
    @endcan

    @can('reject', $intervention)
        <form action="{{ route('interventions.reject', $intervention) }}" method="POST"
              onsubmit="return confirm('Reject this intervention?')">
            @csrf
            <input type="hidden" name="rejection_reason" value="Rejected by administrator">
            <button class="btn btn-danger">Reject</button>
        </form>
    @endcan

    @can('complete', $intervention)
        <form action="{{ route('interventions.complete', $intervention) }}" method="POST">
            @csrf
            <button class="btn btn-primary" onclick="return confirm('Mark this intervention as completed?')">
                Mark as Completed
            </button>
        </form>
    @endcan

    @can('delete', $intervention)
        <form action="{{ route('interventions.destroy', $intervention) }}" method="POST"
              onsubmit="return confirm('Are you sure you want to delete this intervention?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger">Delete</button>
        </form>
    @endcan
</div>
@endsection
