<?php

namespace App\Http\Controllers;

use App\Services\Monitoring\UserAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        protected UserAnalyticsService $userAnalyticsService
    ) {}

    /**
     * Display the analytics dashboard
     */
    public function dashboard(Request $request): Response
    {
        $days = $request->integer('days', 7);
        
        $dashboardData = $this->userAnalyticsService->getDashboardData($days);
        
        return Inertia::render('Analytics/Dashboard', [
            'analytics' => $dashboardData,
            'selectedPeriod' => $days,
        ]);
    }

    /**
     * Get analytics dashboard data via API
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'integer|min:1|max:365',
        ]);
        
        $days = $request->integer('days', 7);
        $dashboardData = $this->userAnalyticsService->getDashboardData($days);
        
        return response()->json($dashboardData);
    }

    /**
     * Get user behavior analytics
     */
    public function getUserBehavior(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'days' => 'integer|min:1|max:365',
        ]);
        
        $userId = $request->integer('user_id');
        $days = $request->integer('days', 30);
        
        $behaviorData = $this->userAnalyticsService->getUserBehaviorAnalytics($userId, $days);
        
        return response()->json($behaviorData);
    }

    /**
     * Get game performance analytics
     */
    public function getGamePerformance(Request $request, string $gameSlug): JsonResponse
    {
        $request->validate([
            'days' => 'integer|min:1|max:365',
        ]);
        
        $days = $request->integer('days', 30);
        
        $performanceData = $this->userAnalyticsService->getGamePerformanceAnalytics($gameSlug, $days);
        
        return response()->json($performanceData);
    }

    /**
     * Track a custom user action
     */
    public function trackAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|max:255',
            'context' => 'array',
        ]);
        
        $this->userAnalyticsService->trackUserAction(
            $request->string('action'),
            $request->array('context', [])
        );
        
        return response()->json(['success' => true]);
    }

    /**
     * Track a game event
     */
    public function trackGameEvent(Request $request): JsonResponse
    {
        $request->validate([
            'game_slug' => 'required|string|max:255',
            'event' => 'required|string|max:255',
            'data' => 'array',
        ]);
        
        $this->userAnalyticsService->trackGameEvent(
            $request->string('game_slug'),
            $request->string('event'),
            $request->array('data', [])
        );
        
        return response()->json(['success' => true]);
    }

    /**
     * Track user engagement
     */
    public function trackEngagement(Request $request): JsonResponse
    {
        $request->validate([
            'engagement_type' => 'required|string|max:255',
            'duration' => 'integer|min:0',
            'context' => 'array',
        ]);
        
        $this->userAnalyticsService->trackUserEngagement(
            $request->string('engagement_type'),
            $request->integer('duration', 0),
            $request->array('context', [])
        );
        
        return response()->json(['success' => true]);
    }

    /**
     * Clear analytics cache (admin only)
     */
    public function clearCache(): JsonResponse
    {
        // In a real application, you would check for admin permissions here
        $this->userAnalyticsService->clearAnalyticsCache();
        
        return response()->json(['success' => true, 'message' => 'Analytics cache cleared']);
    }
}