<?php

namespace App\Services\Core;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaderboardService
{
    /**
     * Cache TTL in seconds (5 minutes).
     *
     * @var int
     */
    protected $cacheTtl = 300;

    /**
     * Cache key prefix for leaderboards.
     *
     * @var string
     */
    protected $cachePrefix = 'leaderboard';

    /**
     * Update or create a leaderboard entry for a user.
     *
     * @param Game $game
     * @param User $user
     * @param int $score
     * @param string|null $date
     * @return Leaderboard
     */
    public function updateScore(Game $game, User $user, int $score, ?string $date = null): Leaderboard
    {
        $date = $date ?? now()->toDateString();
        $today = Carbon::parse($date);
        $monthStart = $today->copy()->startOfMonth()->toDateString();

        // Update daily leaderboard
        $dailyLeaderboard = $this->updateLeaderboardEntry($game, $user, $score, 'daily', $date);

        // Update monthly leaderboard
        $this->updateLeaderboardEntry($game, $user, $score, 'monthly', $monthStart);

        // Update all-time leaderboard
        $this->updateLeaderboardEntry($game, $user, $score, 'all_time', null);

        // Clear cache for this game's leaderboards
        $this->clearLeaderboardCache($game->id);

        // Also clear user-specific cache
        $this->clearUserRankCache($game->id, $user->id);

        Log::info("Updated leaderboard score: game_id={$game->id}, user_id={$user->id}, score={$score}");

        return $dailyLeaderboard;
    }

    /**
     * Clear the user rank cache for a specific user and game.
     *
     * @param int $gameId
     * @param int $userId
     * @return void
     */
    protected function clearUserRankCache(int $gameId, int $userId): void
    {
        $periodTypes = ['daily', 'monthly', 'all_time'];
        $dates = [
            'daily' => now()->toDateString(),
            'monthly' => now()->format('Y-m'),
            'all_time' => 'all'
        ];

        foreach ($periodTypes as $periodType) {
            $periodIdentifier = $dates[$periodType];
            $cacheKey = "{$this->cachePrefix}.{$gameId}.{$periodType}.{$periodIdentifier}.user.{$userId}";
            Cache::forget($cacheKey);

            // Also clear yesterday's cache for daily ranks
            if ($periodType === 'daily') {
                $yesterday = now()->subDay()->toDateString();
                $cacheKey = "{$this->cachePrefix}.{$gameId}.{$periodType}.{$yesterday}.user.{$userId}";
                Cache::forget($cacheKey);
            }
        }

        Log::info("Cleared user rank cache: game_id={$gameId}, user_id={$userId}");
    }

    /**
     * Update or create a leaderboard entry.
     *
     * @param Game $game
     * @param User $user
     * @param int $score
     * @param string $periodType
     * @param string|null $periodDate
     * @return Leaderboard
     */
    protected function updateLeaderboardEntry(Game $game, User $user, int $score, string $periodType, ?string $periodDate): Leaderboard
    {
        return Leaderboard::updateOrCreate(
            [
                'game_id' => $game->id,
                'user_id' => $user->id,
                'period_type' => $periodType,
                'period_date' => $periodDate,
            ],
            [
                'score' => DB::raw("GREATEST(score, {$score})"),
            ]
        );
    }

