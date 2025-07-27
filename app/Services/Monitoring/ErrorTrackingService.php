<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ErrorTrackingService
{
    /**
     * Track an error with context
     */
    public function trackError(Throwable $exception, array $context = []): void
    {
        $errorData = $this->buildErrorData($exception, $context);
        
        // Log the error
        Log::channel('error_tracking')->error('Tracked Error', $errorData);
        
        // Update error statistics
        $this->updateErrorStats($exception);
        
        // Check for error patterns
        $this->checkErrorPatterns($exception);
    }

    /**
     * Track a custom error event
     */
    public function trackCustomError(string $message, array $context = [], string $level = 'error'): void
    {
        $errorData = array_merge([
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
        ], $context);

        Log::channel('error_tracking')->{$level}('Custom Error', $errorData);
    }

    /**
     * Get error statistics
     */
    public function getErrorStats(int $hours = 24): array
    {
        $cacheKey = "error_stats_{$hours}h";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($hours) {
            // In a real implementation, you would query your log storage
            // For now, we'll return cached statistics
            return [
                'total_errors' => Cache::get('error_count_' . $hours, 0),
                'critical_errors' => Cache::get('critical_error_count_' . $hours, 0),
                'error_rate' => Cache::get('error_rate_' . $hours, 0.0),
                'top_errors' => Cache::get('top_errors_' . $hours, []),
                'period_hours' => $hours,
                'last_updated' => now()->toISOString(),
            ];
        });
    }

    /**
     * Build comprehensive error data
     */
    protected function buildErrorData(Throwable $exception, array $context = []): array
    {
        return array_merge([
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
            'previous' => $exception->getPrevious() ? [
                'class' => get_class($exception->getPrevious()),
                'message' => $exception->getPrevious()->getMessage(),
            ] : null,
            'request' => [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'headers' => $this->getSafeHeaders(),
            ],
            'user' => [
                'id' => auth()->id(),
                'email' => auth()->user()?->email,
            ],
            'environment' => [
                'app_env' => app()->environment(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
            'timestamp' => now()->toISOString(),
        ], $context);
    }

    /**
     * Get safe request headers (excluding sensitive data)
     */
    protected function getSafeHeaders(): array
    {
        $headers = request()->headers->all();
        
        // Remove sensitive headers
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];
        
        foreach ($sensitiveHeaders as $header) {
            unset($headers[$header]);
        }
        
        return $headers;
    }

    /**
     * Update error statistics in cache
     */
    protected function updateErrorStats(Throwable $exception): void
    {
        $periods = [1, 24, 168]; // 1 hour, 24 hours, 1 week
        
        foreach ($periods as $hours) {
            $expiration = now()->addHours($hours);
            
            // Increment total error count
            $errorCountKey = "error_count_{$hours}";
            $currentCount = Cache::get($errorCountKey, 0);
            Cache::put($errorCountKey, $currentCount + 1, $expiration);
            
            // Track error types
            $errorTypeKey = "error_type_{$hours}_" . md5(get_class($exception));
            $currentTypeCount = Cache::get($errorTypeKey, 0);
            Cache::put($errorTypeKey, $currentTypeCount + 1, $expiration);
            
            // Update top errors list
            $this->updateTopErrors($exception, $hours);
        }
    }

    /**
     * Update top errors list
     */
    protected function updateTopErrors(Throwable $exception, int $hours): void
    {
        $topErrorsKey = "top_errors_{$hours}";
        $topErrors = Cache::get($topErrorsKey, []);
        
        $errorKey = get_class($exception) . '|' . $exception->getMessage();
        
        if (!isset($topErrors[$errorKey])) {
            $topErrors[$errorKey] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'count' => 0,
                'first_seen' => now()->toISOString(),
            ];
        }
        
        $topErrors[$errorKey]['count']++;
        $topErrors[$errorKey]['last_seen'] = now()->toISOString();
        
        // Keep only top 10 errors
        arsort($topErrors);
        $topErrors = array_slice($topErrors, 0, 10, true);
        
        Cache::put($topErrorsKey, $topErrors, now()->addHours($hours));
    }

    /**
     * Check for error patterns that might indicate issues
     */
    protected function checkErrorPatterns(Throwable $exception): void
    {
        $errorClass = get_class($exception);
        $patternKey = "error_pattern_" . md5($errorClass);
        
        // Count occurrences in the last hour
        $currentCount = Cache::get($patternKey, 0);
        $count = $currentCount + 1;
        Cache::put($patternKey, $count, now()->addHour());
        
        // Alert if error occurs too frequently
        $threshold = config('monitoring.error_threshold', 10);
        
        if ($count >= $threshold) {
            $this->alertErrorPattern($errorClass, $count);
            
            // Reset counter to avoid spam
            Cache::forget($patternKey);
        }
    }

    /**
     * Alert about error patterns
     */
    protected function alertErrorPattern(string $errorClass, int $count): void
    {
        Log::channel('error_tracking')->critical('Error Pattern Detected', [
            'error_class' => $errorClass,
            'count' => $count,
            'period' => '1 hour',
            'threshold' => config('monitoring.error_threshold', 10),
            'timestamp' => now()->toISOString(),
        ]);
        
        // In production, you might want to send notifications here
        if (app()->environment('production')) {
            Log::channel('slack')->critical('🚨 Error Pattern Alert', [
                'message' => "Error {$errorClass} occurred {$count} times in the last hour",
                'environment' => app()->environment(),
            ]);
        }
    }

    /**
     * Clear error statistics
     */
    public function clearErrorStats(): void
    {
        $periods = [1, 24, 168];
        
        foreach ($periods as $hours) {
            Cache::forget("error_count_{$hours}");
            Cache::forget("critical_error_count_{$hours}");
            Cache::forget("error_rate_{$hours}");
            Cache::forget("top_errors_{$hours}");
        }
    }
}