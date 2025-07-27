<?php

namespace App\Http\Controllers;

use App\Services\Core\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GamificationController extends Controller
{
    /**
     * The gamification service instance.
     *
     * @var GamificationService
     */
    protected $gamificationService;

    /**
     * Create a new controller instance.
     *
     * @param GamificationService $gamificationService
     * @return void
     */
    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
        $this->middleware('auth');
    }

    /**
     * Get the authenticated user's rank.
     *
     * @return JsonResponse
     */
    public function getUserRank(): JsonResponse
    {
        $user = Auth::user();
        $rank = $this->gamificationService->getUserRank($user);
        
        return response()->json([
            'rank' => $rank,
        ]);
    }

    /**
     * Get the authenticated user's badges.
     *
     * @return JsonResponse
     */
    public function getUserBadges(): JsonResponse
    {
        $user = Auth::user();
        $badges = $this->gamificationService->getUserBadges($user);
        
        return response()->json([
            'badges' => $badges,
        ]);
    }

    /**
     * Check for new achievements and return any newly awarded badges.
     *
     * @return JsonResponse
     */
    public function checkAchievements(): JsonResponse
    {
        $user = Auth::user();
        
        // Update user rank
        $rank = $this->gamificationService->updateUserRank($user);
        
        // Check and award badges
        $newBadges = $this->gamificationService->checkAndAwardBadges($user);
        
        return response()->json([
            'rank' => $rank ? [
                'id' => $rank->id,
                'name' => $rank->name,
                'icon' => $rank->icon,
            ] : null,
            'new_badges' => $newBadges,
        ]);
    }
}