    /**
     * Get the daily leaderboard for a game.
     *
     * @param Game $game
     * @param int $limit
     * @param string|null $date
     * @return array
     */
    public function getDailyLeaderboard(Game $game, int $limit = 10, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $cacheKey = $this->generateCacheKey($game->id, 'daily', $date, $limit);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($game, $limit, $date) {
            Log::info("Cache miss for daily leaderboard: game_id={$game->id}, date={$date}, limit={$limit}");
            return $this->getLeaderboard($game, 'daily', $date, $limit);
        });
    }

    /**
     * Get the monthly leaderboard for a game.
     *
     * @param Game $game
     * @param int $limit
     * @param string|null $yearMonth
     * @return array
     */
    public function getMonthlyLeaderboard(Game $game, int $limit = 10, ?string $yearMonth = null): array
    {
        $yearMonth = $yearMonth ?? now()->format('Y-m');
        $monthStart = Carbon::parse($yearMonth . '-01')->toDateString();
        $cacheKey = $this->generateCacheKey($game->id, 'monthly', $yearMonth, $limit);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($game, $limit, $monthStart, $yearMonth) {
            Log::info("Cache miss for monthly leaderboard: game_id={$game->id}, month={$yearMonth}, limit={$limit}");
            return $this->getLeaderboard($game, 'monthly', $monthStart, $limit);
        });
    }

    /**
     * Get the all-time leaderboard for a game.
     *
     * @param Game $game
     * @param int $limit
     * @return array
     */
    public function getAllTimeLeaderboard(Game $game, int $limit = 10): array
    {
        $cacheKey = $this->generateCacheKey($game->id, 'all_time', null, $limit);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($game, $limit) {
            Log::info("Cache miss for all-time leaderboard: game_id={$game->id}, limit={$limit}");
            return $this->getLeaderboard($game, 'all_time', null, $limit);
        });
    }

    /**
     * Generate a cache key for leaderboard data.
     *
     * @param int $gameId
     * @param string $periodType
     * @param string|null $periodIdentifier
     * @param int $limit
     * @return string
     */
    protected function generateCacheKey(int $gameId, string $periodType, ?string $periodIdentifier, int $limit): string
    {
        $key = "{$this->cachePrefix}.{$gameId}.{$periodType}";

        if ($periodIdentifier) {
            $key .= ".{$periodIdentifier}";
        }

        $key .= ".{$limit}";

        return $key;
    }

    /**
     * Get a leaderboard.
     *
     * @param Game $game
     * @param string $periodType
     * @param string|null $periodDate
     * @param int $limit
     * @return array
     */
    protected function getLeaderboard(Game $game, string $periodType, ?string $periodDate, int $limit): array
    {
        $query = Leaderboard::where('game_id', $game->id)
            ->where('period_type', $periodType);

        if ($periodDate) {
            $query->where('period_date', $periodDate);
        }

        $entries = $query->orderBy('score', 'desc')
            ->limit($limit)
            ->with('user:id,name,avatar')
            ->get();

        return $entries->map(function ($entry) {
            return [
                'user_id' => $entry->user_id,
                'name' => $entry->user->name,
                'avatar' => $entry->user->avatar,
                'score' => $entry->score,
            ];
        })->toArray();
    }

    /**
     * Get a user's rank on a leaderboard.
     *
     * @param Game $game
     * @param User $user
     * @param string $periodType
     * @param string|null $periodDate
     * @return array|null
     */
    public function getUserRank(Game $game, User $user, string $periodType, ?string $periodDate): ?array
    {
        $periodIdentifier = $periodDate ?? 'all';
        $cacheKey = "{$this->cachePrefix}.{$game->id}.{$periodType}.{$periodIdentifier}.user.{$user->id}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($game, $user, $periodType, $periodDate) {
            Log::info("Cache miss for user rank: game_id={$game->id}, user_id={$user->id}, period_type={$periodType}");

            $entry = Leaderboard::where('game_id', $game->id)
                ->where('user_id', $user->id)
                ->where('period_type', $periodType);

            if ($periodDate) {
                // Handle monthly periods by converting Y-m format to full date
                if ($periodType === 'monthly' && preg_match('/^\d{4}-\d{2}$/', $periodDate)) {
                    $periodDate = Carbon::parse($periodDate . '-01')->toDateString();
                }
                $entry->where('period_date', $periodDate);
            }

            $entry = $entry->first();

            if (!$entry) {
                return null;
            }

            $rank = Leaderboard::where('game_id', $game->id)
                ->where('period_type', $periodType);

            if ($periodDate) {
                // Handle monthly periods by converting Y-m format to full date
                if ($periodType === 'monthly' && preg_match('/^\d{4}-\d{2}$/', $periodDate)) {
                    $periodDate = Carbon::parse($periodDate . '-01')->toDateString();
                }
                $rank->where('period_date', $periodDate);
            }

            $rank = $rank->where('score', '>', $entry->score)
                ->count() + 1;

            return [
                'rank' => $rank,
                'score' => $entry->score,
            ];
        });
    }

    /**
     * Get a user's rank on a leaderboard with cache bypass.
     * Useful for testing or when fresh data is required.
     *
     * @param Game $game
     * @param User $user
     * @param string $periodType
     * @param string|null $periodDate
     * @return array|null
     */
    public function getUserRankFresh(Game $game, User $user, string $periodType, ?string $periodDate): ?array
    {
        $periodIdentifier = $periodDate ?? 'all';
        $cacheKey = "{$this->cachePrefix}.{$game->id}.{$periodType}.{$periodIdentifier}.user.{$user->id}";

        // Clear the cache for this specific key
        Cache::forget($cacheKey);

        // Get fresh data
        return $this->getUserRank($game, $user, $periodType, $periodDate);
    }

    /**
     * Clear the leaderboard cache for a game.
     *
     * @param int $gameId
     * @return void
     */
    protected function clearLeaderboardCache(int $gameId): void
    {
        // Using cache tags would be more efficient, but they're not supported by all cache drivers
        // So we'll use cache key patterns instead
        $cachePatterns = [
            "{$this->cachePrefix}.{$gameId}.daily.*",
            "{$this->cachePrefix}.{$gameId}.monthly.*",
            "{$this->cachePrefix}.{$gameId}.all_time.*",
        ];

        foreach ($cachePatterns as $pattern) {
            // Get all cache keys matching the pattern
            $keys = $this->getCacheKeysMatchingPattern($pattern);

            // Forget each key individually
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }

        Log::info("Cleared leaderboard cache for game ID: {$gameId}");
    }

    /**
     * Get all cache keys matching a pattern.
     *
     * Note: This is a simplified implementation and may not work with all cache drivers.
     * For production, consider using a cache driver that supports tags or implement
     * a more robust solution for your specific cache driver.
     *
     * @param string $pattern
     * @return array
     */
    protected function getCacheKeysMatchingPattern(string $pattern): array
    {
        // Convert the pattern to a regex pattern
        $regexPattern = str_replace('*', '.*', $pattern);

        // Get all cache keys (this is a simplified approach)
        // In a real-world scenario, you might need to implement this differently
        // based on your cache driver
        $keys = [];

        // For Redis, you could use the KEYS command (though it's not recommended for production)
        // For file cache, you could scan the cache directory
        // For now, we'll use a simplified approach with predefined patterns

        // Daily leaderboards (current day and previous day)
        if (strpos($pattern, 'daily') !== false) {
            $gameId = $this->extractGameIdFromPattern($pattern);
            $today = now()->toDateString();
            $yesterday = now()->subDay()->toDateString();

            $keys[] = "{$this->cachePrefix}.{$gameId}.daily.{$today}.10";
            $keys[] = "{$this->cachePrefix}.{$gameId}.daily.{$today}.100";
            $keys[] = "{$this->cachePrefix}.{$gameId}.daily.{$yesterday}.10";
            $keys[] = "{$this->cachePrefix}.{$gameId}.daily.{$yesterday}.100";
        }

        // Monthly leaderboards (current month)
        if (strpos($pattern, 'monthly') !== false) {
            $gameId = $this->extractGameIdFromPattern($pattern);
            $currentMonth = now()->format('Y-m');

            $keys[] = "{$this->cachePrefix}.{$gameId}.monthly.{$currentMonth}.10";
            $keys[] = "{$this->cachePrefix}.{$gameId}.monthly.{$currentMonth}.100";
        }

        // All-time leaderboards
        if (strpos($pattern, 'all_time') !== false) {
            $gameId = $this->extractGameIdFromPattern($pattern);

            $keys[] = "{$this->cachePrefix}.{$gameId}.all_time.10";
            $keys[] = "{$this->cachePrefix}.{$gameId}.all_time.100";
        }

        // User-specific cache keys
        // These would need to be handled differently in a real implementation
        // as we don't know all user IDs here

        return $keys;
    }

    /**
     * Extract game ID from a cache pattern.
     *
     * @param string $pattern
     * @return int|null
     */
    protected function extractGameIdFromPattern(string $pattern): ?int
    {
        // Pattern format: leaderboard.{gameId}.{periodType}.*
        $parts = explode('.', $pattern);
        return isset($parts[1]) ? (int) $parts[1] : null;
    }
}
