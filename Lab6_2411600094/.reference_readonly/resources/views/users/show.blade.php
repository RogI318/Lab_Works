@extends('layouts.app')

@section('title', $user->name)

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="mb-1">
            {{ $user->name }}
            @if ($user->id === auth()->id())
                <span class="badge bg-primary fs-6 align-middle">You</span>
            @endif
        </h2>
        <p class="text-muted mb-0">User details</p>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" value="{{ $user->name }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="text" class="form-control" value="{{ $user->email }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" value="{{ ucfirst($user->role) }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date Registered</label>
                    <input type="text" class="form-control" value="{{ $user->created_at->format('M d, Y') }}" readonly>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">Edit</a>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Back to Users</a>
            </div>
        </div>
    </div>

</div>

@endsection
