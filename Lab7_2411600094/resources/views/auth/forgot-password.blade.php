<x-guest-layout>
    <x-slot name="title">Forgot Password</x-slot>
    <x-slot name="subtitle">Reset your password</x-slot>

    <p class="text-muted small mb-3">
        Forgot your password? No problem. Enter your email address and we will email you a
        password reset link that lets you choose a new one.
    </p>

    @if (session('status'))
        <div class="alert alert-success small">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="Enter email" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">Email Password Reset Link</button>
    </form>

    <div class="text-center mt-3">
        <small><a href="{{ route('login') }}">Back to login</a></small>
    </div>
</x-guest-layout>