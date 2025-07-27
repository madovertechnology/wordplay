<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $dashboardService = app(\App\Services\Core\DashboardService::class);
    return Inertia::render('Dashboard', [
        'dashboardData' => $dashboardService->getDashboardData(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Guest routes (temporarily disabled)
// Route::prefix('guest')->name('guest.')->group(function () {
//     Route::post('/data', [App\Http\Controllers\GuestController::class, 'storeData'])->name('store-data');
//     Route::get('/data/{key}', [App\Http\Controllers\GuestController::class, 'getData'])->name('get-data');
//     Route::delete('/data', [App\Http\Controllers\GuestController::class, 'clearData'])->name('clear-data');
// });

// Leaderboard routes
Route::prefix('leaderboards')->name('leaderboards.')->group(function () {
    // Web routes
    Route::get('/{game}/{period?}', [App\Http\Controllers\LeaderboardController::class, 'show'])
        ->name('show');

    // API routes with enhanced security
    Route::prefix('api')->name('api.')->middleware([])->group(function () {
        Route::get('/{game}/{period?}', [App\Http\Controllers\LeaderboardController::class, 'getLeaderboard'])
            ->name('get');
        Route::get('/{game}/{period?}/user-rank', [App\Http\Controllers\LeaderboardController::class, 'getUserRank'])
            ->name('user-rank');
    });
});

// Streak routes
Route::prefix('api/streaks')->name('api.streaks.')->middleware(['auth'])->group(function () {
    Route::get('/{game}', [App\Http\Controllers\StreakController::class, 'getUserStreak'])
        ->name('get');
    Route::get('/{game}/top', [App\Http\Controllers\StreakController::class, 'getTopStreaks'])
        ->name('top');
});

// Gamification routes
Route::prefix('api/gamification')->name('api.gamification.')->middleware(['auth'])->group(function () {
    Route::get('/rank', [App\Http\Controllers\GamificationController::class, 'getUserRank'])
        ->name('rank');
    Route::get('/badges', [App\Http\Controllers\GamificationController::class, 'getUserBadges'])
        ->name('badges');
    Route::post('/check', [App\Http\Controllers\GamificationController::class, 'checkAchievements'])
        ->name('check');
});

// Word Scramble routes
Route::prefix('games/word-scramble')->name('games.word-scramble.')->group(function () {
    // Web routes
    Route::get('/', [App\Http\Controllers\WordScrambleController::class, 'show'])
        ->name('show');

    // API routes with enhanced security
    Route::prefix('api')->name('api.')->middleware([])->group(function () {
        // Public endpoints with rate limiting
        Route::get('/puzzle', [App\Http\Controllers\WordScrambleController::class, 'getTodaysPuzzle'])
            ->name('puzzle');
        Route::get('/leaderboard/daily', [App\Http\Controllers\WordScrambleController::class, 'getDailyLeaderboard'])
            ->name('leaderboard.daily');
        Route::get('/leaderboard/monthly', [App\Http\Controllers\WordScrambleController::class, 'getMonthlyLeaderboard'])
            ->name('leaderboard.monthly');
        Route::get('/leaderboard/all-time', [App\Http\Controllers\WordScrambleController::class, 'getAllTimeLeaderboard'])
            ->name('leaderboard.all-time');

        // Endpoints that require authentication or guest token with stricter rate limiting
        Route::post('/submit', [App\Http\Controllers\WordScrambleController::class, 'submitWord'])
            ->name('submit');
        Route::get('/submissions', [App\Http\Controllers\WordScrambleController::class, 'getSubmissions'])
            ->name('submissions');
    });
});

// Analytics routes (admin only in production)
Route::prefix('analytics')->name('analytics.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\AnalyticsController::class, 'dashboard'])
        ->name('dashboard');

    // API routes
    Route::prefix('api')->name('api.')->middleware(['api.security', 'api.rate.limit'])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AnalyticsController::class, 'getDashboardData'])
            ->name('dashboard-data');
        Route::get('/user-behavior', [App\Http\Controllers\AnalyticsController::class, 'getUserBehavior'])
            ->name('user-behavior');
        Route::get('/game-performance/{gameSlug}', [App\Http\Controllers\AnalyticsController::class, 'getGamePerformance'])
            ->name('game-performance');
        Route::post('/track-action', [App\Http\Controllers\AnalyticsController::class, 'trackAction'])
            ->name('track-action');
        Route::post('/track-game-event', [App\Http\Controllers\AnalyticsController::class, 'trackGameEvent'])
            ->name('track-game-event');
        Route::post('/track-engagement', [App\Http\Controllers\AnalyticsController::class, 'trackEngagement'])
            ->name('track-engagement');
        Route::delete('/cache', [App\Http\Controllers\AnalyticsController::class, 'clearCache'])
            ->name('clear-cache');
    });
});

