<?php
namespace App\Http\Middleware;
use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
class AuditPageViews
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        try {
            if ($request->user() && $request->isMethod('GET') && !$request->ajax() && !$request->expectsJson() && !$request->is('api/*') && !$request->is('notifications/latest') && $response->getStatusCode() === 200 && str_contains((string) $response->headers->get('Content-Type'), 'text/html') && Cache::add('audit-view:' . $request->user()->id . ':' . $request->fullUrl(), 1, 5))
                AuditLog::record('viewed', null, null, null, '/' . $request->path());
        } catch (\Throwable $e) {
            report($e);
        }
        return $response;
    }
}
