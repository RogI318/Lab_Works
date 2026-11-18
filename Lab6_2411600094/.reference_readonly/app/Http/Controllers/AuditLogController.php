<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

// LAB 6: Audit log page (admins only - the route has can:manage-users)
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('type'), fn ($q) => $q->where('auditable_type', $request->type))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(fn ($w) => $w->where('label', 'like', "%{$s}%")->orWhere('user_name', 'like', "%{$s}%"));
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $actions = ['viewed', 'created', 'updated', 'deleted', 'restored', 'exported', 'notification_read', 'login', 'logout', 'login_failed'];
        $types = AuditLog::whereNotNull('auditable_type')->distinct()->orderBy('auditable_type')->pluck('auditable_type');
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('audit-logs.index', compact('logs', 'actions', 'types', 'users'));
    }
}
