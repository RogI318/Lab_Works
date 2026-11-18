@extends('layouts.app')

@section('title', 'User Management')

@section('content')

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">User Management</h2>
            <p class="text-muted mb-0">Manage accounts and roles (admins only)</p>
        </div>

        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i>
            Add User
        </a>
    </div>

    {{-- Shows the message when a delete is blocked --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr class="border-accent text-uppercase small">
                            <th>#</th>
                            <th>Name</th>
                            <th>Email Address</th>
                            <th>Role</th>
                            <th>Date Registered</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $users->firstItem() + $loop->index }}</td>
                                <td class="fw-bold">
                                    {{ $user->name }}
                                    @if ($user->id === auth()->id())
                                        <span class="badge bg-primary ms-1">You</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $user->email }}
                                    @if ($user->hasPendingChanges())
                                        <span class="badge bg-warning text-dark" title="Waiting for the user to confirm an email or password change">Pending confirmation</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $user->isAdmin() ? 'bg-primary' : 'bg-secondary' }}">{{ ucfirst($user->role) }}</span>
                                </td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    {{-- No Delete button on your own row --}}
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline"
                                            onsubmit="return confirm('Delete this user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No users yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $users->links('pagination::bootstrap-5') }}
    </div>

</div>

@endsection
