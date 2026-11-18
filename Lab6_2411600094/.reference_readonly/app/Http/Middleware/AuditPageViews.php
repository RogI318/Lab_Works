<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

// LAB 6: records every page a signed-in user opens in the audit log ("viewed").
// Background refreshes (API calls, the bell check) and downloads are not counted as page views.
class AuditPageViews
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->log($request, $response);
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function log(Request $request, Response $response): void
    {
        $user = $request->user();

        if (! $user || ! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return;
        }

        if ($response->getStatusCode() !== 200 || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return;
        }

        if ($request->is('api/*') || $request->is('notifications/latest') || $request->is('up')) {
            return;
        }

        $path = '/'.ltrim($request->path(), '/');
        $name = $request->route()?->getName();

        // A refresh of the same page within 5 seconds is one view, not many
        if (! Cache::add('audit-view:'.$user->id.':'.$request->fullUrl(), 1, 5)) {
            return;
        }

        AuditLog::record('viewed', null, null, null, $path.($name ? " ({$name})" : ''));
        // (record() has no model for a page, so mark the type for the filter)
    }
}
