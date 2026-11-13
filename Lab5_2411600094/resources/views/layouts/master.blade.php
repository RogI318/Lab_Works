<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inventory') | RogieMart Hardware</title>

    <!-- Bootstrap 5 (same as Lab 4) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">

    <!-- Lab 4 color scheme -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body>

    <!-- ===== Navbar ===== -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">
                RogieMart Hardware <span class="fw-normal opacity-75">| Inventory</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-start align-items-lg-center">
                    <li class="nav-item mb-2 mb-lg-0 me-lg-3 text-start">
                        <span class="navbar-text text-white">
                            Welcome, <span id="userName">{{ Auth::user()->name }}</span>
                        </span>
                    </li>
                    <li class="nav-item text-start">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button class="btn btn-outline-light btn-sm" type="submit">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">

            <!-- ===== Sidebar ===== -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3" style="min-height: calc(100vh - 56px);">
                <div class="position-sticky" style="top: 0;">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                               href="{{ route('dashboard') }}">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"
                               href="{{ route('products.index') }}">
                                Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('brands.*') ? 'active' : '' }}"
                               href="{{ route('brands.index') }}">
                                Brands
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                               href="{{ route('categories.index') }}">
                                Categories
                            </a>
                        </li>
                        @if (Auth::user()->is_admin)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                                   href="{{ route('users.index') }}">
                                    Manage Users
                                </a>
                            </li>
                        @endif
                        <li class="nav-item mt-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="nav-link text-danger border-0 bg-transparent w-100 text-start" type="submit">
                                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- ===== Main Content ===== -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Toast notification (same idea as Lab 4) --}}
    @foreach (['success' => 'bg-success', 'error' => 'bg-danger', 'warning' => 'bg-warning text-dark'] as $key => $style)
        @if (session($key))
            <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                <div class="toast align-items-center text-white border-0 {{ $style }}" role="alert"
                     id="flashToast-{{ $key }}">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-{{ $key === 'success' ? 'check' : 'exclamation' }}-circle-fill me-2"></i>{{ session($key) }}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.toast').forEach(el => new bootstrap.Toast(el, { delay: 5000 }).show());
    </script>
    @stack('scripts')
</body>
</html>