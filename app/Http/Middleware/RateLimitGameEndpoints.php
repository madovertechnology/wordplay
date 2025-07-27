<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitGameEndpoints
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract game slug and endpoint type
        $gameSlug = $this->extractGameSlug($request);
        $endpointType = $this->getEndpointType($request);
        $isWriteOperation = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);
        
        // Determine user identifier for rate limiting
        $userIdentifier = $this->getUserIdentifier($request);
        
        // Create rate limit key
        $key = "game-{$gameSlug}-{$endpointType}-{$userIdentifier}";
        
        // Get rate limits based on operation type and user status
        [$maxAttempts, $decaySeconds] = $this->getRateLimits($request, $isWriteOperation, $endpointType);
        
        // Check if the request has exceeded the rate limit
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            // Log rate limit violations for monitoring
            \Log::warning('Game endpoint rate limit exceeded', [
                'key' => $key,
                'ip' => $request->ip(),
                'user_id' => Auth::id(),
                'path' => $request->path(),
                'method' => $request->method(),
                'retry_after' => $seconds
            ]);
            
            return response()->json([
                'message' => 'Rate limit exceeded. Please try again in ' . $seconds . ' seconds.',
                'status' => 'error',
                'retry_after' => $seconds,
                'limit' => $maxAttempts,
                'window' => $decaySeconds
            ], 429);
        }
        
        // Increment the rate limiter counter
        RateLimiter::hit($key, $decaySeconds);
        
        // Process the request
        $response = $next($request);
        
        // Add rate limit headers to the response
        $this->addRateLimitHeaders($response, $key, $maxAttempts);
        
        return $response;
    }

    /**
     * Extract game slug from the request path.
     *
     * @param Request $request
     * @return string
     */
    private function extractGameSlug(Request $request): string
    {
        $path = $request->path();
        
        // For games/word-scramble/api/... paths
        if (preg_match('/games\/([^\/]+)/', $path, $matches)) {
            return $matches[1];
        }
        
        // For leaderboards/api/{game}/... paths
        if (preg_match('/leaderboards\/api\/([^\/]+)/', $path, $matches)) {
            return $matches[1];
        }
        
        // For streaks/{game} paths
        if (preg_match('/streaks\/([^\/]+)/', $path, $matches)) {
            return $matches[1];
        }

        return 'unknown';
    }

    /**
     * Determine the endpoint type for more granular rate limiting.
     *
     * @param Request $request
     * @return string
     */
    private function getEndpointType(Request $request): string
    {
        $path = $request->path();
        
        // Submission endpoints (most restrictive)
        if (str_contains($path, '/submit')) {
            return 'submit';
        }
        
        // User-specific data endpoints
        if (str_contains($path, '/submissions') || str_contains($path, '/user-rank')) {
            return 'user-data';
        }
        
        // Leaderboard endpoints
        if (str_contains($path, '/leaderboard') || str_contains($path, 'leaderboards/')) {
            return 'leaderboard';
        }
        
        // Puzzle/game data endpoints
        if (str_contains($path, '/puzzle') || str_contains($path, '/stats')) {
            return 'game-data';
        }
        
        // Streak endpoints
        if (str_contains($path, '/streaks/')) {
            return 'streak';
        }
        
        // Gamification endpoints
        if (str_contains($path, '/gamification/')) {
            return 'gamification';
        }
        
        return 'general';
    }

    /**
     * Get user identifier for rate limiting.
     *
     * @param Request $request
     * @return string
     */
    private function getUserIdentifier(Request $request): string
    {
        if (Auth::check()) {
            return 'user-' . Auth::id();
        }
        
        // For guests, use guest token if available, otherwise IP
        $guestToken = $request->cookie('guest_token');
        if ($guestToken) {
            return 'guest-' . substr(md5($guestToken), 0, 8);
        }
        
        return 'ip-' . $request->ip();
    }

    /**
     * Get rate limits based on operation type and user status.
     *
     * @param Request $request
     * @param bool $isWriteOperation
     * @param string $endpointType
     * @return array [maxAttempts, decaySeconds]
     */
    private function getRateLimits(Request $request, bool $isWriteOperation, string $endpointType): array
    {
        $isAuthenticated = Auth::check();
        
        // Base rate limits (per minute)
        $rateLimits = [
            'submit' => [
                'authenticated' => ['write' => 20, 'read' => 30],
                'guest' => ['write' => 10, 'read' => 20]
            ],
            'user-data' => [
                'authenticated' => ['write' => 30, 'read' => 60],
                'guest' => ['write' => 15, 'read' => 30]
            ],
            'leaderboard' => [
                'authenticated' => ['write' => 10, 'read' => 100],
                'guest' => ['write' => 5, 'read' => 50]
            ],
            'game-data' => [
                'authenticated' => ['write' => 20, 'read' => 120],
                'guest' => ['write' => 10, 'read' => 60]
            ],
            'streak' => [
                'authenticated' => ['write' => 20, 'read' => 60],
                'guest' => ['write' => 0, 'read' => 0] // Guests can't access streaks
            ],
            'gamification' => [
                'authenticated' => ['write' => 30, 'read' => 60],
                'guest' => ['write' => 0, 'read' => 0] // Guests can't access gamification
            ],
            'general' => [
                'authenticated' => ['write' => 40, 'read' => 80],
                'guest' => ['write' => 20, 'read' => 40]
            ]
        ];

        $userType = $isAuthenticated ? 'authenticated' : 'guest';
        $operationType = $isWriteOperation ? 'write' : 'read';
        
        $maxAttempts = $rateLimits[$endpointType][$userType][$operationType] ?? 
                      $rateLimits['general'][$userType][$operationType];
        
        // Decay time is always 60 seconds (1 minute)
        $decaySeconds = 60;
        
        // For highly sensitive operations, use shorter windows
        if ($endpointType === 'submit' && $isWriteOperation) {
            $decaySeconds = 60; // Keep 1 minute for submissions
        }
        
        return [$maxAttempts, $decaySeconds];
    }

    /**
     * Add rate limit headers to the response.
     *
     * @param Response $response
     * @param string $key
     * @param int $maxAttempts
     * @return void
     */
    private function addRateLimitHeaders(Response $response, string $key, int $maxAttempts): void
    {
        $remaining = RateLimiter::remaining($key, $maxAttempts);
        $resetTime = RateLimiter::availableIn($key);
        
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining));
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($resetTime)->timestamp);
    }
}