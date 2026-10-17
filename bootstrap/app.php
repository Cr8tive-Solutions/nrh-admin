<?php
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
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.can'  => \App\Http\Middleware\AdminCan::class,
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Trust only private ranges: the Caddy reverse proxy reaches this
        // container over the Docker bridge (172.16/12). Trusting '*' let any
        // client that can hit the container's published port spoof
        // X-Forwarded-For (defeating login throttling) and X-Forwarded-Host.
        $middleware->trustProxies(
            at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
