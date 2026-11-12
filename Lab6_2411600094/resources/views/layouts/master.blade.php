<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inventory') | Student Portal Supply Centre</title>

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
                Student Portal <span class="fw-normal opacity-75">| Supply Centre Inventory</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                @php
                    $navUser = Auth::user();
                    $unreadCount = $navUser->unreadNotifications()->count();
                    $recentAlerts = $navUser->notifications()
                        ->latest('created_at')->take(15)->get();
                @endphp
                <ul class="navbar-nav ms-auto align-items-start align-items-lg-center">

                    {{-- Lab 6 Part 5 Step 1: notification bell --}}
                    <li class="nav-item dropdown me-lg-2">
                        <a class="nav-link position-relative" href="#" role="button"
                           data-bs-toggle="dropdown" aria-label="Notifications">
                            <i class="bi bi-bell fs-5"></i>
                            <span id="notifBadge"
                                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $unreadCount > 0 ? '' : 'd-none' }}">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow" style="min-width: 340px;">
                            <h6 class="dropdown-header"><i class="bi bi-exclamation-triangle me-1"></i>Low stock alerts ({{ $unreadCount }} unread)</h6>
                            <div style="max-height: 320px; overflow-y: auto;">
                            @forelse ($recentAlerts as $alert)
                                @php
                                    $isOut    = (int) ($alert->data['quantity'] ?? 1) <= 0;
                                    $isUnread = is_null($alert->read_at);
                                    $accent   = $isOut ? '#dc3545' : '#ffc107';
                                    $tint     = $isOut ? '#f8d7da' : '#fff3cd';
                                @endphp
                                <a class="dropdown-item text-wrap {{ $isUnread ? 'fw-semibold' : 'text-muted' }}"
                                   href="{{ route('notifications.show', $alert->id) }}"
                                   style="border-left: 5px solid {{ $accent }}; {{ $isUnread ? 'background:'.$tint.';' : 'opacity:.7;' }}">
                                    <i class="bi {{ $isOut ? 'bi-x-octagon-fill text-danger' : 'bi-exclamation-triangle-fill text-warning' }} me-1"></i>
                                    {{ $alert->data['subject'] ?? 'Inventory alert' }}
                                    @if ($isUnread)<span class="badge bg-danger ms-1">New</span>@endif
                                    <small class="text-muted d-block">{{ $alert->created_at->diffForHumans() }}</small>
                                </a>
                            @empty
                                <p class="dropdown-item text-muted small mb-0">No alerts yet.</p>
                            @endforelse
                            </div>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('notifications.readAll') }}">
                                @csrf @method('PUT')
                                <button class="dropdown-item" type="submit">Mark all as read</button>
                            </form>
                        </div>
                    </li>

                    {{-- Lab 6 Part 5 Step 1: user dropdown --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>{{ $navUser->name }}
                            @if ($navUser->is_admin)
                                <span class="badge text-bg-light ms-1">Admin</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><span class="dropdown-header">{{ $navUser->email }}</span></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-person-gear me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
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
                            <a class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}"
                               href="{{ route('transactions.index') }}">
                                Transactions
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
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                               href="{{ route('reports.index') }}">
                                Reports
                            </a>
                        </li>
                        {{-- Lab 5 (latest): admins maintain accounts here, since registration is closed. --}}
                        @if ($navUser->is_admin)
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
    @foreach (['success' => 'bg-success', 'error' => 'bg-danger', 'warning' => 'bg-warning text-dark', 'info' => 'bg-info text-dark'] as $key => $style)
        @if (session($key))
            <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                <div class="toast align-items-center text-white border-0 {{ $style }}" role="alert"
                     id="flashToast-{{ $key }}">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-{{ $key === 'success' ? 'check' : ($key === 'info' ? 'info' : 'exclamation') }}-circle-fill me-2"></i>{{ session($key) }}
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