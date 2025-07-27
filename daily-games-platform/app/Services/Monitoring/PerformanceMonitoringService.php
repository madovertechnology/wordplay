<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceMonitoringService
{
    /**
     * Track API response time
     */
    public function trackApiResponseTime(string $endpoint, float $responseTime, int $statusCode): void
    {
        $performanceData = [
            'endpoint' => $endpoint,
            'response_time_ms' => round($responseTime, 2),
            'status_code' => $statusCode,
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
        ];

        Log::channel('performance')->info('API Response Time', $performanceData);

        // Update performance statistics
        $this->updateResponseTimeStats($endpoint, $responseTime);

        // Check for slow responses
        $threshold = config('monitoring.performance.slow_request_threshold', 2000);
        if ($responseTime > $threshold) {
            $this->alertSlowResponse($endpoint, $responseTime, $threshold);
        }
    }

    /**
     * Track database query performance
     */
    public function trackDatabaseQuery(string $query, float $executionTime, array $bindings = []): void
    {
        $queryData = [
            'query' => $this->sanitizeQuery($query),
            'execution_time_ms' => round($executionTime, 2),
            'bindings_count' => count($bindings),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('performance')->info('Database Query', $queryData);

        // Check for slow queries
        $threshold = config('monitoring.performance.slow_query_threshold', 1000);
        if ($executionTime > $threshold) {
            $this->alertSlowQuery($query, $executionTime, $threshold);
        }

        // Update query statistics
        $this->updateQueryStats($executionTime);
    }

    /**
     * Track memory usage
     */
    public function trackMemoryUsage(string $context = 'general'): void
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // Convert to MB
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024; // Convert to MB

        $memoryData = [
            'context' => $context,
            'current_memory_mb' => round($memoryUsage, 2),
            'peak_memory_mb' => round($peakMemory, 2),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('performance')->info('Memory Usage', $memoryData);

        // Check for high memory usage
        $threshold = config('monitoring.performance.memory_threshold', 128);
        if ($peakMemory > $threshold) {
            $this->alertHighMemoryUsage($context, $peakMemory, $threshold);
        }
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(int $hours = 24): array
    {
        $cacheKey = "performance_stats_{$hours}h";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($hours) {
            return [
                'response_times' => $this->getResponseTimeStats($hours),
                'database_queries' => $this->getDatabaseQueryStats($hours),
                'memory_usage' => $this->getMemoryUsageStats($hours),
                'slow_requests' => $this->getSlowRequestsCount($hours),
                'slow_queries' => $this->getSlowQueriesCount($hours),
                'period_hours' => $hours,
                'last_updated' => now()->toISOString(),
            ];
        });
    }

    /**
     * Monitor system health
     */
    public function checkSystemHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'checks' => [],
            'timestamp' => now()->toISOString(),
        ];

        // Check database connectivity
        $health['checks']['database'] = $this->checkDatabaseHealth();
        
        // Check cache connectivity
        $health['checks']['cache'] = $this->checkCacheHealth();
        
        // Check disk space
        $health['checks']['disk_space'] = $this->checkDiskSpace();
        
        // Check memory usage
        $health['checks']['memory'] = $this->checkMemoryHealth();

        // Determine overall status
        foreach ($health['checks'] as $check) {
            if ($check['status'] !== 'healthy') {
                $health['status'] = 'unhealthy';
                break;
            }
        }

        // Log health check results
        Log::channel('performance')->info('System Health Check', $health);

        return $health;
    }

    /**
     * Update response time statistics
     */
    protected function updateResponseTimeStats(string $endpoint, float $responseTime): void
    {
        $periods = [1, 24, 168]; // 1 hour, 24 hours, 1 week
        
        foreach ($periods as $hours) {
            $expiration = now()->addHours($hours);
            
            // Update average response time
            $avgKey = "avg_response_time_{$hours}";
            $countKey = "response_count_{$hours}";
            
            $currentAvg = Cache::get($avgKey, 0);
            $currentCount = Cache::get($countKey, 0);
            
            $newCount = $currentCount + 1;
            $newAvg = (($currentAvg * $currentCount) + $responseTime) / $newCount;
            
            Cache::put($avgKey, $newAvg, $expiration);
            Cache::put($countKey, $newCount, $expiration);
            
            // Track endpoint-specific stats
            $endpointKey = "endpoint_stats_{$hours}_" . md5($endpoint);
            $endpointStats = Cache::get($endpointKey, ['count' => 0, 'total_time' => 0]);
            $endpointStats['count']++;
            $endpointStats['total_time'] += $responseTime;
            $endpointStats['avg_time'] = $endpointStats['total_time'] / $endpointStats['count'];
            
            Cache::put($endpointKey, $endpointStats, $expiration);
        }
    }

    /**
     * Update query statistics
     */
    protected function updateQueryStats(float $executionTime): void
    {
        $periods = [1, 24, 168];
        
        foreach ($periods as $hours) {
            $expiration = now()->addHours($hours);
            
            $avgKey = "avg_query_time_{$hours}";
            $countKey = "query_count_{$hours}";
            
            $currentAvg = Cache::get($avgKey, 0);
            $currentCount = Cache::get($countKey, 0);
            
            $newCount = $currentCount + 1;
            $newAvg = (($currentAvg * $currentCount) + $executionTime) / $newCount;
            
            Cache::put($avgKey, $newAvg, $expiration);
            Cache::put($countKey, $newCount, $expiration);
        }
    }

    /**
     * Alert for slow responses
     */
    protected function alertSlowResponse(string $endpoint, float $responseTime, float $threshold): void
    {
        Log::channel('performance')->warning('Slow API Response Detected', [
            'endpoint' => $endpoint,
            'response_time_ms' => $responseTime,
            'threshold_ms' => $threshold,
            'exceeded_by_ms' => round($responseTime - $threshold, 2),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Alert for slow queries
     */
    protected function alertSlowQuery(string $query, float $executionTime, float $threshold): void
    {
        Log::channel('performance')->warning('Slow Database Query Detected', [
            'query' => $this->sanitizeQuery($query),
            'execution_time_ms' => $executionTime,
            'threshold_ms' => $threshold,
            'exceeded_by_ms' => round($executionTime - $threshold, 2),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Alert for high memory usage
     */
    protected function alertHighMemoryUsage(string $context, float $memoryUsage, float $threshold): void
    {
        Log::channel('performance')->warning('High Memory Usage Detected', [
            'context' => $context,
            'memory_usage_mb' => $memoryUsage,
            'threshold_mb' => $threshold,
            'exceeded_by_mb' => round($memoryUsage - $threshold, 2),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Sanitize SQL query for logging
     */
    protected function sanitizeQuery(string $query): string
    {
        // Remove sensitive data patterns and limit length
        $sanitized = preg_replace('/\b\d{4}-\d{2}-\d{2}\b/', 'XXXX-XX-XX', $query);
        $sanitized = preg_replace('/\b\d{10,}\b/', 'XXXXXXXXXX', $sanitized);
        
        return strlen($sanitized) > 500 ? substr($sanitized, 0, 500) . '...' : $sanitized;
    }

    /**
     * Get response time statistics
     */
    protected function getResponseTimeStats(int $hours): array
    {
        return [
            'average_ms' => Cache::get("avg_response_time_{$hours}", 0),
            'total_requests' => Cache::get("response_count_{$hours}", 0),
        ];
    }

    /**
     * Get database query statistics
     */
    protected function getDatabaseQueryStats(int $hours): array
    {
        return [
            'average_ms' => Cache::get("avg_query_time_{$hours}", 0),
            'total_queries' => Cache::get("query_count_{$hours}", 0),
        ];
    }

    /**
     * Get memory usage statistics
     */
    protected function getMemoryUsageStats(int $hours): array
    {
        return [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];
    }

    /**
     * Get slow requests count
     */
    protected function getSlowRequestsCount(int $hours): int
    {
        return Cache::get("slow_requests_{$hours}", 0);
    }

    /**
     * Get slow queries count
     */
    protected function getSlowQueriesCount(int $hours): int
    {
        return Cache::get("slow_queries_{$hours}", 0);
    }

    /**
     * Check database health
     */
    protected function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $start) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Database connection failed',
            ];
        }
    }

    /**
     * Check cache health
     */
    protected function checkCacheHealth(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, now()->addMinute());
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache is working properly',
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache read/write test failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Cache connection failed',
            ];
        }
    }

    /**
     * Check disk space
     */
    protected function checkDiskSpace(): array
    {
        try {
            $storagePath = storage_path();
            $freeBytes = disk_free_space($storagePath);
            $totalBytes = disk_total_space($storagePath);
            
            $freeGB = round($freeBytes / 1024 / 1024 / 1024, 2);
            $totalGB = round($totalBytes / 1024 / 1024 / 1024, 2);
            $usedPercent = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 2);
            
            $status = $usedPercent > 90 ? 'unhealthy' : 'healthy';
            
            return [
                'status' => $status,
                'free_gb' => $freeGB,
                'total_gb' => $totalGB,
                'used_percent' => $usedPercent,
                'message' => "Disk usage: {$usedPercent}%",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Unable to check disk space',
            ];
        }
    }

    /**
     * Check memory health
     */
    protected function checkMemoryHealth(): array
    {
        $currentMB = round(memory_get_usage(true) / 1024 / 1024, 2);
        $peakMB = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $limitMB = ini_get('memory_limit');
        
        // Convert memory limit to MB if it's not -1 (unlimited)
        if ($limitMB !== '-1') {
            $limitMB = $this->convertToMB($limitMB);
            $usedPercent = round(($peakMB / $limitMB) * 100, 2);
            $status = $usedPercent > 80 ? 'unhealthy' : 'healthy';
        } else {
            $usedPercent = 0;
            $status = 'healthy';
            $limitMB = 'unlimited';
        }
        
        return [
            'status' => $status,
            'current_mb' => $currentMB,
            'peak_mb' => $peakMB,
            'limit_mb' => $limitMB,
            'used_percent' => $usedPercent,
            'message' => "Memory usage: {$usedPercent}%",
        ];
    }

    /**
     * Convert memory limit string to MB
     */
    protected function convertToMB(string $memoryLimit): float
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (float) substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024;
            case 'm':
                return $value;
            case 'k':
                return $value / 1024;
            default:
                return $value / 1024 / 1024; // Assume bytes
        }
    }
}