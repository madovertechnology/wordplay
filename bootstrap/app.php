<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware
        $middleware->append([
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\SessionSizeLimitMiddleware::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // Web middleware group
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // API middleware group
        $middleware->alias([
            'auth.rate.limit' => \App\Http\Middleware\RateLimitAuth::class,
            'api.rate.limit' => \App\Http\Middleware\RateLimitApi::class,
            'game.rate.limit' => \App\Http\Middleware\RateLimitGameEndpoints::class,
            'authorize.user.data' => \App\Http\Middleware\AuthorizeUserData::class,
            'api.security' => \App\Http\Middleware\ApiSecurityMiddleware::class,
            'error.tracking' => \App\Http\Middleware\ErrorTrackingMiddleware::class,
            'db.monitoring' => \App\Http\Middleware\DatabaseQueryMonitoringMiddleware::class,
            'analytics.tracking' => \App\Http\Middleware\AnalyticsTrackingMiddleware::class,
        ]);

        // Add monitoring middleware to web middleware
        $middleware->web(append: [
            \App\Http\Middleware\ErrorTrackingMiddleware::class,
            \App\Http\Middleware\DatabaseQueryMonitoringMiddleware::class,
            \App\Http\Middleware\AnalyticsTrackingMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
