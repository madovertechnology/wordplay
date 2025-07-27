<?php

namespace App\Services\Core;

use App\Models\Game;
use App\Models\Streak;
use App\Models\User;
use App\Models\UserGameStats;
use App\Repositories\GameRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class BaseGameService implements GameServiceInterface
{
    /**
     * The game repository instance.
     *
     * @var GameRepository
     */
    protected $gameRepository;
    
    /**
     * Cache TTL for user stats in seconds (5 minutes)
     */
    const USER_STATS_CACHE_TTL = 300;
    
    /**
     * Cache key prefix for user stats
     */
    const USER_STATS_CACHE_PREFIX = 'user_stats';
    
    /**
     * Cache key prefix for user streaks
     */
    const USER_STREAK_CACHE_PREFIX = 'user_streak';
    
    /**
     * BaseGameService constructor.
     *
     * @param GameRepository $gameRepository
     */
    public function __construct(GameRepository $gameRepository)
    {
        $this->gameRepository = $gameRepository;
    }
    
    /**
     * Get all games.
     *
     * @return Collection
     */
    public function getAllGames(): Collection
    {
        return Cache::remember('games.all', 60 * 60, function () {
            return $this->gameRepository->all();
        });
    }
    
    /**
     * Get all active games.
     *
     * @return Collection
     */
    public function getActiveGames(): Collection
    {
        return Cache::remember('games.active', 60 * 60, function () {
            return $this->gameRepository->getAllActive();
        });
    }
    
    /**
     * Get a game by its slug.
     *
     * @param string $slug
     * @return Game|null
     */
    public function getGameBySlug(string $slug): ?Game
    {
        return Cache::remember("games.slug.{$slug}", 60 * 60, function () use ($slug) {
            return $this->gameRepository->findBySlug($slug);
        });
    }
    
    /**
     * Get a game by its ID.
     *
     * @param int $id
     * @return Game|null
     */
    public function getGameById(int $id): ?Game
    {
        return Cache::remember("games.id.{$id}", 60 * 60, function () use ($id) {
            return $this->gameRepository->find($id);
        });
    }
    
    /**
     * Toggle the active status of a game.
     *
     * @param int $id
     * @return Game
     */
    public function toggleGameActive(int $id): Game
    {
        $game = $this->gameRepository->toggleActive($id);
        
        // Clear cache
        Cache::forget('games.all');
        Cache::forget('games.active');
        Cache::forget("games.id.{$id}");
        Cache::forget("games.slug.{$game->slug}");
        
        return $game;
    }
    
    /**
     * Get user stats for a game.
     *
     * @param User $user
     * @param Game $game
     * @return array
     */
    public function getUserGameStats(User $user, Game $game): array
    {
        $cacheKey = $this->getUserStatsCacheKey($user->id, $game->id);
        
        return Cache::remember($cacheKey, self::USER_STATS_CACHE_TTL, function () use ($user, $game) {
            Log::info("Cache miss for user game stats: user_id={$user->id}, game_id={$game->id}");
            
            $stats = UserGameStats::where('user_id', $user->id)
                ->where('game_id', $game->id)
                ->first();
                
            if (!$stats) {
                return [
                    'total_score' => 0,
                    'plays_count' => 0,
                    'last_played_at' => null,
                ];
            }
            
            return [
                'total_score' => $stats->total_score,
                'plays_count' => $stats->plays_count,
                'last_played_at' => $stats->last_played_at,
            ];
        });
    }
    
    /**
     * Get user streak for a game.
     *
     * @param User $user
     * @param Game $game
     * @return array
     */
    public function getUserGameStreak(User $user, Game $game): array
    {
        $cacheKey = $this->getUserStreakCacheKey($user->id, $game->id);
        
        return Cache::remember($cacheKey, self::USER_STATS_CACHE_TTL, function () use ($user, $game) {
            Log::info("Cache miss for user game streak: user_id={$user->id}, game_id={$game->id}");
            
            $streak = Streak::where('user_id', $user->id)
                ->where('game_id', $game->id)
                ->first();
                
            if (!$streak) {
                return [
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'last_played_date' => null,
                ];
            }
            
            return [
                'current_streak' => $streak->current_streak,
                'longest_streak' => $streak->longest_streak,
                'last_played_date' => $streak->last_played_date,
            ];
        });
    }
    
    /**
     * Generate a cache key for user stats.
     *
     * @param int $userId
     * @param int $gameId
     * @return string
     */
    protected function getUserStatsCacheKey(int $userId, int $gameId): string
    {
        return self::USER_STATS_CACHE_PREFIX . ".user.{$userId}.game.{$gameId}";
    }
    
    /**
     * Generate a cache key for user streak.
     *
     * @param int $userId
     * @param int $gameId
     * @return string
     */
    protected function getUserStreakCacheKey(int $userId, int $gameId): string
    {
        return self::USER_STREAK_CACHE_PREFIX . ".user.{$userId}.game.{$gameId}";
    }
    
    /**
     * Clear the user stats cache.
     *
     * @param int $userId
     * @param int $gameId
     * @return void
     */
    public function clearUserStatsCache(int $userId, int $gameId): void
    {
        $statsCacheKey = $this->getUserStatsCacheKey($userId, $gameId);
        $streakCacheKey = $this->getUserStreakCacheKey($userId, $gameId);
        
        Cache::forget($statsCacheKey);
        Cache::forget($streakCacheKey);
        
        Log::info("Cleared user stats and streak cache: user_id={$userId}, game_id={$gameId}");
    }
    
    /**
     * Get daily leaderboard for a game.
     *
     * @param Game $game
     * @param int $limit
     * @return array
     */
    public function getDailyLeaderboard(Game $game, int $limit = 10): array
    {
        // This is a base implementation that should be overridden by specific game services
        return [];
    }
    
    /**
     * Get monthly leaderboard for a game.
     *
     * @param Game $game
     * @param int $limit
     * @return array
     */
    public function getMonthlyLeaderboard(Game $game, int $limit = 10): array
    {
        // This is a base implementation that should be overridden by specific game services
        return [];
    }
    
    /**
     * Get all-time leaderboard for a game.
     *
     * @param Game $game
     * @param int $limit
     * @return array
     */
    public function getAllTimeLeaderboard(Game $game, int $limit = 10): array
    {
        // This is a base implementation that should be overridden by specific game services
        return [];
    }
}