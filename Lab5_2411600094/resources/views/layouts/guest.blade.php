<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} | Student Portal Supply Centre</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body class="login-page">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card login-card shadow-lg" style="max-width: 440px; width: 100%;">
            <div class="card-header bg-primary text-white text-center py-4">
                <h2 class="mb-0 mt-2">Student Portal</h2>
                <p class="mb-0 small">{{ $subtitle ?? 'Supply Centre Inventory System' }}</p>
            </div>

            <div class="card-body p-4">
                {{ $slot }}
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>