<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure PostgreSQL-specific settings for production
        if (app()->environment('production')) {
            $this->configureProductionDatabase();
        }

        // Log slow queries in non-production environments
        if (!app()->environment('production')) {
            $this->logSlowQueries();
        }

        // Set up database connection event listeners
        $this->setupConnectionListeners();
    }

    /**
     * Configure database settings optimized for production PostgreSQL
     */
    private function configureProductionDatabase(): void
    {
        // Set PostgreSQL-specific configuration
        DB::statement("SET statement_timeout = '30s'");
        DB::statement("SET lock_timeout = '10s'");
        DB::statement("SET idle_in_transaction_session_timeout = '60s'");
        
        // Enable query plan caching
        DB::statement("SET plan_cache_mode = 'auto'");
        
        // Optimize for read-heavy workloads
        DB::statement("SET random_page_cost = 1.1");
        DB::statement("SET effective_cache_size = '256MB'");
    }

    /**
     * Log slow queries for performance monitoring
     */
    private function logSlowQueries(): void
    {
        DB::listen(function (QueryExecuted $query) {
            if ($query->time > 1000) { // Log queries taking more than 1 second
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'connection' => $query->connectionName,
                ]);
            }
        });
    }

    /**
     * Set up database connection event listeners
     */
    private function setupConnectionListeners(): void
    {
        // Monitor database connection health
        DB::listen(function (QueryExecuted $query) {
            // Track query metrics for monitoring
            if (app()->bound('metrics')) {
                app('metrics')->increment('database.queries.total');
                app('metrics')->histogram('database.queries.duration', $query->time);
            }
        });
    }
}
