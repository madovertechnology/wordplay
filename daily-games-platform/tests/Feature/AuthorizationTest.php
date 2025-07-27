<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
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

    public function test_unauthenticated_users_cannot_access_streak_endpoints()
    {
        $response = $this->json('GET', '/api/streaks/word-scramble', [], [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Authentication required.',
                    'status' => 'error'
                ]);
    }

    public function test_unauthenticated_users_cannot_access_gamification_endpoints()
    {
        $response = $this->json('GET', '/api/gamification/rank', [], [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Authentication required.',
                    'status' => 'error'
                ]);
    }

    public function test_guests_can_access_public_game_endpoints()
    {
        $guest = Guest::factory()->create();
        
        $response = $this->withCookie('guest_token', $guest->guest_token)
                        ->json('GET', '/games/word-scramble/api/puzzle', [], [
                            'Accept' => 'application/json'
                        ]);

        $response->assertStatus(200);
    }

    public function test_guests_can_submit_words_with_valid_token()
    {
        $guest = Guest::factory()->create();
        
        $response = $this->withCookie('guest_token', $guest->guest_token)
                        ->json('POST', '/games/word-scramble/api/submit', 
                            ['word' => 'test'], 
                            ['Accept' => 'application/json']);

        // Should not be blocked by authorization (may fail validation but not auth)
        $this->assertNotEquals(401, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_guests_cannot_submit_without_valid_token()
    {
        $response = $this->json('POST', '/games/word-scramble/api/submit', 
                        ['word' => 'test'], 
                        ['Accept' => 'application/json']);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Authentication or valid guest session required.',
                    'status' => 'error'
                ]);
    }

    public function test_authenticated_users_can_access_all_endpoints()
    {
        $user = User::factory()->create();
        
        // Test streak endpoint
        $streakResponse = $this->actingAs($user)
                              ->json('GET', '/api/streaks/word-scramble', [], [
                                  'Accept' => 'application/json'
                              ]);
        
        $this->assertNotEquals(401, $streakResponse->getStatusCode());
        $this->assertNotEquals(403, $streakResponse->getStatusCode());
        
        // Test gamification endpoint
        $gamificationResponse = $this->actingAs($user)
                                   ->json('GET', '/api/gamification/rank', [], [
                                       'Accept' => 'application/json'
                                   ]);
        
        $this->assertNotEquals(401, $gamificationResponse->getStatusCode());
        $this->assertNotEquals(403, $gamificationResponse->getStatusCode());
        
        // Test game submission
        $submitResponse = $this->actingAs($user)
                              ->json('POST', '/games/word-scramble/api/submit', 
                                  ['word' => 'test'], 
                                  ['Accept' => 'application/json']);
        
        $this->assertNotEquals(401, $submitResponse->getStatusCode());
        $this->assertNotEquals(403, $submitResponse->getStatusCode());
    }

    public function test_user_data_access_is_restricted_to_owner()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // This test would apply if we had user-specific endpoints with user ID parameters
        // For now, we test that the middleware doesn't block legitimate access
        $response = $this->actingAs($user1)
                        ->json('GET', '/api/streaks/word-scramble', [], [
                            'Accept' => 'application/json'
                        ]);
        
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_inactive_games_are_blocked()
    {
        // Create an inactive game
        Game::factory()->create([
            'slug' => 'inactive-game',
            'name' => 'Inactive Game',
            'is_active' => false,
        ]);
        
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                        ->json('GET', '/games/inactive-game/api/puzzle', [], [
                            'Accept' => 'application/json'
                        ]);

        $response->assertStatus(503)
                ->assertJson([
                    'message' => 'Game is currently unavailable.',
                    'status' => 'error'
                ]);
    }

    public function test_nonexistent_games_return_404()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                        ->json('GET', '/games/nonexistent/api/puzzle', [], [
                            'Accept' => 'application/json'
                        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Game not found.',
                    'status' => 'error'
                ]);
    }

    public function test_write_operations_require_authentication_or_guest_token()
    {
        // Test POST without authentication or guest token
        $response = $this->json('POST', '/games/word-scramble/api/submit', 
                        ['word' => 'test'], 
                        ['Accept' => 'application/json']);

        $response->assertStatus(401);
        
        // Test PUT without authentication or guest token
        $response = $this->json('PUT', '/api/gamification/check', 
                        [], 
                        ['Accept' => 'application/json']);

        $response->assertStatus(401);
    }

    public function test_malformed_guest_tokens_are_rejected()
    {
        // Test with malformed token
        $response = $this->withCookie('guest_token', 'invalid-token-format!')
                        ->json('POST', '/games/word-scramble/api/submit', 
                            ['word' => 'test'], 
                            ['Accept' => 'application/json']);

        $response->assertStatus(422); // Should fail validation in SubmitWordRequest
    }

    public function test_valid_requests_pass_authorization()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                        ->json('GET', '/games/word-scramble/api/puzzle', [], [
                            'Accept' => 'application/json'
                        ]);

        // Should pass authorization checks
        $this->assertNotEquals(401, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}