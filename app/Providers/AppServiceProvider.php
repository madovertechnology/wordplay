<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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

        // Temporarily disable guest service
        // $this->app->singleton(\App\Services\User\GuestService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Vite
        \Illuminate\Support\Facades\Vite::prefetch(concurrency: 3);
    }
}
