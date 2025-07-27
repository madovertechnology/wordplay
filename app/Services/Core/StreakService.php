<?php

namespace App\Services\Core;

use App\Models\Game;
use App\Models\Streak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class StreakService
{
    /**
     * Cache TTL in seconds (5 minutes).
     *
     * @var int
     */
    protected $cacheTtl = 300;

    /**
     * Update a user's streak for a game.
     *
     * @param Game $game
     * @param User $user
     * @param string|null $date
     * @return Streak
     */
    public function updateStreak(Game $game, User $user, ?string $date = null): Streak
    {
        $date = $date ?? now()->toDateString();
        $today = Carbon::parse($date);
        
        $streak = Streak::firstOrNew([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);
        
        // If this is the first time playing
        if (!$streak->last_played_date) {
            $streak->current_streak = 1;
            $streak->longest_streak = 1;
            $streak->last_played_date = $today;
        } else {
            $lastPlayed = Carbon::parse($streak->last_played_date);
            $daysSinceLastPlayed = $today->diffInDays($lastPlayed);
            
            // If played today already, don't update streak
            if ($daysSinceLastPlayed === 0 && $today->isSameDay($lastPlayed)) {
                // Just return the current streak
                return $streak;
            }
            
            // If played yesterday, increment streak
            if ($daysSinceLastPlayed === 1 || ($daysSinceLastPlayed === 0 && !$today->isSameDay($lastPlayed))) {
                $streak->current_streak++;
                
                // Update longest streak if current streak is longer
                if ($streak->current_streak > $streak->longest_streak) {
                    $streak->longest_streak = $streak->current_streak;
                }
            } 
            // If missed a day, reset streak
            else {
                $streak->current_streak = 1;
            }
            
            $streak->last_played_date = $today;
        }
        
        $streak->save();
        
        // Clear cache
        $this->clearStreakCache($user->id, $game->id);
        
        return $streak;
    }
    
    /**
     * Get a user's streak for a game.
     *
     * @param Game $game
     * @param User $user
     * @return array
     */
    public function getUserStreak(Game $game, User $user): array
    {
        $cacheKey = "streak.{$user->id}.{$game->id}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($game, $user) {
            $streak = Streak::where('user_id', $user->id)
                ->where('game_id', $game->id)
                ->first();
                
            if (!$streak) {
                return [
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'last_played_date' => null,
                    'will_break_tomorrow' => false,
                ];
            }
            
            $lastPlayed = Carbon::parse($streak->last_played_date);
            $today = Carbon::today();
            $willBreakTomorrow = $lastPlayed->diffInDays($today) >= 1;
            
            return [
                'current_streak' => $streak->current_streak,
                'longest_streak' => $streak->longest_streak,
                'last_played_date' => $streak->last_played_date->toDateString(),
                'will_break_tomorrow' => $willBreakTomorrow,
            ];
        });
    }
    
    /**
     * Check if a user has played a game today.
     *
     * @param Game $game
     * @param User $user
     * @return bool
     */
    public function hasPlayedToday(Game $game, User $user): bool
    {
        $streak = Streak::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->first();
            
        if (!$streak || !$streak->last_played_date) {
            return false;
        }
        
        return $streak->last_played_date->isToday();
    }
    
    /**
     * Get users with the longest streaks for a game.
     *
     * @param Game $game
     * @param int $limit
     * @return array
     */
    public function getTopStreaks(Game $game, int $limit = 10): array
    {
        $cacheKey = "streaks.top.{$game->id}.{$limit}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($game, $limit) {
            $streaks = Streak::where('game_id', $game->id)
                ->orderBy('current_streak', 'desc')
                ->limit($limit)
                ->with('user:id,name,avatar')
                ->get();
                
            return $streaks->map(function ($streak) {
                return [
                    'user_id' => $streak->user_id,
                    'name' => $streak->user->name,
                    'avatar' => $streak->user->avatar,
                    'current_streak' => $streak->current_streak,
                    'longest_streak' => $streak->longest_streak,
                ];
            })->toArray();
        });
    }
    
    /**
     * Clear the streak cache for a user and game.
     *
     * @param int $userId
     * @param int $gameId
     * @return void
     */
    protected function clearStreakCache(int $userId, int $gameId): void
    {
        // Clear streak-specific caches
        Cache::forget("streak.{$userId}.{$gameId}");
        Cache::forget("streaks.top.{$gameId}.10"); // Common limit
        Cache::forget("streaks.top.{$gameId}.100"); // Another common limit
        
        // Clear user stats caches that include streak information
        Cache::forget(BaseGameService::USER_STREAK_CACHE_PREFIX . ".user.{$userId}.game.{$gameId}");
        Cache::forget(DashboardService::USER_STATS_CACHE_PREFIX . ".user.{$userId}.game.{$gameId}");
        
        // Log the cache clearing
        \Illuminate\Support\Facades\Log::info("Cleared streak cache: user_id={$userId}, game_id={$gameId}");
    }
}