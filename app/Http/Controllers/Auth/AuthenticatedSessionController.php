<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Get the user before session regeneration
        $user = Auth::user();

        $request->session()->regenerate();

        // Re-authenticate the user after session regeneration
        Auth::login($user);

        return redirect()->intended(route('dashboard', absolute: false));
    }

                /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Force logout regardless of authentication state
        Auth::guard('web')->logout();

        // Clear all session data
        $request->session()->flush();

        // Invalidate and regenerate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Clear all possible cookies
        $cookies = [
            cookie()->forget('daily_games_platform_session'),
            cookie()->forget('XSRF-TOKEN'),
            cookie()->forget('laravel_session'),
        ];

        // Check if this is an Inertia request
        if ($request->header('X-Inertia')) {
            // For Inertia requests, return a response that will force a full page reload
            $response = redirect('/')->withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Inertia-Location' => '/'
            ]);

            // Add all cookies to the response
            foreach ($cookies as $cookie) {
                if ($cookie) {
                    $response->withCookie($cookie);
                }
            }

            return $response;
        }

        // For regular requests, do a hard redirect
        $response = redirect('/')->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);

        // Add all cookies to the response
        foreach ($cookies as $cookie) {
            if ($cookie) {
                $response->withCookie($cookie);
            }
        }

        return $response;
    }
}
