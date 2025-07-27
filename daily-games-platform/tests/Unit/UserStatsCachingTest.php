<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Streak;
use App\Models\User;
use App\Models\UserGameStats;
use App\Services\Core\BaseGameService;
use App\Services\Core\DashboardService;
use App\Services\Core\StreakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserStatsCachingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Game $game;
    protected DashboardService $dashboardService;
    protected StreakService $streakService;
    protected BaseGameService $baseGameService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
        
        // Create a test game
        $this->game = Game::factory()->create([
            'name' => 'Test Game',
            'slug' => 'test-game',
            'is_active' => true,
        ]);
        
        // Create the services
        $this->dashboardService = app(DashboardService::class);
        $this->streakService = app(StreakService::class);
        
        // We need to use a concrete implementation of BaseGameService
        // Since it's abstract, we'll create a simple implementation
        $this->baseGameService = new class(app(\App\Repositories\GameRepository::class)) extends BaseGameService {
            // Implement required abstract methods if any
        };
    }

    #[Test]
    public function it_caches_user_game_stats()
    {
        // Create user game stats
        $stats = UserGameStats::create([
            'user_id' => $this->user->id,
            'game_id' => $this->game->id,
            'total_score' => 100,
            'plays_count' => 5,
            'last_played_at' => now(),
        ]);
        
        // Clear any existing cache
        $cacheKey = BaseGameService::USER_STATS_CACHE_PREFIX . ".user.{$this->user->id}.game.{$this->game->id}";
        Cache::forget($cacheKey);
        
        // First call should cache the result
        $result1 = $this->baseGameService->getUserGameStats($this->user, $this->game);
        
        // Modify the stats directly in the database
        UserGameStats::where('user_id', $this->user->id)
            ->where('game_id', $this->game->id)
            ->update(['total_score' => 200]);
        
        // Second call should return cached result (not reflecting the DB update)
        $result2 = $this->baseGameService->getUserGameStats($this->user, $this->game);
        
        // Results should be identical despite DB change
        $this->assertEquals($result1, $result2);
        $this->assertEquals(100, $result2['total_score']);
        
        // Clear the cache
        $this->baseGameService->clearUserStatsCache($this->user->id, $this->game->id);
        
        // Third call should fetch fresh data
        $result3 = $this->baseGameService->getUserGameStats($this->user, $this->game);
        
        // Results should now reflect the DB change
        $this->assertNotEquals($result1, $result3);
        $this->assertEquals(200, $result3['total_score']);
    }

    #[Test]
    public function it_caches_user_game_streak()
    {
        // Create user streak
        $streak = Streak::create([
            'user_id' => $this->user->id,
            'game_id' => $this->game->id,
            'current_streak' => 5,
            'longest_streak' => 10,
            'last_played_date' => now(),
        ]);
        
        // Clear any existing cache
        $cacheKey = BaseGameService::USER_STREAK_CACHE_PREFIX . ".user.{$this->user->id}.game.{$this->game->id}";
        Cache::forget($cacheKey);
        
        // First call should cache the result
        $result1 = $this->baseGameService->getUserGameStreak($this->user, $this->game);
        
        // Modify the streak directly in the database
        Streak::where('user_id', $this->user->id)
            ->where('game_id', $this->game->id)
            ->update(['current_streak' => 7]);
        
        // Second call should return cached result (not reflecting the DB update)
        $result2 = $this->baseGameService->getUserGameStreak($this->user, $this->game);
        
        // Results should be identical despite DB change
        $this->assertEquals($result1, $result2);
        $this->assertEquals(5, $result2['current_streak']);
        
        // Clear the cache
        $this->baseGameService->clearUserStatsCache($this->user->id, $this->game->id);
        
        // Third call should fetch fresh data
        $result3 = $this->baseGameService->getUserGameStreak($this->user, $this->game);
        
        // Results should now reflect the DB change
        $this->assertNotEquals($result1, $result3);
        $this->assertEquals(7, $result3['current_streak']);
    }

    #[Test]
    public function it_caches_dashboard_user_game_stats()
    {
        // Create user game stats and streak
        UserGameStats::create([
            'user_id' => $this->user->id,
            'game_id' => $this->game->id,
            'total_score' => 100,
            'plays_count' => 5,
            'last_played_at' => now(),
        ]);
        
        Streak::create([
            'user_id' => $this->user->id,
            'game_id' => $this->game->id,
            'current_streak' => 5,
            'longest_streak' => 10,
            'last_played_date' => now(),
        ]);
        
        // Use reflection to access protected method
        $reflectionMethod = new \ReflectionMethod(DashboardService::class, 'getUserGameStats');
        $reflectionMethod->setAccessible(true);
        
        // Clear any existing cache
        $cacheKey = DashboardService::USER_STATS_CACHE_PREFIX . ".user.{$this->user->id}.game.{$this->game->id}";
        Cache::forget($cacheKey);
        
        // First call should cache the result
        $result1 = $reflectionMethod->invoke($this->dashboardService, $this->user, $this->game);
        
        // Modify the stats and streak directly in the database
        UserGameStats::where('user_id', $this->user->id)
            ->where('game_id', $this->game->id)
            ->update(['total_score' => 200]);
            
        Streak::where('user_id', $this->user->id)
            ->where('game_id', $this->game->id)
            ->update(['current_streak' => 7]);
        
        // Second call should return cached result (not reflecting the DB update)
        $result2 = $reflectionMethod->invoke($this->dashboardService, $this->user, $this->game);
        
        // Results should be identical despite DB change
        $this->assertEquals($result1, $result2);
        $this->assertEquals(100, $result2['total_score']);
        $this->assertEquals(5, $result2['current_streak']);
        
        // Use reflection to access protected method for clearing cache
        $reflectionMethod = new \ReflectionMethod(DashboardService::class, 'clearUserGameStatsCache');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->dashboardService, $this->user->id, $this->game->id);
        
        // Get the method again since reflection might have reset it
        $reflectionMethod = new \ReflectionMethod(DashboardService::class, 'getUserGameStats');
        $reflectionMethod->setAccessible(true);
        
        // Third call should fetch fresh data
        $result3 = $reflectionMethod->invoke($this->dashboardService, $this->user, $this->game);
        
        // Results should now reflect the DB change
        $this->assertNotEquals($result1, $result3);
        $this->assertEquals(200, $result3['total_score']);
        $this->assertEquals(7, $result3['current_streak']);
    }

    #[Test]
    public function it_invalidates_cache_when_streak_updates()
    {
        // Create user streak
        $streak = Streak::create([
            'user_id' => $this->user->id,
            'game_id' => $this->game->id,
            'current_streak' => 5,
            'longest_streak' => 10,
            'last_played_date' => now()->subDay(),
        ]);
        
        // Cache the streak data
        $baseGameServiceCacheKey = BaseGameService::USER_STREAK_CACHE_PREFIX . ".user.{$this->user->id}.game.{$this->game->id}";
        $dashboardCacheKey = DashboardService::USER_STATS_CACHE_PREFIX . ".user.{$this->user->id}.game.{$this->game->id}";
        
        // Get initial data to cache it
        $this->baseGameService->getUserGameStreak($this->user, $this->game);
        
        // Use reflection to access protected method for dashboard stats
        $reflectionMethod = new \ReflectionMethod(DashboardService::class, 'getUserGameStats');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->dashboardService, $this->user, $this->game);
        
        // Update the streak
        $this->streakService->updateStreak($this->game, $this->user);
        
        // Check that caches were invalidated
        $this->assertFalse(Cache::has($baseGameServiceCacheKey));
        $this->assertFalse(Cache::has($dashboardCacheKey));
    }
}