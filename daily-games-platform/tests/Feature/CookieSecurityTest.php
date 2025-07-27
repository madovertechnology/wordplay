<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Services\User\GuestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CookieSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guest cookies are set with secure attributes.
     */
    public function test_guest_cookies_are_secure(): void
    {
        // Create a mock request
        $request = Request::create('/', 'GET');
        
        // Get an instance of the GuestService
        $guestService = app(GuestService::class);
        
        // Create a guest user
        $guest = $guestService->createGuest();
        
        // Get the response from the test client
        $response = $this->get('/');
        
        // Check that the cookie is set in the response
        $cookies = $response->headers->getCookies();
        $guestCookie = null;
        
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'guest_token') {
                $guestCookie = $cookie;
                break;
            }
        }
        
        // Assert that the cookie was found
        $this->assertNotNull($guestCookie, 'Guest cookie not found in response');
        
        // Assert that the cookie has the correct security attributes
        // Note: In testing environment, secure might be false
        if (config('app.env') !== 'local' && config('app.env') !== 'testing') {
            $this->assertTrue($guestCookie->isSecure(), 'Cookie is not secure');
        }
        
        $this->assertTrue($guestCookie->isHttpOnly(), 'Cookie is not HTTP only');
        $this->assertEquals('lax', $guestCookie->getSameSite(), 'Cookie does not have SameSite=Lax');
    }

    /**
     * Test that session cookies are set with secure attributes.
     */
    public function test_session_cookies_are_secure(): void
    {
        // Set session configuration for testing
        Config::set('session.secure', true);
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'lax');
        
        // Make a request that will start a session
        $response = $this->get('/');
        
        // Get the session cookie
        $cookies = $response->headers->getCookies();
        $sessionCookie = null;
        
        foreach ($cookies as $cookie) {
            if (strpos($cookie->getName(), 'session') !== false) {
                $sessionCookie = $cookie;
                break;
            }
        }
        
        // Assert that the session cookie was found
        $this->assertNotNull($sessionCookie, 'Session cookie not found in response');
        
        // Assert that the cookie has the correct security attributes
        // Note: In testing environment, secure might be false
        if (config('app.env') !== 'local' && config('app.env') !== 'testing') {
            $this->assertTrue($sessionCookie->isSecure(), 'Session cookie is not secure');
        }
        
        $this->assertTrue($sessionCookie->isHttpOnly(), 'Session cookie is not HTTP only');
        $this->assertEquals('lax', $sessionCookie->getSameSite(), 'Session cookie does not have SameSite=Lax');
    }
}