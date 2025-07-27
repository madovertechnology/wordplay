<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validate request headers
        if (!$this->validateHeaders($request)) {
            return response()->json([
                'message' => 'Invalid request headers.',
                'status' => 'error'
            ], 400);
        }

        // Check for API abuse patterns
        if ($this->detectAbusePatterns($request)) {
            return response()->json([
                'message' => 'Request blocked due to abuse detection.',
                'status' => 'error'
            ], 429);
        }

        // Validate request size
        if (!$this->validateRequestSize($request)) {
            return response()->json([
                'message' => 'Request payload too large.',
                'status' => 'error'
            ], 413);
        }

        // Add security headers to response
        $response = $next($request);
        $this->addSecurityHeaders($response);

        return $response;
    }

    /**
     * Validate request headers for security.
     *
     * @param Request $request
     * @return bool
     */
    private function validateHeaders(Request $request): bool
    {
        // Check for required headers on API requests
        if (str_contains($request->path(), '/api/')) {
            // Require Accept header for API requests
            if (!$request->hasHeader('Accept')) {
                Log::warning('API request missing Accept header', [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                    'user_agent' => $request->header('User-Agent')
                ]);
                return false;
            }

            // Validate Content-Type for POST/PUT/PATCH requests
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
                $contentType = $request->header('Content-Type');
                $allowedTypes = [
                    'application/json',
                    'application/x-www-form-urlencoded',
                    'multipart/form-data'
                ];

                $isValidContentType = false;
                foreach ($allowedTypes as $type) {
                    if (str_contains($contentType, $type)) {
                        $isValidContentType = true;
                        break;
                    }
                }

                if (!$isValidContentType) {
                    Log::warning('API request with invalid Content-Type', [
                        'ip' => $request->ip(),
                        'path' => $request->path(),
                        'content_type' => $contentType
                    ]);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Detect abuse patterns in requests.
     *
     * @param Request $request
     * @return bool
     */
    private function detectAbusePatterns(Request $request): bool
    {
        $ip = $request->ip();
        $userAgent = $request->header('User-Agent', '');
        
        // Check for rapid successive requests from same IP
        $requestKey = "api_requests:{$ip}";
        $requestCount = Cache::get($requestKey, 0);
        
        if ($requestCount > 200) { // More than 200 requests per minute from same IP
            Log::warning('High request volume detected', [
                'ip' => $ip,
                'count' => $requestCount,
                'path' => $request->path()
            ]);
            return true;
        }
        
        Cache::put($requestKey, $requestCount + 1, 60); // Increment counter for 1 minute

        // Check for suspicious user agents
        $suspiciousAgents = [
            'masscan', 'nmap', 'nikto', 'sqlmap', 'dirb', 'gobuster',
            'wpscan', 'nuclei', 'burpsuite', 'owasp'
        ];

        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                Log::warning('Suspicious user agent detected', [
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'path' => $request->path()
                ]);
                return true;
            }
        }

        // Check for empty or very short user agents (often bots)
        if (strlen($userAgent) < 10) {
            Log::info('Short user agent detected', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'path' => $request->path()
            ]);
        }

        return false;
    }

    /**
     * Validate request payload size.
     *
     * @param Request $request
     * @return bool
     */
    private function validateRequestSize(Request $request): bool
    {
        // Check content length
        $contentLength = $request->header('Content-Length', 0);
        $maxSize = 1024 * 1024; // 1MB max for API requests

        if ($contentLength > $maxSize) {
            Log::warning('Request payload too large', [
                'ip' => $request->ip(),
                'content_length' => $contentLength,
                'max_size' => $maxSize,
                'path' => $request->path()
            ]);
            return false;
        }

        return true;
    }

    /**
     * Add security headers to the response.
     *
     * @param Response $response
     * @return void
     */
    private function addSecurityHeaders(Response $response): void
    {
        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Add API-specific headers
        if (str_contains($response->headers->get('Content-Type', ''), 'application/json')) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }
    }
}