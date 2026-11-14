@extends('layouts.master')

@section('title', 'Manage Users')

@section('content')
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Manage Users</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#user-add">
            <i class="bi bi-person-plus me-1"></i> Add User
        </button>
    </div>

    <div class="card shadow-hover">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Users</h5>
            <span class="badge bg-light text-dark">{{ $users->total() }} users</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="text-muted">#{{ $user->id }}</td>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                    @if ($user->is(auth()->user()))
                                        <span class="badge bg-secondary ms-1">You</span>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    {{-- Lab 7 Part 1: the four roles replace Admin/User. --}}
                                    <span class="badge {{ $user->roleBadgeClass() }}">{{ $user->roleLabel() }}</span>
                                </td>
                                <td class="small text-muted">{{ $user->created_at?->format('M d, Y') }}</td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-info text-white" title="View"
                                            data-bs-toggle="modal" data-bs-target="#user-view-{{ $user->id }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" title="Edit"
                                            data-bs-toggle="modal" data-bs-target="#user-edit-{{ $user->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    {{-- An admin can only view and edit their own account; no delete button on their own row. --}}
                                    @unless ($user->is(auth()->user()))
                                        <button type="button" class="btn btn-sm btn-danger" title="Delete"
                                                data-bs-toggle="modal" data-bs-target="#user-delete-{{ $user->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

    {{-- ===== Add User modal ===== --}}
    @php $addOpened = old('modal_key') === 'user-add'; @endphp
    <div class="modal fade" id="user-add" tabindex="-1" aria-labelledby="user-addLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="user-addLabel"><i class="bi bi-person-plus me-1"></i> Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <input type="hidden" name="modal_key" value="user-add">

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="user-add-name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="user-add-name"
                                   class="form-control @if($addOpened) @error('name') is-invalid @enderror @endif"
                                   value="{{ $addOpened ? old('name') : '' }}" required>
                            @if($addOpened) @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>

                        <div class="mb-3">
                            <label for="user-add-email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="user-add-email"
                                   class="form-control @if($addOpened) @error('email') is-invalid @enderror @endif"
                                   value="{{ $addOpened ? old('email') : '' }}" required>
                            @if($addOpened) @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>

                        <div class="mb-3">
                            <label for="user-add-password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="user-add-password"
                                   class="form-control @if($addOpened) @error('password') is-invalid @enderror @endif"
                                   autocomplete="new-password" required>
                            @if($addOpened) @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>

                        <div class="mb-3">
                            <label for="user-add-password-confirm" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" id="user-add-password-confirm"
                                   class="form-control" autocomplete="new-password" required>
                        </div>

                        {{-- Lab 7 Part 1 Step 2: role replaces the admin tick-box. --}}
                        <div class="mb-3">
                            <label for="user-add-role" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <select name="role" id="user-add-role"
                                    class="form-select @if($addOpened) @error('role') is-invalid @enderror @endif">
                                @foreach ($roles as $value => $label)
                                    <option value="{{ $value }}"
                                            @selected(old('modal_key') === 'user-add' ? old('role', 'staff') === $value : $value === 'staff')>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @if($addOpened) @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                            <div class="form-text">
                                Administrator and Manager handle suppliers and purchase orders;
                                Warehouse Staff receives deliveries; Staff keeps the Lab 5/6 screens only.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($users as $user)
        @php
            $isSelf     = $user->is(auth()->user());
            $editKey    = "user-edit-{$user->id}";
            $editOpened = old('modal_key') === $editKey;
            $deleteKey  = "user-delete-{$user->id}";
            $viewKey    = "user-view-{$user->id}";
        @endphp

        {{-- ===== View User modal ===== --}}
        <div class="modal fade" id="{{ $viewKey }}" tabindex="-1" aria-labelledby="{{ $viewKey }}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="{{ $viewKey }}Label"><i class="bi bi-person-badge me-1"></i> User Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <div class="rounded-circle bg-gradient-primary text-white d-inline-flex align-items-center justify-content-center fw-bold"
                                 style="width: 64px; height: 64px; font-size: 1.6rem;">
                                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                            </div>
                            <h5 class="mt-2 mb-0">
                                {{ $user->name }}
                                @if ($isSelf)
                                    <span class="badge bg-secondary ms-1">You</span>
                                @endif
                            </h5>
                            <span class="badge {{ $user->roleBadgeClass() }}">{{ $user->roleLabel() }}</span>
                        </div>

                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th class="text-muted fw-semibold" style="width: 40%;">User ID</th>
                                    <td>#{{ $user->id }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-semibold">Name</th>
                                    <td>{{ $user->name }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-semibold">Email</th>
                                    <td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-semibold">Role</th>
                                    <td>{{ $user->roleLabel() }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-semibold">Email Verified</th>
                                    <td>{{ $user->email_verified_at ? $user->email_verified_at->format('M d, Y h:i A') : 'Not verified' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-semibold">Created</th>
                                    <td>{{ $user->created_at?->format('M d, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-semibold">Last Updated</th>
                                    <td>{{ $user->updated_at?->format('M d, Y h:i A') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Edit User modal ===== --}}
        <div class="modal fade" id="{{ $editKey }}" tabindex="-1" aria-labelledby="{{ $editKey }}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="{{ $editKey }}Label"><i class="bi bi-pencil-square me-1"></i> Edit User</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="modal_key" value="{{ $editKey }}">

                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="{{ $editKey }}-name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="{{ $editKey }}-name"
                                       class="form-control @if($editOpened) @error('name') is-invalid @enderror @endif"
                                       value="{{ $editOpened ? old('name') : $user->name }}" required>
                                @if($editOpened) @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                            </div>

                            <div class="mb-3">
                                <label for="{{ $editKey }}-email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="{{ $editKey }}-email"
                                       class="form-control @if($editOpened) @error('email') is-invalid @enderror @endif"
                                       value="{{ $editOpened ? old('email') : $user->email }}" required>
                                @if($editOpened) @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                            </div>

                            <div class="mb-3">
                                <label for="{{ $editKey }}-password" class="form-label fw-semibold">New Password</label>
                                <input type="password" name="password" id="{{ $editKey }}-password"
                                       class="form-control @if($editOpened) @error('password') is-invalid @enderror @endif"
                                       autocomplete="new-password" placeholder="Leave blank to keep the current password">
                                @if($editOpened) @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                            </div>

                            <div class="mb-3">
                                <label for="{{ $editKey }}-password-confirm" class="form-label fw-semibold">Confirm New Password</label>
                                <input type="password" name="password_confirmation" id="{{ $editKey }}-password-confirm"
                                       class="form-control" autocomplete="new-password">
                            </div>

                            {{-- Lab 7 Part 1 Step 2: role dropdown; own row is
                                 locked so an admin cannot demote themselves. --}}
                            <div class="mb-3">
                                <label for="{{ $editKey }}-role" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                                <select name="role" id="{{ $editKey }}-role"
                                        class="form-select @if($editOpened) @error('role') is-invalid @enderror @endif"
                                        @disabled($isSelf)>
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}"
                                                @selected($editOpened ? old('role') === $value : $user->role === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($editOpened) @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                                {{-- A disabled control posts nothing, so carry the real role. --}}
                                @if ($isSelf)
                                    <input type="hidden" name="role" value="{{ $user->role }}">
                                    <div class="form-text">You can't change your own role.</div>
                                @endif
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== Delete User modal ===== --}}
        <div class="modal fade" id="{{ $deleteKey }}" tabindex="-1" aria-labelledby="{{ $deleteKey }}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="{{ $deleteKey }}Label"><i class="bi bi-exclamation-triangle-fill me-1"></i> Delete User</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    @if ($isSelf)
                        <div class="modal-body">
                            <div class="alert alert-warning mb-0">You can't delete your own account while you are logged in.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    @else
                        <form method="POST" action="{{ route('users.destroy', $user) }}">
                            @csrf
                            @method('DELETE')
                            <div class="modal-body">
                                <p class="mb-0">Delete <strong>{{ $user->name }}</strong> ({{ $user->email }})? They will no longer be able to log in.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Yes, delete</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
@endsection

{{-- Reopen whichever modal was submitted when validation fails. --}}
@if ($errors->any() && old('modal_key'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById(@json(old('modal_key')));
                if (el) new bootstrap.Modal(el).show();
            });
        </script>
    @endpush
@endif