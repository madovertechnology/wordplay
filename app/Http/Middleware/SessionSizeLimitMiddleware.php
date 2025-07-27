<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SessionSizeLimitMiddleware
{
    /**
     * Maximum session size in bytes (4KB limit)
     */
    protected $maxSessionSize = 4096;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check session size before processing
        $this->checkSessionSize($request);

        $response = $next($request);

        // Check session size after processing
        $this->checkSessionSize($request);

        return $response;
    }

    /**
     * Check and limit session size
     */
    private function checkSessionSize(Request $request): void
    {
        if (!$request->session()->isStarted()) {
            return;
        }

        $sessionData = $request->session()->all();
        $sessionSize = strlen(serialize($sessionData));

        if ($sessionSize > $this->maxSessionSize) {
            Log::warning('Session size exceeded limit', [
                'size' => $sessionSize,
                'limit' => $this->maxSessionSize,
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'path' => $request->path()
            ]);

            // Remove non-essential session data
            $this->cleanupSession($request);
        }
    }

    /**
     * Clean up session data to reduce size
     */
    private function cleanupSession(Request $request): void
    {
        $session = $request->session();

        // Keep only essential session data
        $essentialKeys = [
            '_token',
            'auth',
            'user_id',
            'guest_token',
            'flash',
            'errors'
        ];

        $allData = $session->all();
        $cleanedData = [];

        foreach ($essentialKeys as $key) {
            if (isset($allData[$key])) {
                $cleanedData[$key] = $allData[$key];
            }
        }

        // Clear session and restore only essential data
        $session->flush();
        foreach ($cleanedData as $key => $value) {
            $session->put($key, $value);
        }

        Log::info('Session cleaned up to reduce size', [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'path' => $request->path()
        ]);
    }
}
