<?php

namespace Tests\Unit;

use App\Services\Monitoring\ErrorTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Exception;

class ErrorTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ErrorTrackingService $errorTrackingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorTrackingService = new ErrorTrackingService();
        Cache::flush();
    }

    public function test_tracks_error_with_context(): void
    {
        Log::shouldReceive('channel')
            ->with('error_tracking')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('error')
            ->once()
            ->with('Tracked Error', \Mockery::type('array'));

        $exception = new Exception('Test error');
        $context = ['test_key' => 'test_value'];

        $this->errorTrackingService->trackError($exception, $context);
    }

    public function test_tracks_custom_error(): void
    {
        Log::shouldReceive('channel')
            ->with('error_tracking')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('error')
            ->once()
            ->with('Custom Error', \Mockery::type('array'));

        $this->errorTrackingService->trackCustomError('Custom error message', ['key' => 'value']);
    }

    public function test_updates_error_statistics(): void
    {
        $exception = new Exception('Test error');
        
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('error')->once();

        $this->errorTrackingService->trackError($exception);

        // Check that error count was incremented
        $this->assertEquals(1, Cache::get('error_count_1'));
        $this->assertEquals(1, Cache::get('error_count_24'));
        $this->assertEquals(1, Cache::get('error_count_168'));
    }

    public function test_gets_error_statistics(): void
    {
        // Set up some test data
        Cache::put('error_count_24', 5, now()->addHour());
        Cache::put('critical_error_count_24', 2, now()->addHour());
        Cache::put('error_rate_24', 1.5, now()->addHour());

        $stats = $this->errorTrackingService->getErrorStats(24);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_errors', $stats);
        $this->assertArrayHasKey('critical_errors', $stats);
        $this->assertArrayHasKey('error_rate', $stats);
        $this->assertArrayHasKey('period_hours', $stats);
        $this->assertEquals(24, $stats['period_hours']);
    }

    public function test_clears_error_statistics(): void
    {
        // Set up some test data
        Cache::put('error_count_1', 5, now()->addHour());
        Cache::put('error_count_24', 10, now()->addHour());
        Cache::put('top_errors_1', ['test' => 'data'], now()->addHour());

        $this->errorTrackingService->clearErrorStats();

        $this->assertNull(Cache::get('error_count_1'));
        $this->assertNull(Cache::get('error_count_24'));
        $this->assertNull(Cache::get('top_errors_1'));
    }

    public function test_tracks_error_patterns(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('error')->times(5);
        Log::shouldReceive('critical')->once();

        // Set a low threshold for testing
        config(['monitoring.error_threshold' => 3]);

        $exception = new Exception('Repeated error');

        // Track the same error multiple times to trigger pattern detection
        for ($i = 0; $i < 5; $i++) {
            $this->errorTrackingService->trackError($exception);
        }
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}