// Health check routes
Route::get('/health', [App\Http\Controllers\HealthController::class, 'index'])->name('health');
Route::get('/health/detailed', [App\Http\Controllers\HealthController::class, 'detailed'])->name('health.detailed');

// Debug route for authentication testing
Route::get('/debug/auth', function () {
    return response()->json([
        'authenticated' => \Illuminate\Support\Facades\Auth::check(),
        'user' => \Illuminate\Support\Facades\Auth::user(),
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
        'user_id_in_session' => session('auth.user_id'),
    ]);
})->middleware('web');

// Test route for session persistence
Route::get('/debug/session-test', function () {
    $testValue = session('test_value', 'not_set');
    session(['test_value' => 'test_' . time()]);

    return response()->json([
        'session_id' => session()->getId(),
        'test_value' => $testValue,
        'new_test_value' => session('test_value'),
        'session_data' => session()->all(),
    ]);
})->middleware('web');

// Test route without middleware
Route::get('/debug/session-test-no-middleware', function () {
    $testValue = session('test_value', 'not_set');
    session(['test_value' => 'test_' . time()]);

    return response()->json([
        'session_id' => session()->getId(),
        'test_value' => $testValue,
        'new_test_value' => session('test_value'),
        'session_data' => session()->all(),
    ]);
});

// Test logout route
Route::get('/debug/test-logout', function () {
    return response()->json([
        'authenticated' => \Illuminate\Support\Facades\Auth::check(),
        'user' => \Illuminate\Support\Facades\Auth::user(),
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
    ]);
})->middleware('auth');

// Simple session test without any middleware
Route::get('/debug/simple-session-test', function () {
    $sessionId = session()->getId();
    $testValue = session('simple_test', 'not_set');
    session(['simple_test' => 'simple_' . time()]);

    return response()->json([
        'session_id' => $sessionId,
        'test_value' => $testValue,
        'new_test_value' => session('simple_test'),
        'session_data' => session()->all(),
    ]);
})->withoutMiddleware(\App\Http\Middleware\EncryptCookies::class);

// Debug logout test
Route::post('/debug/test-logout', function () {
    $beforeLogout = [
        'authenticated' => \Illuminate\Support\Facades\Auth::check(),
        'user' => \Illuminate\Support\Facades\Auth::user(),
        'session_id' => session()->getId(),
    ];

    \Illuminate\Support\Facades\Auth::guard('web')->logout();
    session()->invalidate();
    session()->regenerateToken();

    $afterLogout = [
        'authenticated' => \Illuminate\Support\Facades\Auth::check(),
        'user' => \Illuminate\Support\Facades\Auth::user(),
        'session_id' => session()->getId(),
    ];

    return response()->json([
        'before_logout' => $beforeLogout,
        'after_logout' => $afterLogout,
    ]);
});

// Simple logout test without CSRF
Route::get('/debug/logout-test', function () {
    $beforeLogout = [
        'authenticated' => \Illuminate\Support\Facades\Auth::check(),
        'user' => \Illuminate\Support\Facades\Auth::user(),
        'session_id' => session()->getId(),
    ];

    \Illuminate\Support\Facades\Auth::guard('web')->logout();
    session()->flush();
    session()->invalidate();
    session()->regenerateToken();

    $afterLogout = [
        'authenticated' => \Illuminate\Support\Facades\Auth::check(),
        'user' => \Illuminate\Support\Facades\Auth::user(),
        'session_id' => session()->getId(),
    ];

    return response()->json([
        'before_logout' => $beforeLogout,
        'after_logout' => $afterLogout,
        'message' => 'Logout completed',
    ]);
});

