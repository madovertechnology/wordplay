<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as FacadesRateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the IP address or authenticated user ID as the key
        $key = $request->user() ? 'api-' . $request->user()->id : 'api-' . $request->ip();
        
        // Define the maximum number of attempts allowed within the decay time
        $maxAttempts = 60; // 60 requests per minute for API endpoints
        
        // Define the decay time in seconds (1 minute)
        $decaySeconds = 60;
        
        // Check if the request has exceeded the rate limit
        if (FacadesRateLimiter::tooManyAttempts($key, $maxAttempts)) {
            // Calculate the number of seconds until the rate limit is reset
            $seconds = FacadesRateLimiter::availableIn($key);
            
            return response()->json([
                'message' => 'API rate limit exceeded. Please try again in ' . $seconds . ' seconds.',
            ], 429);
        }
        
        // Increment the rate limiter counter
        FacadesRateLimiter::hit($key, $decaySeconds);
        
        // Continue with the request
        return $next($request);
    }
}