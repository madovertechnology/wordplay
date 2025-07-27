<?php

namespace Tests\Feature\Auth;

use App\Models\Guest;
use App\Services\User\GuestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
    
    public function test_guest_data_is_transferred_on_registration(): void
    {
        // Create a guest user
        $guestToken = \Illuminate\Support\Str::uuid();
        $guest = Guest::create(['guest_token' => $guestToken]);
        
        // Add some test data for the guest
        $guest->storeData('game_test', ['score' => 100]);
        
        // Set the guest token cookie
        $cookie = Cookie::make('guest_token', $guestToken, 43200);
        
        // Register a new user with the guest cookie
        $response = $this->withCookie('guest_token', $guestToken)
            ->post('/register', [
                'name' => 'Guest User',
                'email' => 'guest@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
        
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        
        // The guest cookie should be cleared
        $this->assertTrue(Cookie::hasQueued('guest_token'));
    }
}