// Force logout route for frontend
Route::get('/force-logout', function () {
    \Illuminate\Support\Facades\Auth::guard('web')->logout();
    session()->flush();
    session()->invalidate();
    session()->regenerateToken();

    $cookie = cookie()->forget('daily_games_platform_session');

    return redirect('/')->withCookie($cookie)->withHeaders([
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ]);
})->name('force.logout');

// Test login and logout functionality
Route::get('/debug/test-login-logout', function () {
    // Try to authenticate a test user
    $user = \App\Models\User::first();

    if (!$user) {
        return response()->json(['error' => 'No users found in database']);
    }

    \Illuminate\Support\Facades\Auth::login($user);

    $beforeLogout = [
        'authenticated' => \Illuminate\Support\Facades\Auth::check(),
        'user' => \Illuminate\Support\Facades\Auth::user(),
        'session_id' => session()->getId(),
    ];

    // Perform logout
    \Illuminate\Support\Facades\Auth::guard('web')->logout();
    session()->flush();
    session()->invalidate();
    session()->regenerateToken();

    $afterLogout = [
        'authenticated' => \Illuminate\Support\Facades\Auth::check(),
        'user' => \Illuminate\Support\Facades\Auth::user(),
        'session_id' => session()->getId(),
    ];

    return response()->json([
        'before_logout' => $beforeLogout,
        'after_logout' => $afterLogout,
        'message' => 'Login and logout test completed',
    ]);
});

// Nuclear logout - completely bypasses Inertia and forces hard logout (temporarily disabled)
// Route::get('/nuclear-logout', function () {
//     // Force logout
//     \Illuminate\Support\Facades\Auth::guard('web')->logout();

//     // Clear all session data
//     session()->flush();
//     session()->invalidate();
//     session()->regenerateToken();

//     // Clear all possible cookies
//     $cookies = [
//         cookie()->forget('daily_games_platform_session'),
//         cookie()->forget('XSRF-TOKEN'),
//         cookie()->forget('laravel_session'),
//     ];

//     // Return a simple HTML page that clears everything and redirects
//     $html = '
//     <!DOCTYPE html>
//     <html>
//     <head>
//         <title>Logging out...</title>
//         <script>
//             // Clear all browser storage
//             localStorage.clear();
//             sessionStorage.clear();

//             // Clear all cookies
//             document.cookie.split(";").forEach(function(c) {
//                 document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
//             });

//             // Force redirect to home page
//             setTimeout(function() {
//                 window.location.replace("/");
//             }, 100);
//         </script>
//     </head>
//     <body>
//         <p>Logging out...</p>
//     </body>
//     </html>';

//     $response = response($html);

//     // Add all cookies to the response
//     foreach ($cookies as $cookie) {
//         if ($cookie) {
//             $response->withCookie($cookie);
//         }
//     }

//     return $response->withHeaders([
//         'Cache-Control' => 'no-cache, no-store, must-revalidate',
//         'Pragma' => 'no-cache',
//         'Expires' => '0'
//     ]);
// })->name('nuclear.logout');

// Force refresh authentication state (temporarily disabled)
// Route::get('/refresh-auth', function () {
//     return Inertia::render('Auth/RefreshAuth', [
//         'auth' => [
//             'user' => null,
//         ],
//         'message' => 'Authentication state refreshed'
//     ]);
// })->name('refresh.auth');

// Simple direct logout link for testing (temporarily disabled)
// Route::get('/direct-logout', function () {
//     // Force logout
//     \Illuminate\Support\Facades\Auth::guard('web')->logout();

//     // Clear all session data
//     session()->flush();
//     session()->invalidate();
//     session()->regenerateToken();

//     // Clear all possible cookies
//     $cookies = [
//         cookie()->forget('daily_games_platform_session'),
//         cookie()->forget('XSRF-TOKEN'),
//         cookie()->forget('laravel_session'),
//     ];

//     $response = redirect('/')->withHeaders([
//         'Cache-Control' => 'no-cache, no-store, must-revalidate',
//         'Pragma' => 'no-cache',
//         'Expires' => '0'
//     ]);

//     // Add all cookies to the response
//     foreach ($cookies as $cookie) {
//         if ($cookie) {
//             $response->withCookie($cookie);
//         }
//     }

//     return $response;
// })->name('direct.logout');

// Simple logout that forces complete browser refresh (temporarily disabled)
// Route::get('/simple-logout', function () {
//     // Force logout
//     \Illuminate\Support\Facades\Auth::guard('web')->logout();

