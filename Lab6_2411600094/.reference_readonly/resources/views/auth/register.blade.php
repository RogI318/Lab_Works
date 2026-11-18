<x-guest-layout subtitle="Create your account">

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="name" name="name"
                       value="{{ old('name') }}" placeholder="Enter your name"
                       required autofocus autocomplete="name">
            </div>
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email"
                       value="{{ old('email') }}" placeholder="you@gmail.com"
                       required autocomplete="username">
            </div>
            <div class="form-text">We will send a verification link to this address.</div>
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Enter password" required autocomplete="new-password">
            </div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" id="password_confirmation"
                       name="password_confirmation" placeholder="Repeat password"
                       required autocomplete="new-password">
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">Register</button>

        <div class="text-center mt-3">
            <small class="text-muted">
                Already registered? <a href="{{ route('login') }}">Login</a>
            </small>
        </div>
    </form>

</x-guest-layout>
