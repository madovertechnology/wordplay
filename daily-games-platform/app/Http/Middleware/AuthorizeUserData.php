<?php

namespace App\Http\Middleware;

use App\Models\Game;
use App\Models\Guest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeUserData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for suspicious request patterns
        if ($this->isSuspiciousRequest($request)) {
            return response()->json([
                'message' => 'Request blocked due to suspicious activity.',
                'status' => 'error'
            ], 403);
        }

        // Validate game access for game-specific endpoints
        if ($this->isGameEndpoint($request)) {
            $gameValidation = $this->validateGameAccess($request);
            if ($gameValidation !== true) {
                return $gameValidation;
            }
        }

        // Handle authentication requirements
        $authResult = $this->handleAuthentication($request);
        if ($authResult !== true) {
            return $authResult;
        }

        // For routes that include a user ID parameter, ensure it matches the authenticated user
        $userId = $request->route('user');
        if ($userId && Auth::check() && $userId != Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized access to user data.',
                'status' => 'error'
            ], 403);
        }

        // For admin-only endpoints (if any), check for admin role
        if ($request->route()->getName() && str_contains($request->route()->getName(), 'admin')) {
            $user = Auth::user();
            if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole('admin')) {
                return response()->json([
                    'message' => 'Admin access required.',
                    'status' => 'error'
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Check if the request shows suspicious patterns.
     *
     * @param Request $request
     * @return bool
     */
    private function isSuspiciousRequest(Request $request): bool
    {
        // Check for common attack patterns
        $userAgent = $request->header('User-Agent', '');
        $suspiciousPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'python-requests', 'postman', 'insomnia'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                // Allow legitimate tools but log for monitoring
                \Log::info('Suspicious user agent detected', [
                    'user_agent' => $userAgent,
                    'ip' => $request->ip(),
                    'path' => $request->path()
                ]);
                break;
            }
        }

        // Check for SQL injection attempts in query parameters
        $queryString = $request->getQueryString();
        if ($queryString) {
            $sqlPatterns = [
                'union', 'select', 'insert', 'update', 'delete', 'drop',
                'exec', 'script', 'javascript:', 'vbscript:', '<script'
            ];
            
            foreach ($sqlPatterns as $pattern) {
                if (stripos($queryString, $pattern) !== false) {
                    \Log::warning('Potential SQL injection attempt', [
                        'query' => $queryString,
                        'ip' => $request->ip(),
                        'path' => $request->path()
                    ]);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if this is a game-specific endpoint.
     *
     * @param Request $request
     * @return bool
     */
    private function isGameEndpoint(Request $request): bool
    {
        $path = $request->path();
        return str_contains($path, 'games/') || 
               str_contains($path, 'leaderboards/') ||
               str_contains($path, 'streaks/');
    }

    /**
     * Validate access to game endpoints.
     *
     * @param Request $request
     * @return Response|bool
     */
    private function validateGameAccess(Request $request)
    {
        // Extract game slug from the route
        $gameSlug = $this->extractGameSlug($request);
        
        if ($gameSlug) {
            // Check if the game exists and is active
            $game = Cache::remember("game:{$gameSlug}", 300, function () use ($gameSlug) {
                return Game::where('slug', $gameSlug)->first();
            });

            if (!$game) {
                return response()->json([
                    'message' => 'Game not found.',
                    'status' => 'error'
                ], 404);
            }

            if (!$game->is_active) {
                return response()->json([
                    'message' => 'Game is currently unavailable.',
                    'status' => 'error'
                ], 503);
            }
        }

        return true;
    }

    /**
     * Extract game slug from the request path.
     *
     * @param Request $request
     * @return string|null
     */
    private function extractGameSlug(Request $request): ?string
    {
        $path = $request->path();
        
        // For games/word-scramble/api/... paths
        if (preg_match('/games\/([^\/]+)/', $path, $matches)) {
            return $matches[1];
        }
        
        // For leaderboards/{game}/... paths
        if (preg_match('/leaderboards\/api\/([^\/]+)/', $path, $matches)) {
            return $matches[1];
        }
        
        // For streaks/{game} paths
        if (preg_match('/streaks\/([^\/]+)/', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Handle authentication requirements for different endpoints.
     *
     * @param Request $request
     * @return Response|bool
     */
    private function handleAuthentication(Request $request)
    {
        $path = $request->path();
        $method = $request->method();

        // Endpoints that require authentication
        $authRequiredPatterns = [
            '/api\/streaks\//',
            '/api\/gamification\//',
        ];

        foreach ($authRequiredPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                if (!Auth::check()) {
                    return response()->json([
                        'message' => 'Authentication required.',
                        'status' => 'error'
                    ], 401);
                }
                return true;
            }
        }

        // Endpoints that require authentication OR valid guest token
        $authOrGuestPatterns = [
            '/games\/.*\/api\/submit$/',
            '/games\/.*\/api\/submissions$/',
        ];

        foreach ($authOrGuestPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                // Check if user is authenticated
                if (Auth::check()) {
                    return true;
                }

                // For submit endpoints, allow the request to proceed and let the controller handle guest token creation
                if (preg_match('/\/api\/submit$/', $path)) {
                    return true;
                }

                // For submissions endpoint, check for valid guest token but don't block if missing
                $guestToken = $request->cookie('guest_token');
                if ($guestToken) {
                    $guest = Cache::remember("guest:{$guestToken}", 300, function () use ($guestToken) {
                        return Guest::where('guest_token', $guestToken)->first();
                    });

                    if ($guest) {
                        return true;
                    }
                }

                // Allow the request to proceed for submissions - controller will handle empty response
                return true;
            }
        }

        // Write operations require stricter validation (except for game submissions which are handled above)
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Skip additional validation for game submission endpoints - already handled above
            if (preg_match('/games\/.*\/api\/submit$/', $path)) {
                return true;
            }
            
            // Additional validation for other write operations
            if (!Auth::check()) {
                $guestToken = $request->cookie('guest_token');
                if (!$guestToken) {
                    return response()->json([
                        'message' => 'Authentication or valid guest session required for this operation.',
                        'status' => 'error'
                    ], 401);
                }
            }
        }

        return true;
    }
}