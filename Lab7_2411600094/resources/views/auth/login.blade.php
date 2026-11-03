<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body class="login-page">

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card login-card shadow-lg" style="max-width: 400px; width: 100%;">
            <div class="card-header bg-primary text-white text-center py-4">
                <h2 class="mb-0 mt-2">Student Portal</h2>
                <p class="mb-0 small">Login to your dashboard</p>
            </div>

            <div class="card-body p-4">

            
                @if (session('status'))
                    <div class="alert alert-success small">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}"
                               placeholder="Enter email" required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Enter password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Login button --}}
                    <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
                </form>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <a href="{{ route('password.request') }}">Forgot your password?</a>
                    </small>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">Demo: admin@example.com / password123</small>
                </div>

            </div>
        </div>
    </div>

</body>
</html>