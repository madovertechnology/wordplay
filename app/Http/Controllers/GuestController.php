<?php

namespace App\Http\Controllers;

use App\Services\User\GuestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    /**
     * The guest service instance.
     *
     * @var \App\Services\User\GuestService
     */
    protected $guestService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\User\GuestService  $guestService
     * @return void
     */
    public function __construct(GuestService $guestService)
    {
        $this->guestService = $guestService;
    }

    /**
     * Store guest data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeData(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required',
        ]);

        $guest = $this->guestService->getGuestFromRequest($request);
        
        if (!$guest) {
            $guest = $this->guestService->createGuest();
        }
        
        $guest->storeData($request->key, $request->value);
        
        return response()->json(['success' => true]);
    }

    /**
     * Get guest data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request, string $key): JsonResponse
    {
        $guest = $this->guestService->getGuestFromRequest($request);
        
        if (!$guest) {
            return response()->json(['data' => null]);
        }
        
        $data = $guest->getData($key);
        
        return response()->json(['data' => $data]);
    }

    /**
     * Clear guest data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearData(Request $request): JsonResponse
    {
        $guest = $this->guestService->getGuestFromRequest($request);
        
        if ($guest) {
            // Delete all guest data
            $guest->data()->delete();
        }
        
        return response()->json(['success' => true]);
    }
}
