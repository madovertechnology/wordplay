<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
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
        $this->app->bind(\App\Repositories\GameRepository::class, function ($app) {
            return new \App\Repositories\GameRepository(new \App\Models\Game());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
