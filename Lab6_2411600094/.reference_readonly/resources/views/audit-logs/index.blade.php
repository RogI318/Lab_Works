@extends('layouts.app')

@section('title', 'Audit Log')

@section('content')

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Audit Log</h2>
            <p class="text-muted mb-0">Who did what, and when. Entries cannot be edited or deleted.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-hover mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('audit-logs.index') }}" class="row g-2 align-items-end">

                <div class="col-md-3">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, email or user">
                </div>

                <div class="col-md-2">
                    <label class="form-label small">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($actions as $a)
                            <option value="{{ $a }}" @selected(request('action') === $a)>{{ ucfirst(str_replace('_', ' ', $a)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small">Record type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($types as $t)
                            <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small">User</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label small">From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
                </div>

                <div class="col-md-1">
                    <label class="form-label small">To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>

            </form>
        </div>
    </div>

    <!-- Log table -->
    <div class="card shadow-hover">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Record</th>
                            <th>Details</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->created_at?->timezone(config('app.timezone'))->format('M d, Y h:i:s A') }}</td>
                                <td>{{ $log->user_name ?? 'System / guest' }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $log->action_color }}">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</span>
                                </td>
                                <td>
                                    {{ $log->auditable_type ?? '-' }}
                                    @if ($log->auditable_id) <span class="text-muted">#{{ $log->auditable_id }}</span> @endif
                                    @if ($log->label)
                                        <div class="small text-muted">{{ $log->label }}</div>
                                    @endif
                                </td>
                                <td style="min-width: 240px;">
                                    @if ($log->old_values || $log->new_values)
                                        <details>
                                            <summary class="small">
                                                @if ($log->action === 'updated')
                                                    {{ count($log->new_values ?? []) }} field(s) changed
                                                @else
                                                    Show values
                                                @endif
                                            </summary>
                                            <table class="table table-sm mb-0 mt-1">
                                                @if ($log->action === 'updated')
                                                    <thead><tr><th>Field</th><th>Old</th><th>New</th></tr></thead>
                                                    @foreach (($log->new_values ?? []) as $field => $new)
                                                        <tr>
                                                            <td>{{ $field }}</td>
                                                            <td class="text-danger">{{ $log->old_values[$field] ?? '-' }}</td>
                                                            <td class="text-success">{{ $new ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    @foreach (($log->new_values ?? $log->old_values ?? []) as $field => $value)
                                                        <tr>
                                                            <td>{{ $field }}</td>
                                                            <td>{{ $value ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            </table>
                                        </details>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="small text-muted text-nowrap">{{ $log->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No audit entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $logs->links('pagination::bootstrap-5') }}
    </div>

</div>

@endsection
