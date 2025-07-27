<?php

namespace Tests\Feature\Auth;

use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the social authentication redirect.
     */
    public function test_social_authentication_redirect(): void
    {
        $response = $this->get(route('social.redirect', ['provider' => 'google']));

        $response->assertRedirect();
    }

    /**
     * Test the social authentication callback with a new user.
     */
    public function test_social_authentication_callback_with_new_user(): void
    {
        // Mock the Socialite facade
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')
            ->andReturn('123456789')
            ->shouldReceive('getName')
            ->andReturn('Test User')
            ->shouldReceive('getEmail')
            ->andReturn('test@example.com')
            ->shouldReceive('getAvatar')
            ->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::mock()->shouldReceive('user')
                ->andReturn($abstractUser)
                ->getMock());

        // Make the request
        $response = $this->get(route('social.callback', ['provider' => 'google']));

        // Assert that a new user was created
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'provider' => 'google',
            'provider_id' => '123456789',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        // Assert that the user is authenticated
        $this->assertAuthenticated();

        // Assert that the user is redirected to the dashboard
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * Test the social authentication callback with an existing user.
     */
    public function test_social_authentication_callback_with_existing_user(): void
    {
        // Create an existing user
        $user = User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('password'),
            'provider' => 'google',
            'provider_id' => '987654321',
        ]);

        // Mock the Socialite facade
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')
            ->andReturn('987654321')
            ->shouldReceive('getName')
            ->andReturn('Existing User')
            ->shouldReceive('getEmail')
            ->andReturn('existing@example.com')
            ->shouldReceive('getAvatar')
            ->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::mock()->shouldReceive('user')
                ->andReturn($abstractUser)
                ->getMock());

        // Make the request
        $response = $this->get(route('social.callback', ['provider' => 'google']));

        // Assert that the user is authenticated
        $this->assertAuthenticated();

        // Assert that the user is redirected to the dashboard
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * Test the social authentication callback with guest data transfer.
     */
    public function test_social_authentication_callback_with_guest_data_transfer(): void
    {
        // Create a guest user
        $guestToken = \Illuminate\Support\Str::uuid();
        $guest = Guest::create(['guest_token' => $guestToken]);
        
        // Add some test data for the guest
        $guest->storeData('game_test', ['score' => 100]);
        
        // Set the guest token cookie
        Cookie::queue('guest_token', $guestToken, 43200);

        // Mock the Socialite facade
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')
            ->andReturn('123456789')
            ->shouldReceive('getName')
            ->andReturn('Test User')
            ->shouldReceive('getEmail')
            ->andReturn('test@example.com')
            ->shouldReceive('getAvatar')
            ->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::mock()->shouldReceive('user')
                ->andReturn($abstractUser)
                ->getMock());

        // Make the request
        $response = $this->withCookie('guest_token', $guestToken)
            ->get(route('social.callback', ['provider' => 'google']));

        // Assert that a new user was created
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'provider' => 'google',
            'provider_id' => '123456789',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        // Assert that the user is authenticated
        $this->assertAuthenticated();

        // Assert that the user is redirected to the dashboard
        $response->assertRedirect(route('dashboard'));
        
        // The guest cookie should be cleared
        $this->assertTrue(Cookie::hasQueued('guest_token'));
    }
}
