<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Services\User\GuestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

class GuestUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a guest user is created when visiting the site.
     */
    public function test_guest_user_is_created_when_visiting_site(): void
    {
        // Make a request to the site
        $response = $this->get('/');

        // Check that a guest_token cookie was set
        $this->assertTrue(Cookie::hasQueued('guest_token'));
        
        // Get the queued cookie value
        $cookieValue = Cookie::queued('guest_token')->getValue();
        
        // Check that a guest user was created in the database
        $this->assertDatabaseHas('guests', [
            'guest_token' => $cookieValue,
        ]);
    }
    
    /**
     * Test that guest data can be stored and retrieved.
     */
    public function test_guest_data_can_be_stored_and_retrieved(): void
    {
        // Create a guest user
        $guestToken = \Illuminate\Support\Str::uuid();
        $guest = Guest::create(['guest_token' => $guestToken]);
        
        // Store some test data
        $testData = ['score' => 100, 'level' => 5];
        $guest->storeData('game_test', $testData);
        
        // Retrieve the data
        $retrievedData = $guest->getData('game_test');
        
        // Check that the data was stored and retrieved correctly
        $this->assertEquals($testData, $retrievedData);
    }
    
    /**
     * Test that the guest service can get a guest from a request.
     */
    public function test_guest_service_can_get_guest_from_request(): void
    {
        // Create a guest user
        $guestToken = \Illuminate\Support\Str::uuid();
        $guest = Guest::create(['guest_token' => $guestToken]);
        
        // Create a request with the guest token cookie
        $request = request()->create('/');
        $request->cookies->set('guest_token', $guestToken);
        
        // Get the guest from the request
        $guestService = app(GuestService::class);
        $retrievedGuest = $guestService->getGuestFromRequest($request);
        
        // Check that the correct guest was retrieved
        $this->assertNotNull($retrievedGuest);
        $this->assertEquals($guest->id, $retrievedGuest->id);
    }
}
