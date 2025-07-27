<?php

namespace App\Services\User;

use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class GuestService
{
    /**
     * The cookie name for storing the guest token.
     *
     * @var string
     */
    protected $cookieName = 'guest_token';

    /**
     * The cookie lifetime in minutes.
     *
     * @var int
     */
    protected $cookieLifetime = 43200; // 30 days

    /**
     * Get or create a guest user.
     *
     * @param Request $request
     * @return Guest
     */
    public function getOrCreateGuest(Request $request): Guest
    {
        $guestToken = $request->cookie($this->cookieName);

        if ($guestToken) {
            $guest = Guest::where('guest_token', $guestToken)->first();

            if ($guest) {
                return $guest;
            }
        }

        return $this->createGuest();
    }

    /**
     * Create a new guest user.
     *
     * @return Guest
     */
    public function createGuest(): Guest
    {
        $guestToken = Str::uuid();

        $guest = Guest::create([
            'guest_token' => $guestToken,
        ]);

        Cookie::queue(
            Cookie::make(
                $this->cookieName,
                $guestToken,
                $this->cookieLifetime,
                '/',
                null,
                config('app.env') !== 'local', // Secure only in non-local environments
                true, // HTTP Only
                false, // Raw
                'lax' // SameSite
            )
        );

        return $guest;
    }

    /**
     * Transfer guest data to a user.
     *
     * @param Guest $guest
     * @param User $user
     * @return void
     */
    public function transferDataToUser(Guest $guest, User $user): void
    {
        // Transfer game progress
        $gameData = $guest->data()->where('key', 'like', 'game_%')->get();

        foreach ($gameData as $data) {
            // Process game data and associate it with the user
            // This will be implemented based on specific game requirements
        }

        // Transfer streaks
        $streakData = $guest->data()->where('key', 'like', 'streak_%')->get();

        foreach ($streakData as $data) {
            // Process streak data and associate it with the user
            // This will be implemented based on specific game requirements
        }

        // After transferring all data, we can delete the guest
        // $guest->delete();

        // Or we can keep the guest but clear the cookie
        Cookie::queue(
            Cookie::make(
                $this->cookieName,
                '',
                -2628000, // Expire immediately (negative value)
                '/',
                null,
                config('app.env') !== 'local', // Secure only in non-local environments
                true, // HTTP Only
                false, // Raw
                'lax' // SameSite
            )
        );
    }

    /**
     * Check if the request has a valid guest token.
     *
     * @param Request $request
     * @return bool
     */
    public function hasValidGuestToken(Request $request): bool
    {
        $guestToken = $request->cookie($this->cookieName);

        if (!$guestToken) {
            return false;
        }

        return Guest::where('guest_token', $guestToken)->exists();
    }

    /**
     * Get the guest from the request.
     *
     * @param Request $request
     * @return Guest|null
     */
    public function getGuestFromRequest(Request $request): ?Guest
    {
        $guestToken = $request->cookie($this->cookieName);

        if (!$guestToken) {
            return null;
        }

        return Guest::where('guest_token', $guestToken)->first();
    }
}
