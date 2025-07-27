<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class HealthController extends Controller
{
    /**
     * Basic health check endpoint
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => Carbon::now()->toISOString(),
            'environment' => app()->environment(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * Comprehensive health check with dependencies
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];

        $overallStatus = collect($checks)->every(fn($check) => $check['status'] === 'ok') ? 'ok' : 'error';

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => Carbon::now()->toISOString(),
            'environment' => app()->environment(),
            'version' => config('app.version', '1.0.0'),
            'checks' => $checks,
        ], $overallStatus === 'ok' ? 200 : 503);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            
            // Test a simple query
            $result = DB::select('SELECT 1 as test');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'response_time_ms' => $responseTime,
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'connection' => config('database.default'),
            ];
        }
    }

    /**
     * Check cache connectivity
     */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'ok',
                    'response_time_ms' => $responseTime,
                    'driver' => config('cache.default'),
                ];
            } else {
                return [
                    'status' => 'error',
                    'error' => 'Cache value mismatch',
                    'driver' => config('cache.default'),
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'driver' => config('cache.default'),
            ];
        }
    }

    /**
     * Check queue connectivity
     */
    private function checkQueue(): array
    {
        try {
            $start = microtime(true);
            $connection = Queue::connection();
            $size = $connection->size();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'response_time_ms' => $responseTime,
                'driver' => config('queue.default'),
                'pending_jobs' => $size,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'driver' => config('queue.default'),
            ];
        }
    }

    /**
     * Check storage accessibility
     */
    private function checkStorage(): array
    {
        try {
            $start = microtime(true);
            $disk = \Storage::disk();
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'health check test';
            
            $disk->put($testFile, $testContent);
            $retrieved = $disk->get($testFile);
            $disk->delete($testFile);
            
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $testContent) {
                return [
                    'status' => 'ok',
                    'response_time_ms' => $responseTime,
                    'driver' => config('filesystems.default'),
                ];
            } else {
                return [
                    'status' => 'error',
                    'error' => 'Storage content mismatch',
                    'driver' => config('filesystems.default'),
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'driver' => config('filesystems.default'),
            ];
        }
    }
}
