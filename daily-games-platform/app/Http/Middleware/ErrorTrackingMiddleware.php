<?php

namespace App\Http\Middleware;

use App\Services\Monitoring\ErrorTrackingService;
use App\Services\Monitoring\PerformanceMonitoringService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ErrorTrackingMiddleware
{
    public function __construct(
        protected ErrorTrackingService $errorTrackingService,
        protected PerformanceMonitoringService $performanceMonitoringService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $response = $next($request);
            
            // Track performance metrics
            $this->trackPerformanceMetrics($request, $response, $startTime, $startMemory);
            
            return $response;
        } catch (Throwable $exception) {
            // Track the error
            $this->errorTrackingService->trackError($exception, [
                'middleware' => 'ErrorTrackingMiddleware',
                'request_id' => $request->header('X-Request-ID', uniqid()),
            ]);
            
            throw $exception;
        }
    }

    /**
     * Track performance metrics for the request
     */
    protected function trackPerformanceMetrics(
        Request $request, 
        Response $response, 
        float $startTime, 
        int $startMemory
    ): void {
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        // Use the performance monitoring service
        $endpoint = $request->method() . ' ' . $request->path();
        $this->performanceMonitoringService->trackApiResponseTime(
            $endpoint,
            $executionTime,
            $response->getStatusCode()
        );
        
        // Track memory usage
        $this->performanceMonitoringService->trackMemoryUsage('request_' . $request->path());

        // Track 4xx and 5xx responses
        if ($response->getStatusCode() >= 400) {
            $errorLevel = $response->getStatusCode() >= 500 ? 'error' : 'warning';
            
            Log::channel('error_tracking')->{$errorLevel}('HTTP Error Response', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'execution_time_ms' => round($executionTime, 2),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'error_type' => 'http_error',
                'is_server_error' => $response->getStatusCode() >= 500,
                'is_client_error' => $response->getStatusCode() >= 400 && $response->getStatusCode() < 500,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }
}