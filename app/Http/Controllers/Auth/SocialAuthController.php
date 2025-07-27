<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\User\GuestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the provider authentication page.
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the provider callback.
     *
     * @param string $provider
     * @param GuestService $guestService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(string $provider, GuestService $guestService): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            
            // Check if the user already exists
            $user = User::where('email', $socialUser->getEmail())->first();
            
            if ($user) {
                // Update provider details if they don't match
                if ($user->provider !== $provider || $user->provider_id !== $socialUser->getId()) {
                    $user->update([
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'avatar' => $socialUser->getAvatar(),
                    ]);
                }
            } else {
                // Create a new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(\Illuminate\Support\Str::random(24)), // Random password as it won't be used
                    'avatar' => $socialUser->getAvatar(),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
                
                // Transfer guest data if available
                $guest = $guestService->getGuestFromRequest(request());
                if ($guest) {
                    $guestService->transferDataToUser($guest, $user);
                }
            }
            
            // Log in the user
            Auth::login($user);
            
            return redirect()->route('dashboard');
            
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'An error occurred during social login. Please try again.',
            ]);
        }
    }
}
