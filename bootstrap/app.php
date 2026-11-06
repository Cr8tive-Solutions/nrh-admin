<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\AdminCan;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => AdminAuth::class,
            'admin.can' => AdminCan::class,
        ]);
        $middleware->append(SecurityHeaders::class);

        // Trusted proxy ranges come from config('trustedproxy.proxies')
        // (env TRUSTED_PROXIES), defaulting to the private ranges the Caddy
        // reverse proxy reaches this container from. Trusting '*' let any
        // client that can hit the container's published port spoof
        // X-Forwarded-For (defeating login throttling) and X-Forwarded-Host.
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
