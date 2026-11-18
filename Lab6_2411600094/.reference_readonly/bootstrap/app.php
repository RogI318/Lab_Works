<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // LAB 6: every opened page is written to the audit log
        $middleware->appendToGroup('web', \App\Http\Middleware\AuditPageViews::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
