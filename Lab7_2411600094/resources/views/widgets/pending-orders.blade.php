{{--
    Lab 7 Part 5 Step 2: the pending-approvals widget.

    Data is bound by ViewServiceProvider; $pending is one cached aggregate row,
    so this card costs no query of its own. id + data-widget are the hooks the
    30-second poller uses to repaint it; data-pending-* mark the two numbers it
    animates.
--}}
<div class="card shadow-hover h-100" id="widget-pending-orders" data-widget="pending-orders">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-hourglass-split me-1"></i>Pending Approvals</h5>
        <a href="{{ route('purchase-orders.index', ['status' => 'pending']) }}" class="btn btn-light btn-sm">
            Review
        </a>
    </div>
    <div class="card-body" data-widget-body>
        @if ($pending['count'] === 0)
            <div class="text-center text-muted py-4">
                <i class="bi bi-check2-circle d-block mb-2 fs-3"></i>
                Nothing is waiting for a signature.
            </div>
        @else
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div id="pendingCount" class="display-6 fw-bold text-primary lh-1">{{ $pending['count'] }}</div>
                    <div class="small text-muted">order(s) awaiting approval</div>
                </div>
                <div class="text-end">
                    <div id="pendingValue" class="h4 fw-bold text-success mb-0">{{ number_format($pending['value'], 2) }}</div>
                    <div class="small text-muted">PHP committed</div>
                </div>
            </div>
        @endif
    </div>
</div>
