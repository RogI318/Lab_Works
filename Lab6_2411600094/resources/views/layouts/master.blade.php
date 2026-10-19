<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Student Portal') | Student Portal</title>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>

<body>

    <!-- ===== Navbar ===== -->
    @php
        $unreadCount = Auth::user()->unreadNotifications()->count();
        $recentNotifications = Auth::user()->notifications()->latest()->take(5)->get();
    @endphp

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">Student Portal</a>

            {{-- Notification bell (outside the collapse so it is always visible on phones) --}}
            <div class="dropdown ms-auto me-2 me-lg-3">
                <a class="nav-link position-relative text-white px-2" href="#" id="bellDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell-fill fs-5"></i>
                    <span id="bellCount"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $unreadCount ? '' : 'd-none' }}">{{ $unreadCount }}</span>
                </a>

                <ul class="dropdown-menu dropdown-menu-end notif-menu shadow" aria-labelledby="bellDropdown">
                    <li class="notif-header d-flex justify-content-between align-items-center">
                        <strong>Notifications</strong>
                        @if($unreadCount)
                            <form method="POST" action="{{ route('notifications.readAll') }}">
                                @csrf
                                <button class="notif-mark-all" type="submit">Mark all as read</button>
                            </form>
                        @endif
                    </li>

                    @forelse($recentNotifications as $n)
                        <li>
                            <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                @csrf
                                <button type="submit"
                                    class="dropdown-item notif-item {{ $n->read_at ? '' : 'notif-unread' }}">
                                    <div class="d-flex gap-2">
                                        <i class="bi bi-exclamation-triangle-fill text-warning mt-1"></i>
                                        <div>
                                            <div class="notif-text">{{ $n->data['message'] ?? 'Notification' }}</div>
                                            <small class="notif-time">{{ $n->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </button>
                            </form>
                        </li>
                    @empty
                        <li class="px-3 py-4 text-center text-muted small">No notifications yet.</li>
                    @endforelse
                </ul>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse flex-grow-0" id="navbarNav">
                <ul class="navbar-nav align-items-lg-center">
                    {{-- User dropdown with logout --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Welcome, <span id="userName">{{ Auth::user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit">
                                        <i class="bi bi-box-arrow-right me-1"></i> Logout
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
                                href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}"
                                href="{{ route('students.index') }}">Students</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('courses.*') ? 'active' : '' }}"
                                href="{{ route('courses.index') }}">Courses</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('assignments.*') ? 'active' : '' }}"
                                href="{{ route('assignments.index') }}">Assignments</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                                href="{{ route('reports.index') }}">Reports</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                                href="{{ route('profile.edit') }}">Profile</a>
                        </li>
                        <li class="nav-item mt-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="nav-link text-danger border-0 bg-transparent w-100 text-start"
                                    type="submit">Logout</button>
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
    @php
        $flash = session('success') ? ['success', 'check-circle-fill', session('success')]
            : (session('error') ? ['danger', 'exclamation-triangle-fill', session('error')] : null);
    @endphp

    @if($flash)
        <div id="notificationToast" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div class="toast align-items-center text-white bg-{{ $flash[0] }} border-0" role="alert" id="flashToast">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-{{ $flash[1] }} me-2"></i>{{ $flash[2] }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    @if($flash)
        <script>
            new bootstrap.Toast(document.getElementById('flashToast'), { delay: 5000 }).show();
        </script>
    @endif

</body>

</html>