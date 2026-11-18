<x-guest-layout>

    <h5 class="text-center mb-2">
        Reset Password
    </h5>

    <p class="text-muted small text-center mb-4">
        Choose a new password for your account.
    </p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Field -->
        <div class="mb-3">

            <label for="email" class="form-label">
                Email
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-envelope"></i>
                </span>

                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    value="{{ old('email', $request->email) }}"
                    required
                    autofocus
                    autocomplete="username"
                >

            </div>

            @error('email')
                <div class="text-danger small mt-1">
                    {{ $message }}
                </div>
            @enderror

        </div>

        <!-- New Password -->
        <div class="mb-3">

            <label for="password" class="form-label">
                New Password
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-lock"></i>
                </span>

                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="At least 8 characters"
                    required
                    autocomplete="new-password"
                >

            </div>

            @error('password')
                <div class="text-danger small mt-1">
                    {{ $message }}
                </div>
            @enderror

        </div>

        <!-- Confirm Password -->
        <div class="mb-3">

            <label for="password_confirmation" class="form-label">
                Confirm Password
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-lock-fill"></i>
                </span>

                <input
                    type="password"
                    class="form-control"
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="Repeat the new password"
                    required
                    autocomplete="new-password"
                >

            </div>

            @error('password_confirmation')
                <div class="text-danger small mt-1">
                    {{ $message }}
                </div>
            @enderror

        </div>

        <!-- Reset Button -->
        <button
            type="submit"
            class="btn btn-primary w-100 py-2"
        >
            Reset Password
        </button>

    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="small">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Login
        </a>
    </div>

</x-guest-layout>
