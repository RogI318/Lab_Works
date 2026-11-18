@extends('layouts.master')
@section('title', 'Audit Log')
@section('content')
    <div class="pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Audit Log</h2>
        <p class="text-muted mb-0">Recent system activity</p>
    </div>
    <div class="card shadow-hover">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody id="auditLogRows">
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                            <td>{{ $log->user_name ?? 'System' }}</td>
                            <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                            <td>{{ $log->auditable_type ?? '—' }}</td>
                            <td>{{ $log->label }}@if($log->auditable_type === 'InventoryTransaction' && $log->new_values)<small
                                class="d-block text-muted">Type: {{ $log->new_values['type'] ?? '—' }} · Qty:
                                {{ $log->new_values['quantity'] ?? '—' }} · Stock:
                                {{ $log->new_values['quantity_before'] ?? '?' }} →
                            {{ $log->new_values['quantity_after'] ?? '?' }}</small>@endif</td>
                            <td>{{ $log->ip_address }}</td>
                    </tr>@empty<tr>
                        <td colspan="6" class="text-center text-muted py-4">No audit entries.</td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $logs->links() }}</div>
    </div>
@endsection
@push('scripts')
    <script>
        (() => {
            const body = document.getElementById('auditLogRows'); if (!body) return;
            const esc = v => { const e = document.createElement('span'); e.textContent = v ?? ''; return e.innerHTML; };
            async function refresh() { try { const r = await fetch(@json(route('audit-logs.latest')), { headers: { Accept: 'application/json' } }); if (!r.ok) return; const rows = await r.json(); body.innerHTML = rows.length ? rows.map(x => `<tr><td>${esc(x.at)}</td><td>${esc(x.user)}</td><td><span class="badge bg-secondary">${esc(x.action)}</span></td><td>${esc(x.type)}</td><td>${esc(x.label)}${x.type === 'InventoryTransaction' && x.values ? `<small class="d-block text-muted">Type: ${esc(x.values.type)} · Qty: ${esc(x.values.quantity)} · Stock: ${esc(x.values.quantity_before ?? '?')} → ${esc(x.values.quantity_after ?? '?')}</small>` : ''}</td><td>${esc(x.ip)}</td></tr>`).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">No audit entries.</td></tr>'; } catch (e) { } }
            setInterval(refresh, 5000);
        })();
    </script>
@endpush