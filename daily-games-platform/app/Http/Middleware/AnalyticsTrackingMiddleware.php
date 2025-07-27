<?php

namespace App\Http\Middleware;

use App\Services\Monitoring\UserAnalyticsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsTrackingMiddleware
{
    public function __construct(
        protected UserAnalyticsService $userAnalyticsService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only track successful responses
        if ($response->getStatusCode() < 400) {
            $this->trackPageView($request);
            $this->trackUserSession($request);
        }
        
        return $response;
    }

    /**
     * Track page view
     */
    protected function trackPageView(Request $request): void
    {
        // Don't track API endpoints or asset requests
        if ($this->shouldSkipTracking($request)) {
            return;
        }

        $page = $this->getPageIdentifier($request);
        $userId = auth()->id();
        $guestId = $this->getGuestId($request);

        $this->userAnalyticsService->trackPageView($page, $userId, $guestId);
    }

    /**
     * Track user session
     */
    protected function trackUserSession(Request $request): void
    {
        $sessionId = session()->getId();
        $userId = auth()->id();
        
        // Track session start if it's a new session
        if (!session()->has('analytics_session_tracked')) {
            session()->put('analytics_session_tracked', true);
            session()->put('analytics_session_start', now()->timestamp);
            
            $this->userAnalyticsService->trackUserAction('session_start', [
                'session_id' => $sessionId,
                'is_authenticated' => $userId !== null,
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
            ], $userId);
        }
        
        // Update last activity
        session()->put('analytics_last_activity', now()->timestamp);
    }

    /**
     * Determine if tracking should be skipped
     */
    protected function shouldSkipTracking(Request $request): bool
    {
        $path = $request->path();
        
        // Skip API endpoints
        if (str_starts_with($path, 'api/')) {
            return true;
        }
        
        // Skip asset requests
        if (str_starts_with($path, 'build/') || 
            str_starts_with($path, 'storage/') ||
            str_ends_with($path, '.css') ||
            str_ends_with($path, '.js') ||
            str_ends_with($path, '.ico') ||
            str_ends_with($path, '.png') ||
            str_ends_with($path, '.jpg') ||
            str_ends_with($path, '.svg')) {
            return true;
        }
        
        // Skip health check endpoints
        if ($path === 'up' || $path === 'health') {
            return true;
        }
        
        return false;
    }

    /**
     * Get page identifier for tracking
     */
    protected function getPageIdentifier(Request $request): string
    {
        $path = $request->path();
        
        // Normalize common patterns
        $path = preg_replace('/\/\d+$/', '/{id}', $path); // Replace numeric IDs
        $path = preg_replace('/\/[a-f0-9-]{36}$/', '/{uuid}', $path); // Replace UUIDs
        
        return $path === '/' ? 'home' : $path;
    }

    /**
     * Get guest ID from cookie or session
     */
    protected function getGuestId(Request $request): ?string
    {
        // Try to get guest ID from cookie first
        $guestId = $request->cookie('guest_id');
        
        if (!$guestId) {
            // Try to get from session
            $guestId = session()->get('guest_id');
        }
        
        return $guestId;
    }
}