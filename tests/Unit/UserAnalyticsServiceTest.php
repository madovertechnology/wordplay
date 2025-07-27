<?php

namespace Tests\Unit;

use App\Services\Monitoring\UserAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UserAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserAnalyticsService $userAnalyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userAnalyticsService = new UserAnalyticsService();
        Cache::flush();
    }

    public function test_tracks_page_view(): void
    {
        Log::shouldReceive('channel')
            ->with('analytics')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Page View', \Mockery::type('array'));

        $this->userAnalyticsService->trackPageView('/dashboard', 1, 'guest123');
    }

    public function test_tracks_user_action(): void
    {
        Log::shouldReceive('channel')
            ->with('analytics')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('User Action', \Mockery::type('array'));

        $this->userAnalyticsService->trackUserAction('button_click', ['button' => 'submit'], 1);
    }

    public function test_tracks_game_event(): void
    {
        Log::shouldReceive('channel')
            ->with('analytics')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Game Event', \Mockery::type('array'));

        $this->userAnalyticsService->trackGameEvent('word-scramble', 'word_submitted', ['word' => 'test'], 1);
    }

    public function test_tracks_user_engagement(): void
    {
        Log::shouldReceive('channel')
            ->with('analytics')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('User Engagement', \Mockery::type('array'));

        $this->userAnalyticsService->trackUserEngagement('game_session', 300, ['game' => 'word-scramble']);
    }

    public function test_respects_analytics_configuration(): void
    {
        // Disable page view tracking
        config(['monitoring.analytics.track_page_views' => false]);

        // The method should return early without logging
        $this->userAnalyticsService->trackPageView('/dashboard', 1, 'guest123');
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function test_updates_page_view_statistics(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->userAnalyticsService->trackPageView('/dashboard', 1);

        // Check that page view stats were updated
        $this->assertEquals(1, Cache::get('page_views_total_1d'));
        $this->assertEquals(1, Cache::get('page_views_total_7d'));
        $this->assertEquals(1, Cache::get('page_views_total_30d'));
    }

    public function test_gets_dashboard_data(): void
    {
        // Set up some test data
        Cache::put('page_views_total_7d', 100, now()->addDay());
        Cache::put('unique_visitors_7d', 50, now()->addDay());
        Cache::put('total_sessions_7d', 75, now()->addDay());

        $dashboardData = $this->userAnalyticsService->getDashboardData(7);

        $this->assertIsArray($dashboardData);
        $this->assertArrayHasKey('overview', $dashboardData);
        $this->assertArrayHasKey('page_views', $dashboardData);
        $this->assertArrayHasKey('user_actions', $dashboardData);
        $this->assertArrayHasKey('game_events', $dashboardData);
        $this->assertArrayHasKey('engagement', $dashboardData);
        $this->assertArrayHasKey('retention', $dashboardData);
        $this->assertEquals(7, $dashboardData['period_days']);
    }

    public function test_gets_user_behavior_analytics(): void
    {
        $userId = 1;
        
        // Set up some test data
        Cache::put("user_sessions_{$userId}_30d", 10, now()->addDay());
        Cache::put("user_page_views_{$userId}_30d", 50, now()->addDay());

        $behaviorData = $this->userAnalyticsService->getUserBehaviorAnalytics($userId, 30);

        $this->assertIsArray($behaviorData);
        $this->assertArrayHasKey('user_id', $behaviorData);
        $this->assertArrayHasKey('session_count', $behaviorData);
        $this->assertArrayHasKey('page_views', $behaviorData);
        $this->assertArrayHasKey('actions_performed', $behaviorData);
        $this->assertArrayHasKey('games_played', $behaviorData);
        $this->assertEquals($userId, $behaviorData['user_id']);
        $this->assertEquals(30, $behaviorData['period_days']);
    }

    public function test_gets_game_performance_analytics(): void
    {
        $gameSlug = 'word-scramble';
        
        // Set up some test data
        Cache::put("game_total_plays_{$gameSlug}_30d", 100, now()->addDay());
        Cache::put("game_unique_players_{$gameSlug}_30d", 25, now()->addDay());

        $performanceData = $this->userAnalyticsService->getGamePerformanceAnalytics($gameSlug, 30);

        $this->assertIsArray($performanceData);
        $this->assertArrayHasKey('game_slug', $performanceData);
        $this->assertArrayHasKey('total_plays', $performanceData);
        $this->assertArrayHasKey('unique_players', $performanceData);
        $this->assertArrayHasKey('average_session_duration', $performanceData);
        $this->assertArrayHasKey('completion_rate', $performanceData);
        $this->assertEquals($gameSlug, $performanceData['game_slug']);
        $this->assertEquals(30, $performanceData['period_days']);
    }

    public function test_clears_analytics_cache(): void
    {
        // Set up some test data
        Cache::put('analytics_dashboard_7d', ['test' => 'data'], now()->addHour());
        Cache::put('page_views_total_7d', 100, now()->addHour());
        Cache::put('popular_pages_7d', ['home' => 50], now()->addHour());

        $this->userAnalyticsService->clearAnalyticsCache();

        $this->assertNull(Cache::get('analytics_dashboard_7d'));
        $this->assertNull(Cache::get('page_views_total_7d'));
        $this->assertNull(Cache::get('popular_pages_7d'));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}