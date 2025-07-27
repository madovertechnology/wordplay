<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class UserAnalyticsService
{
    /**
     * Track page view
     */
    public function trackPageView(string $page, ?int $userId = null, ?string $guestId = null): void
    {
        if (!config('monitoring.analytics.track_page_views', true)) {
            return;
        }

        $analyticsData = [
            'event_type' => 'page_view',
            'page' => $page,
            'user_id' => $userId ?? auth()->id(),
            'guest_id' => $guestId,
            'session_id' => session()->getId(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('analytics')->info('Page View', $analyticsData);

        // Update page view statistics
        $this->updatePageViewStats($page);
    }

    /**
     * Track user action
     */
    public function trackUserAction(string $action, array $context = [], ?int $userId = null): void
    {
        if (!config('monitoring.analytics.track_user_actions', true)) {
            return;
        }

        $analyticsData = array_merge([
            'event_type' => 'user_action',
            'action' => $action,
            'user_id' => $userId ?? auth()->id(),
            'session_id' => session()->getId(),
            'ip' => request()->ip(),
            'timestamp' => now()->toISOString(),
        ], $context);

        Log::channel('analytics')->info('User Action', $analyticsData);

        // Update action statistics
        $this->updateActionStats($action);
    }

    /**
     * Track game event
     */
    public function trackGameEvent(string $gameSlug, string $event, array $data = [], ?int $userId = null): void
    {
        if (!config('monitoring.analytics.track_game_events', true)) {
            return;
        }

        $analyticsData = array_merge([
            'event_type' => 'game_event',
            'game_slug' => $gameSlug,
            'event' => $event,
            'user_id' => $userId ?? auth()->id(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
        ], $data);

        Log::channel('analytics')->info('Game Event', $analyticsData);

        // Update game statistics
        $this->updateGameStats($gameSlug, $event);
    }

    /**
     * Track user engagement
     */
    public function trackUserEngagement(string $engagementType, int $duration = 0, array $context = []): void
    {
        $analyticsData = array_merge([
            'event_type' => 'user_engagement',
            'engagement_type' => $engagementType,
            'duration_seconds' => $duration,
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
        ], $context);

        Log::channel('analytics')->info('User Engagement', $analyticsData);

        // Update engagement statistics
        $this->updateEngagementStats($engagementType, $duration);
    }

    /**
     * Get analytics dashboard data
     */
    public function getDashboardData(int $days = 7): array
    {
        $cacheKey = "analytics_dashboard_{$days}d";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($days) {
            return [
                'overview' => $this->getOverviewStats($days),
                'page_views' => $this->getPageViewStats($days),
                'user_actions' => $this->getUserActionStats($days),
                'game_events' => $this->getGameEventStats($days),
                'engagement' => $this->getEngagementStats($days),
                'retention' => $this->getRetentionStats($days),
                'period_days' => $days,
                'last_updated' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get user behavior analytics
     */
    public function getUserBehaviorAnalytics(?int $userId = null, int $days = 30): array
    {
        $userId = $userId ?? auth()->id();
        $cacheKey = "user_behavior_{$userId}_{$days}d";
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($userId, $days) {
            return [
                'user_id' => $userId,
                'session_count' => $this->getUserSessionCount($userId, $days),
                'page_views' => $this->getUserPageViews($userId, $days),
                'actions_performed' => $this->getUserActions($userId, $days),
                'games_played' => $this->getUserGamesPlayed($userId, $days),
                'engagement_time' => $this->getUserEngagementTime($userId, $days),
                'last_activity' => $this->getUserLastActivity($userId),
                'period_days' => $days,
            ];
        });
    }

    /**
     * Get game performance analytics
     */
    public function getGamePerformanceAnalytics(string $gameSlug, int $days = 30): array
    {
        $cacheKey = "game_performance_{$gameSlug}_{$days}d";
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($gameSlug, $days) {
            return [
                'game_slug' => $gameSlug,
                'total_plays' => $this->getGameTotalPlays($gameSlug, $days),
                'unique_players' => $this->getGameUniquePlayers($gameSlug, $days),
                'average_session_duration' => $this->getGameAverageSessionDuration($gameSlug, $days),
                'completion_rate' => $this->getGameCompletionRate($gameSlug, $days),
                'popular_times' => $this->getGamePopularTimes($gameSlug, $days),
                'user_retention' => $this->getGameUserRetention($gameSlug, $days),
                'period_days' => $days,
            ];
        });
    }

    /**
     * Update page view statistics
     */
    protected function updatePageViewStats(string $page): void
    {
        $periods = [1, 7, 30]; // 1 day, 7 days, 30 days
        
        foreach ($periods as $days) {
            $expiration = now()->addDays($days);
            
            // Total page views
            $totalKey = "page_views_total_{$days}d";
            $currentTotal = Cache::get($totalKey, 0);
            Cache::put($totalKey, $currentTotal + 1, $expiration);
            
            // Page-specific views
            $pageKey = "page_views_{$days}d_" . md5($page);
            $currentPageViews = Cache::get($pageKey, 0);
            Cache::put($pageKey, $currentPageViews + 1, $expiration);
            
            // Update popular pages
            $this->updatePopularPages($page, $days);
        }
    }

    /**
     * Update action statistics
     */
    protected function updateActionStats(string $action): void
    {
        $periods = [1, 7, 30];
        
        foreach ($periods as $days) {
            $expiration = now()->addDays($days);
            
            $actionKey = "user_actions_{$days}d_" . md5($action);
            $currentCount = Cache::get($actionKey, 0);
            Cache::put($actionKey, $currentCount + 1, $expiration);
        }
    }

    /**
     * Update game statistics
     */
    protected function updateGameStats(string $gameSlug, string $event): void
    {
        $periods = [1, 7, 30];
        
        foreach ($periods as $days) {
            $expiration = now()->addDays($days);
            
            // Game event count
            $eventKey = "game_events_{$days}d_{$gameSlug}_" . md5($event);
            $currentCount = Cache::get($eventKey, 0);
            Cache::put($eventKey, $currentCount + 1, $expiration);
            
            // Total game events
            $totalKey = "game_events_total_{$days}d_{$gameSlug}";
            $currentTotal = Cache::get($totalKey, 0);
            Cache::put($totalKey, $currentTotal + 1, $expiration);
        }
    }

    /**
     * Update engagement statistics
     */
    protected function updateEngagementStats(string $engagementType, int $duration): void
    {
        $periods = [1, 7, 30];
        
        foreach ($periods as $days) {
            $expiration = now()->addDays($days);
            
            // Engagement count
            $countKey = "engagement_count_{$days}d_" . md5($engagementType);
            $currentCount = Cache::get($countKey, 0);
            Cache::put($countKey, $currentCount + 1, $expiration);
            
            // Total engagement time
            $timeKey = "engagement_time_{$days}d_" . md5($engagementType);
            $currentTime = Cache::get($timeKey, 0);
            Cache::put($timeKey, $currentTime + $duration, $expiration);
        }
    }

    /**
     * Update popular pages list
     */
    protected function updatePopularPages(string $page, int $days): void
    {
        $popularPagesKey = "popular_pages_{$days}d";
        $popularPages = Cache::get($popularPagesKey, []);
        
        if (!isset($popularPages[$page])) {
            $popularPages[$page] = 0;
        }
        
        $popularPages[$page]++;
        
        // Keep only top 20 pages
        arsort($popularPages);
        $popularPages = array_slice($popularPages, 0, 20, true);
        
        Cache::put($popularPagesKey, $popularPages, now()->addDays($days));
    }

    /**
     * Get overview statistics
     */
    protected function getOverviewStats(int $days): array
    {
        return [
            'total_page_views' => Cache::get("page_views_total_{$days}d", 0),
            'unique_visitors' => Cache::get("unique_visitors_{$days}d", 0),
            'total_sessions' => Cache::get("total_sessions_{$days}d", 0),
            'bounce_rate' => Cache::get("bounce_rate_{$days}d", 0.0),
        ];
    }

    /**
     * Get page view statistics
     */
    protected function getPageViewStats(int $days): array
    {
        return [
            'total_views' => Cache::get("page_views_total_{$days}d", 0),
            'popular_pages' => Cache::get("popular_pages_{$days}d", []),
        ];
    }

    /**
     * Get user action statistics
     */
    protected function getUserActionStats(int $days): array
    {
        // This would typically query cached action data
        return [
            'total_actions' => Cache::get("total_actions_{$days}d", 0),
            'top_actions' => Cache::get("top_actions_{$days}d", []),
        ];
    }

    /**
     * Get game event statistics
     */
    protected function getGameEventStats(int $days): array
    {
        return [
            'total_game_events' => Cache::get("total_game_events_{$days}d", 0),
            'games_by_popularity' => Cache::get("games_by_popularity_{$days}d", []),
        ];
    }

    /**
     * Get engagement statistics
     */
    protected function getEngagementStats(int $days): array
    {
        return [
            'average_session_duration' => Cache::get("avg_session_duration_{$days}d", 0),
            'total_engagement_time' => Cache::get("total_engagement_time_{$days}d", 0),
        ];
    }

    /**
     * Get retention statistics
     */
    protected function getRetentionStats(int $days): array
    {
        return [
            'daily_retention' => Cache::get("daily_retention_{$days}d", 0.0),
            'weekly_retention' => Cache::get("weekly_retention_{$days}d", 0.0),
            'monthly_retention' => Cache::get("monthly_retention_{$days}d", 0.0),
        ];
    }

    /**
     * Get user session count
     */
    protected function getUserSessionCount(int $userId, int $days): int
    {
        return Cache::get("user_sessions_{$userId}_{$days}d", 0);
    }

    /**
     * Get user page views
     */
    protected function getUserPageViews(int $userId, int $days): int
    {
        return Cache::get("user_page_views_{$userId}_{$days}d", 0);
    }

    /**
     * Get user actions
     */
    protected function getUserActions(int $userId, int $days): array
    {
        return Cache::get("user_actions_{$userId}_{$days}d", []);
    }

    /**
     * Get user games played
     */
    protected function getUserGamesPlayed(int $userId, int $days): array
    {
        return Cache::get("user_games_{$userId}_{$days}d", []);
    }

    /**
     * Get user engagement time
     */
    protected function getUserEngagementTime(int $userId, int $days): int
    {
        return Cache::get("user_engagement_time_{$userId}_{$days}d", 0);
    }

    /**
     * Get user last activity
     */
    protected function getUserLastActivity(int $userId): ?string
    {
        return Cache::get("user_last_activity_{$userId}");
    }

    /**
     * Get game total plays
     */
    protected function getGameTotalPlays(string $gameSlug, int $days): int
    {
        return Cache::get("game_total_plays_{$gameSlug}_{$days}d", 0);
    }

    /**
     * Get game unique players
     */
    protected function getGameUniquePlayers(string $gameSlug, int $days): int
    {
        return Cache::get("game_unique_players_{$gameSlug}_{$days}d", 0);
    }

    /**
     * Get game average session duration
     */
    protected function getGameAverageSessionDuration(string $gameSlug, int $days): float
    {
        return Cache::get("game_avg_session_{$gameSlug}_{$days}d", 0.0);
    }

    /**
     * Get game completion rate
     */
    protected function getGameCompletionRate(string $gameSlug, int $days): float
    {
        return Cache::get("game_completion_rate_{$gameSlug}_{$days}d", 0.0);
    }

    /**
     * Get game popular times
     */
    protected function getGamePopularTimes(string $gameSlug, int $days): array
    {
        return Cache::get("game_popular_times_{$gameSlug}_{$days}d", []);
    }

    /**
     * Get game user retention
     */
    protected function getGameUserRetention(string $gameSlug, int $days): array
    {
        return Cache::get("game_user_retention_{$gameSlug}_{$days}d", []);
    }

    /**
     * Clear analytics cache
     */
    public function clearAnalyticsCache(): void
    {
        $periods = [1, 7, 30];
        $cacheKeys = [];
        
        foreach ($periods as $days) {
            $cacheKeys[] = "analytics_dashboard_{$days}d";
            $cacheKeys[] = "page_views_total_{$days}d";
            $cacheKeys[] = "popular_pages_{$days}d";
            $cacheKeys[] = "total_actions_{$days}d";
            $cacheKeys[] = "total_game_events_{$days}d";
        }
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}