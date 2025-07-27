<?php

namespace App\Services\Core;

use App\Models\Game;
use App\Models\User;
use App\Repositories\GameRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardService
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
     * DashboardService constructor.
     *
     * @param GameRepository $gameRepository
     */
    public function __construct(GameRepository $gameRepository)
    {
        $this->gameRepository = $gameRepository;
    }
    
    /**
     * Get dashboard data for the authenticated user.
     *
     * @return array
     * @throws \Exception
     */
    public function getDashboardData(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            Log::warning('DashboardService: Attempted to get dashboard data for unauthenticated user');
            throw new \Exception('User must be authenticated to access dashboard data');
        }
        
        $games = $this->gameRepository->getAllActive();
        
        $gamesData = [];
        
        foreach ($games as $game) {
            $gamesData[] = $this->getGameData($game, $user);
        }
        
        return [
            'games' => $gamesData,
            'user' => $this->getUserData($user),
        ];
    }
    
    /**
     * Get data for a specific game.
     *
     * @param Game $game
     * @param User|null $user
     * @return array
     */
    protected function getGameData(Game $game, ?User $user): array
    {
        $data = [
            'id' => $game->id,
            'slug' => $game->slug,
            'name' => $game->name,
            'description' => $game->description,
        ];
        
        if ($user) {
            // Get user-specific data for this game with caching
            $data['user_stats'] = $this->getUserGameStats($user, $game);
        }
        
        return $data;
    }
    
    /**
     * Get user stats for a specific game with caching.
     *
     * @param User $user
     * @param Game $game
     * @return array
     */
    protected function getUserGameStats(User $user, Game $game): array
    {
        $cacheKey = $this->getUserGameStatsCacheKey($user->id, $game->id);
        
        return Cache::remember($cacheKey, self::USER_STATS_CACHE_TTL, function () use ($user, $game) {
            Log::info("Cache miss for user game stats: user_id={$user->id}, game_id={$game->id}");
            
            $stats = $user->gameStats()->where('game_id', $game->id)->first();
            $streak = $user->streaks()->where('game_id', $game->id)->first();
            
            return [
                'total_score' => $stats ? $stats->total_score : 0,
                'plays_count' => $stats ? $stats->plays_count : 0,
                'last_played_at' => $stats ? $stats->last_played_at : null,
                'current_streak' => $streak ? $streak->current_streak : 0,
                'longest_streak' => $streak ? $streak->longest_streak : 0,
            ];
        });
    }
    
    /**
     * Generate a cache key for user game stats.
     *
     * @param int $userId
     * @param int $gameId
     * @return string
     */
    protected function getUserGameStatsCacheKey(int $userId, int $gameId): string
    {
        return self::USER_STATS_CACHE_PREFIX . ".user.{$userId}.game.{$gameId}";
    }
    
    /**
     * Clear the user game stats cache.
     *
     * @param int $userId
     * @param int $gameId
     * @return void
     */
    public function clearUserGameStatsCache(int $userId, int $gameId): void
    {
        $cacheKey = $this->getUserGameStatsCacheKey($userId, $gameId);
        Cache::forget($cacheKey);
        Log::info("Cleared user game stats cache: user_id={$userId}, game_id={$gameId}");
    }
    
    /**
     * Get data for the user.
     *
     * @param User|null $user
     * @return array
     */
    protected function getUserData(?User $user): array
    {
        if (!$user) {
            return [
                'is_guest' => true,
            ];
        }
        
        return [
            'is_guest' => false,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'has_social_login' => $user->hasSocialLogin(),
            'rank' => $this->getUserRank($user),
            'badges' => $this->getUserBadges($user),
        ];
    }
    
    /**
     * Get the user's rank.
     *
     * @param User $user
     * @return array|null
     */
    protected function getUserRank(User $user): ?array
    {
        $rank = $user->currentRank();
        
        if (!$rank) {
            return null;
        }
        
        return [
            'name' => $rank->name,
            'icon' => $rank->icon,
        ];
    }
    
    /**
     * Get the user's badges.
     *
     * @param User $user
     * @return array
     */
    protected function getUserBadges(User $user): array
    {
        $badges = $user->badges()->get();
        
        $badgesData = [];
        
        foreach ($badges as $badge) {
            $badgesData[] = [
                'name' => $badge->name,
                'description' => $badge->description,
                'icon' => $badge->icon,
                'awarded_at' => $badge->pivot->awarded_at,
            ];
        }
        
        return $badgesData;
    }
}