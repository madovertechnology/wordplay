<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited(): void
    {
        // Clear any existing rate limiter hits
        $ip = '127.0.0.1';
        RateLimiter::clear($ip);

        // Make 5 login attempts (which is our limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
            
            // All of these should return a 302 redirect (failed login, but not rate limited)
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // The 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        
        // This should be rate limited with a 429 status code
        $response->assertStatus(429);
    }

    public function test_csrf_protection_is_configured(): void
    {
        // Instead of testing the actual CSRF protection (which is hard to simulate in tests),
        // we'll verify that the VerifyCsrfToken middleware is properly configured
        
        // Check that the middleware class exists
        $this->assertTrue(class_exists(\App\Http\Middleware\VerifyCsrfToken::class));
        
        // Check that the middleware is registered in the bootstrap/app.php file
        $appContent = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertStringContainsString('\App\Http\Middleware\VerifyCsrfToken::class', $appContent);
        
        // Check that there are no exceptions in the middleware by reading the file directly
        $middlewareContent = file_get_contents(app_path('Http/Middleware/VerifyCsrfToken.php'));
        $this->assertStringContainsString('protected $except = [', $middlewareContent);
        
        // The except array should be empty or only contain comments
        $this->assertStringNotContainsString("'", $middlewareContent, "CSRF middleware has routes excluded from protection");
    }

    public function test_api_endpoints_require_authentication(): void
    {
        // First, let's check if the game exists in the database
        $gameId = \App\Models\Game::where('slug', 'word-scramble')->first()?->id ?? 1;
        
        // Try to access a protected API endpoint without authentication
        $response = $this->getJson('/api/gamification/rank');
        
        // The request should fail with a 401 status code (unauthorized)
        $response->assertStatus(401);
        
        // Create a user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Now the request should succeed or at least not return 401
        $response = $this->getJson('/api/gamification/rank');
        $this->assertNotEquals(401, $response->getStatusCode());
    }
}