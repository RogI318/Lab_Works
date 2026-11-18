@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the following:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $editing = isset($user);
    $isSelf = $editing && $user->id === auth()->id();
@endphp

@if ($editing && $user->hasPendingChanges())
    <div class="alert alert-warning">
        Waiting for the user to confirm:
        {{ collect([$user->pending_email ? 'new email '.$user->pending_email : null, $user->pending_password ? 'new password' : null])->filter()->implode(' and ') }}.
        Saving again sends a fresh confirmation email.
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" placeholder="e.g. name@example.com" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" {{ $isSelf ? 'disabled' : '' }} required>
            @foreach (['admin' => 'Admin', 'staff' => 'Staff'] as $value => $label)
                <option value="{{ $value }}" {{ old('role', $user->role ?? 'staff') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @if ($isSelf)
            {{-- A disabled select is not sent, so we send the role in a hidden field --}}
            <input type="hidden" name="role" value="{{ $user->role }}">
            <div class="form-text">You cannot change your own role.</div>
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" minlength="8" {{ $editing ? '' : 'required' }}>
        @if ($editing)
            <div class="form-text">Leave blank to keep the current password. Changing the email or password sends the user a confirmation email first.</div>
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" minlength="8" {{ $editing ? '' : 'required' }}>
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">Save User</button>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
