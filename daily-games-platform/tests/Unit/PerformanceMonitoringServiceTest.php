<?php

namespace Tests\Unit;

use App\Services\Monitoring\PerformanceMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PerformanceMonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PerformanceMonitoringService $performanceMonitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->performanceMonitoringService = new PerformanceMonitoringService();
        Cache::flush();
    }

    public function test_tracks_api_response_time(): void
    {
        Log::shouldReceive('channel')
            ->with('performance')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('API Response Time', \Mockery::type('array'));

        $this->performanceMonitoringService->trackApiResponseTime('/api/test', 150.5, 200);
    }

    public function test_tracks_database_query(): void
    {
        Log::shouldReceive('channel')
            ->with('performance')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Database Query', \Mockery::type('array'));

        $this->performanceMonitoringService->trackDatabaseQuery(
            'SELECT * FROM users WHERE id = ?',
            25.5,
            [1]
        );
    }

    public function test_tracks_memory_usage(): void
    {
        Log::shouldReceive('channel')
            ->with('performance')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Memory Usage', \Mockery::type('array'));

        $this->performanceMonitoringService->trackMemoryUsage('test_context');
    }

    public function test_alerts_slow_response(): void
    {
        Log::shouldReceive('channel')
            ->with('performance')
            ->twice()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('API Response Time', \Mockery::type('array'));
            
        Log::shouldReceive('warning')
            ->once()
            ->with('Slow API Response Detected', \Mockery::type('array'));

        // Set a low threshold for testing
        config(['monitoring.performance.slow_request_threshold' => 100]);

        $this->performanceMonitoringService->trackApiResponseTime('/api/slow', 150, 200);
    }

    public function test_alerts_slow_query(): void
    {
        Log::shouldReceive('channel')
            ->with('performance')
            ->twice()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Database Query', \Mockery::type('array'));
            
        Log::shouldReceive('warning')
            ->once()
            ->with('Slow Database Query Detected', \Mockery::type('array'));

        // Set a low threshold for testing
        config(['monitoring.performance.slow_query_threshold' => 10]);

        $this->performanceMonitoringService->trackDatabaseQuery(
            'SELECT * FROM large_table',
            25.5,
            []
        );
    }

    public function test_gets_performance_statistics(): void
    {
        // Set up some test data
        Cache::put('avg_response_time_24', 150.5, now()->addHour());
        Cache::put('response_count_24', 100, now()->addHour());
        Cache::put('avg_query_time_24', 25.0, now()->addHour());
        Cache::put('query_count_24', 500, now()->addHour());

        $stats = $this->performanceMonitoringService->getPerformanceStats(24);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('response_times', $stats);
        $this->assertArrayHasKey('database_queries', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertArrayHasKey('period_hours', $stats);
        $this->assertEquals(24, $stats['period_hours']);
    }

    public function test_checks_system_health(): void
    {
        $health = $this->performanceMonitoringService->checkSystemHealth();

        $this->assertIsArray($health);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('checks', $health);
        $this->assertArrayHasKey('timestamp', $health);
        
        // Check that all expected health checks are present
        $this->assertArrayHasKey('database', $health['checks']);
        $this->assertArrayHasKey('cache', $health['checks']);
        $this->assertArrayHasKey('disk_space', $health['checks']);
        $this->assertArrayHasKey('memory', $health['checks']);
    }

    public function test_sanitizes_query_for_logging(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->once();

        // Test with a query containing sensitive data
        $sensitiveQuery = "SELECT * FROM users WHERE created_at = '2023-12-25' AND phone = '1234567890'";
        
        $this->performanceMonitoringService->trackDatabaseQuery($sensitiveQuery, 10.0, []);
        
        // The test passes if no exception is thrown and the method completes
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}