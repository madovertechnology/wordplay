<?php

namespace App\Http\Middleware;

use App\Services\User\GuestService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HandleGuestUsers
{
    /**
     * The guest service instance.
     *
     * @var \App\Services\User\GuestService
     */
    protected $guestService;

    /**
     * Create a new middleware instance.
     *
     * @param  \App\Services\User\GuestService  $guestService
     * @return void
     */
    public function __construct(GuestService $guestService)
    {
        $this->guestService = $guestService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Process the request first
        $response = $next($request);
        
        // Only handle guest users if the user is not authenticated
        if (!Auth::check()) {
            $guest = $this->guestService->getOrCreateGuest($request);
            
            // Share the guest with the view
            if ($guest) {
                $request->attributes->set('guest', $guest);
            }
        }

        return $response;
    }
}
