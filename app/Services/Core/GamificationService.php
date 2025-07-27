<?php

namespace App\Services\Core;

use App\Models\Badge;
use App\Models\Game;
use App\Models\Rank;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    /**
     * Cache TTL in seconds (5 minutes).
     *
     * @var int
     */
    protected $cacheTtl = 300;

    /**
     * Check and update a user's rank based on their total score.
     *
     * @param User $user
     * @return Rank|null
     */
    public function updateUserRank(User $user): ?Rank
    {
        // Calculate the user's total score across all games
        $totalScore = DB::table('user_game_stats')
            ->where('user_id', $user->id)
            ->sum('total_score');
            
        // Find the highest rank the user qualifies for
        $rank = Rank::where('threshold', '<=', $totalScore)
            ->orderBy('threshold', 'desc')
            ->first();
            
        if (!$rank) {
            return null;
        }
        
        // Check if the user already has this rank
        $currentRank = $user->currentRank();
        
        if (!$currentRank || $currentRank->id !== $rank->id) {
            // Assign the new rank to the user
            $user->ranks()->attach($rank->id);
            
            // Clear cache
            $this->clearUserRankCache($user->id);
        }
        
        return $rank;
    }
    
    /**
     * Check if a user qualifies for any badges and award them.
     *
     * @param User $user
     * @return array
     */
    public function checkAndAwardBadges(User $user): array
    {
        $awardedBadges = [];
        
        // Get all badges the user doesn't have yet
        $badges = Badge::whereNotIn('id', function ($query) use ($user) {
            $query->select('badge_id')
                ->from('user_badges')
                ->where('user_id', $user->id);
        })->get();
        
        foreach ($badges as $badge) {
            if ($this->userQualifiesForBadge($user, $badge)) {
                $this->awardBadge($user, $badge);
                $awardedBadges[] = $badge;
            }
        }
        
        return $awardedBadges;
    }
    
    /**
     * Check if a user qualifies for a specific badge.
     *
     * @param User $user
     * @param Badge $badge
     * @return bool
     */
    protected function userQualifiesForBadge(User $user, Badge $badge): bool
    {
        $criteria = $badge->criteria;
        
        // Check different types of criteria
        if (isset($criteria['streak'])) {
            return $this->checkStreakCriteria($user, $criteria['streak']);
        }
        
        if (isset($criteria['score'])) {
            return $this->checkScoreCriteria($user, $criteria['score']);
        }
        
        if (isset($criteria['plays'])) {
            return $this->checkPlaysCriteria($user, $criteria['plays']);
        }
        
        return false;
    }
    
    /**
     * Check if a user meets streak criteria.
     *
     * @param User $user
     * @param array $criteria
     * @return bool
     */
    protected function checkStreakCriteria(User $user, array $criteria): bool
    {
        $gameId = $criteria['game_id'] ?? null;
        $minStreak = $criteria['min_streak'] ?? 0;
        
        $query = DB::table('streaks')
            ->where('user_id', $user->id);
            
        if ($gameId) {
            $query->where('game_id', $gameId);
        }
        
        if ($criteria['type'] === 'current') {
            return $query->where('current_streak', '>=', $minStreak)->exists();
        } else {
            return $query->where('longest_streak', '>=', $minStreak)->exists();
        }
    }
    
    /**
     * Check if a user meets score criteria.
     *
     * @param User $user
     * @param array $criteria
     * @return bool
     */
    protected function checkScoreCriteria(User $user, array $criteria): bool
    {
        $gameId = $criteria['game_id'] ?? null;
        $minScore = $criteria['min_score'] ?? 0;
        
        $query = DB::table('user_game_stats')
            ->where('user_id', $user->id);
            
        if ($gameId) {
            $query->where('game_id', $gameId);
        }
        
        if ($criteria['type'] === 'total') {
            return $query->sum('total_score') >= $minScore;
        } else {
            // Single game high score
            return $query->where('total_score', '>=', $minScore)->exists();
        }
    }
    
    /**
     * Check if a user meets plays criteria.
     *
     * @param User $user
     * @param array $criteria
     * @return bool
     */
    protected function checkPlaysCriteria(User $user, array $criteria): bool
    {
        $gameId = $criteria['game_id'] ?? null;
        $minPlays = $criteria['min_plays'] ?? 0;
        
        $query = DB::table('user_game_stats')
            ->where('user_id', $user->id);
            
        if ($gameId) {
            $query->where('game_id', $gameId);
        }
        
        return $query->sum('plays_count') >= $minPlays;
    }
    
    /**
     * Award a badge to a user.
     *
     * @param User $user
     * @param Badge $badge
     * @return void
     */
    public function awardBadge(User $user, Badge $badge): void
    {
        $user->badges()->attach($badge->id, [
            'awarded_at' => Carbon::now(),
        ]);
        
        // Clear cache
        $this->clearUserBadgesCache($user->id);
    }
    
    /**
     * Get a user's current rank.
     *
     * @param User $user
     * @return array|null
     */
    public function getUserRank(User $user): ?array
    {
        $cacheKey = "user.{$user->id}.rank";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user) {
            $rank = $user->currentRank();
            
            if (!$rank) {
                return null;
            }
            
            return [
                'id' => $rank->id,
                'name' => $rank->name,
                'icon' => $rank->icon,
            ];
        });
    }
    
    /**
     * Get a user's badges.
     *
     * @param User $user
     * @return array
     */
    public function getUserBadges(User $user): array
    {
        $cacheKey = "user.{$user->id}.badges";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user) {
            return $user->badges()
                ->orderBy('awarded_at', 'desc')
                ->get()
                ->map(function ($badge) {
                    return [
                        'id' => $badge->id,
                        'name' => $badge->name,
                        'description' => $badge->description,
                        'icon' => $badge->icon,
                        'awarded_at' => $badge->pivot->awarded_at,
                    ];
                })
                ->toArray();
        });
    }
    
    /**
     * Clear the user rank cache.
     *
     * @param int $userId
     * @return void
     */
    protected function clearUserRankCache(int $userId): void
    {
        Cache::forget("user.{$userId}.rank");
    }
    
    /**
     * Clear the user badges cache.
     *
     * @param int $userId
     * @return void
     */
    protected function clearUserBadgesCache(int $userId): void
    {
        Cache::forget("user.{$userId}.badges");
    }
}