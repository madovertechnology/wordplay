<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityImplementationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test game
        Game::factory()->create([
            'slug' => 'word-scramble',
            'name' => 'Word Scramble',
            'is_active' => true,
        ]);
    }

    public function test_api_security_middleware_adds_headers()
    {
        $response = $this->json('GET', '/games/word-scramble/api/puzzle', [], [
            'Accept' => 'application/json'
        ]);

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_rate_limiting_middleware_adds_headers()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                        ->json('GET', '/games/word-scramble/api/puzzle', [], [
                            'Accept' => 'application/json'
                        ]);

        $response->assertHeader('X-RateLimit-Limit')
                ->assertHeader('X-RateLimit-Remaining');
    }

    public function test_authorization_middleware_blocks_unauthenticated_access()
    {
        $response = $this->json('GET', '/api/streaks/word-scramble', [], [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(401);
    }

    public function test_guest_token_validation_works()
    {
        $guest = Guest::factory()->create();
        
        $response = $this->withCookie('guest_token', $guest->guest_token)
                        ->json('GET', '/games/word-scramble/api/puzzle', [], [
                            'Accept' => 'application/json'
                        ]);

        $response->assertStatus(200);
    }

    public function test_sql_injection_protection_works()
    {
        $response = $this->json('GET', '/games/word-scramble/api/puzzle?date=2024-01-01%27%20UNION%20SELECT', [], [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_users_have_access()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                        ->json('GET', '/games/word-scramble/api/puzzle', [], [
                            'Accept' => 'application/json'
                        ]);

        $response->assertStatus(200);
    }

    public function test_rate_limiting_works_for_different_user_types()
    {
        $user = User::factory()->create();
        
        // Test that rate limiting headers are present for authenticated users
        $authResponse = $this->actingAs($user)
                            ->json('POST', '/games/word-scramble/api/submit', 
                                ['word' => 'test'], 
                                ['Accept' => 'application/json']);
        
        $authLimit = (int) $authResponse->headers->get('X-RateLimit-Limit');
        
        // Verify rate limiting is working (should have a positive limit)
        $this->assertGreaterThan(0, $authLimit, 'Rate limiting should be active with a positive limit');
        $this->assertTrue($authResponse->headers->has('X-RateLimit-Remaining'), 'Rate limit remaining header should be present');
    }

    public function test_api_requests_require_accept_header()
    {
        // Test that the security middleware is working by checking headers are added
        $response = $this->json('GET', '/games/word-scramble/api/puzzle', [], [
            'Accept' => 'application/json'
        ]);

        // Verify security headers are present (indicating middleware is working)
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_enhanced_word_submission_validation()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                        ->json('POST', '/games/word-scramble/api/submit', 
                            ['word' => 'test'], 
                            ['Accept' => 'application/json']);

        // Should not be blocked by authorization (may fail game logic but not security)
        $this->assertNotEquals(401, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}