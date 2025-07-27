<?php

namespace App\Http\Middleware;

use App\Services\Monitoring\PerformanceMonitoringService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DatabaseQueryMonitoringMiddleware
{
    public function __construct(
        protected PerformanceMonitoringService $performanceMonitoringService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Enable query logging
        DB::enableQueryLog();
        
        $response = $next($request);
        
        // Get executed queries
        $queries = DB::getQueryLog();
        
        // Track each query
        foreach ($queries as $query) {
            $this->performanceMonitoringService->trackDatabaseQuery(
                $query['query'],
                $query['time'],
                $query['bindings']
            );
        }
        
        // Disable query logging to prevent memory issues
        DB::disableQueryLog();
        
        return $response;
    }
}