//     // Clear all session data
//     session()->flush();
//     session()->invalidate();
//     session()->regenerateToken();

//     // Return a simple HTML page that forces complete refresh
//     return response('
//     <!DOCTYPE html>
//     <html>
//     <head>
//         <title>Logging out...</title>
//         <script>
//             // Clear everything
//             localStorage.clear();
//             sessionStorage.clear();

//             // Clear all cookies
//             document.cookie.split(";").forEach(function(c) {
//                 document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
//             });

//             // Force complete page reload to home
//             window.location.replace("/");
//         </script>
//     </head>
//     <body>
//         <p>Logging out...</p>
//     </body>
//     </html>
//     ')->withHeaders([
//         'Cache-Control' => 'no-cache, no-store, must-revalidate',
//         'Pragma' => 'no-cache',
//         'Expires' => '0'
//     ]);
// })->name('simple.logout');

// Diagnostic page to check authentication state (temporarily disabled)
// Route::get('/auth-debug', function () {
//     $user = \Illuminate\Support\Facades\Auth::user();
//     $authenticated = \Illuminate\Support\Facades\Auth::check();
//     $sessionId = session()->getId();
//     $sessionData = session()->all();

//     return response('
//     <!DOCTYPE html>
//     <html>
//     <head>
//         <title>Auth Debug</title>
//         <style>
//             body { font-family: Arial, sans-serif; margin: 20px; }
//             .section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
//             .backend { background: #f0f0f0; }
//             .frontend { background: #e0f0e0; }
//         </style>
//     </head>
//     <body>
//         <h1>Authentication Debug</h1>

//         <div class="section backend">
//             <h2>Backend State</h2>
//             <p><strong>Authenticated:</strong> ' . ($authenticated ? 'YES' : 'NO') . '</p>
//             <p><strong>User:</strong> ' . ($user ? $user->name . ' (' . $user->email . ')' : 'NULL') . '</p>
//             <p><strong>Session ID:</strong> ' . $sessionId . '</p>
//             <p><strong>Session Data:</strong> <pre>' . json_encode($sessionData, JSON_PRETTY_PRINT) . '</pre></p>
//         </div>

//         <div class="section frontend">
//             <h2>Frontend State</h2>
//             <p><strong>localStorage:</strong> <span id="localStorage"></span></p>
//             <p><strong>sessionStorage:</strong> <span id="sessionStorage"></span></p>
//             <p><strong>Cookies:</strong> <span id="cookies"></span></p>
//             <p><strong>Inertia User:</strong> <span id="inertiaUser"></span></p>
//         </div>

//         <div class="section">
//             <h2>Actions</h2>
//             <button onclick="clearEverything()">Clear Everything</button>
//             <button onclick="window.location.reload()">Reload Page</button>
//             <button onclick="window.location.href=\'/\'">Go Home</button>
//         </div>

//         <script>
//             // Display frontend state
//             document.getElementById("localStorage").textContent = JSON.stringify(localStorage);
//             document.getElementById("sessionStorage").textContent = JSON.stringify(sessionStorage);
//             document.getElementById("cookies").textContent = document.cookie;

//             // Try to get Inertia user data
//             try {
//                 const app = document.getElementById("app");
//                 <span id="inertiaUser"></span>
//             } catch (e) {
//                 document.getElementById("inertiaUser").textContent = "Error: " + e.message;
//             }

//             function clearEverything() {
//                 localStorage.clear();
//                 sessionStorage.clear();
//                 document.cookie.split(";").forEach(function(c) {
//                     document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
//                 });
//                 window.location.reload();
//             }
//         </script>
//     </body>
//     </html>
//     ');
// })->name('auth.debug');

// Force Inertia to refresh authentication state (temporarily disabled)
// Route::get('/force-auth-refresh', function () {
//     // Force logout on backend
//     \Illuminate\Support\Facades\Auth::guard('web')->logout();
//     session()->flush();
//     session()->invalidate();
//     session()->regenerateToken();

//     // Return Inertia response with null user
//     return Inertia::render('Auth/ForceRefresh', [
//         'auth' => [
//             'user' => null,
//         ],
//         'message' => 'Authentication state forced to refresh'
//     ]);
// })->name('force.auth.refresh');

require __DIR__.'/auth.php';
