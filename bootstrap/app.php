<?php

use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use App\Http\Middleware\EnsureTenantContext;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            ResolveTenant::class,
        ]);

        $middleware->api(append: [
            ResolveTenant::class,
        ]);

        $middleware->alias([
            'tenant' => EnsureTenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
