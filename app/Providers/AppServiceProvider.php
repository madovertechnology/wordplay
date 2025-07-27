<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Configuration\Middleware;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register repositories
        $this->app->bind(\App\Repositories\RepositoryInterface::class, \App\Repositories\BaseRepository::class);
        $this->app->bind(\App\Repositories\GameRepository::class, \App\Repositories\GameRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure middleware
        $this->configureMiddleware();

        // Configure Vite
        \Illuminate\Support\Facades\Vite::prefetch(concurrency: 3);
    }

    /**
     * Configure application middleware
     */
    private function configureMiddleware(): void
    {
        $middleware = app(Middleware::class);

        // Global middleware
        $middleware->append([
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
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
    }
}
