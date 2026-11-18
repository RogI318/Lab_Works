<x-guest-layout>

    <h5 class="text-center mb-2">
        Forgot Password
    </h5>

    <p class="text-muted small text-center mb-4">
        Enter your account email and we will send you a link to choose a new password.
    </p>

    <!-- Session Status (shown after the link is sent) -->
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

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
                    value="{{ old('email') }}"
                    placeholder="Enter your email"
                    required
                    autofocus
                >

            </div>

            @error('email')
                <div class="text-danger small mt-1">
                    {{ $message }}
                </div>
            @enderror

        </div>

        <!-- Send Button -->
        <button
            type="submit"
            class="btn btn-primary w-100 py-2"
        >
            Email Password Reset Link
        </button>

    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="small">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Login
        </a>
    </div>

</x-guest-layout>
