<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\Core\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    /**
     * The leaderboard service instance.
     *
     * @var LeaderboardService
     */
    protected $leaderboardService;

    /**
     * Create a new controller instance.
     *
     * @param LeaderboardService $leaderboardService
     * @return void
     */
    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Display the leaderboard for a game.
     *
     * @param Game $game
     * @param string $period
     * @return Response
     */
    public function show(Game $game, string $period = 'daily'): Response
    {
        $user = Auth::user();
        $date = now()->toDateString();
        $yearMonth = now()->format('Y-m');
        
        $leaderboard = [];
        $userRank = null;
        
        switch ($period) {
            case 'daily':
                $leaderboard = $this->leaderboardService->getDailyLeaderboard($game, 100, $date);
                if ($user) {
                    $userRank = $this->leaderboardService->getUserRank($game, $user, 'daily', $date);
                }
                break;
                
            case 'monthly':
                $leaderboard = $this->leaderboardService->getMonthlyLeaderboard($game, 100, $yearMonth);
                if ($user) {
                    $userRank = $this->leaderboardService->getUserRank($game, $user, 'monthly', $yearMonth);
                }
                break;
                
            case 'all-time':
                $leaderboard = $this->leaderboardService->getAllTimeLeaderboard($game, 100);
                if ($user) {
                    $userRank = $this->leaderboardService->getUserRank($game, $user, 'all_time', null);
                }
                break;
        }
        
        return Inertia::render('Leaderboard', [
            'game' => $game,
            'period' => $period,
            'leaderboard' => $leaderboard,
            'userRank' => $userRank,
        ]);
    }

    /**
     * Get the leaderboard data for a game via API.
     *
     * @param \App\Http\Requests\Leaderboard\GetLeaderboardRequest $request
     * @param Game $game
     * @param string $period
     * @return JsonResponse
     */
    public function getLeaderboard(\App\Http\Requests\Leaderboard\GetLeaderboardRequest $request, Game $game, string $period = 'daily'): JsonResponse
    {
        $limit = $request->input('limit');
        $date = $request->input('date');
        $yearMonth = $request->input('yearMonth');
        
        $leaderboard = [];
        
        switch ($period) {
            case 'daily':
                $leaderboard = $this->leaderboardService->getDailyLeaderboard($game, $limit, $date);
                break;
                
            case 'monthly':
                $leaderboard = $this->leaderboardService->getMonthlyLeaderboard($game, $limit, $yearMonth);
                break;
                
            case 'all-time':
                $leaderboard = $this->leaderboardService->getAllTimeLeaderboard($game, $limit);
                break;
        }
        
        return response()->json([
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Get the user's rank for a game.
     *
     * @param \App\Http\Requests\Leaderboard\GetUserRankRequest $request
     * @param Game $game
     * @param string $period
     * @return JsonResponse
     */
    public function getUserRank(\App\Http\Requests\Leaderboard\GetUserRankRequest $request, Game $game, string $period = 'daily'): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'userRank' => null,
            ]);
        }
        
        $date = $request->input('date');
        $yearMonth = $request->input('yearMonth');
        
        $userRank = null;
        
        switch ($period) {
            case 'daily':
                $userRank = $this->leaderboardService->getUserRank($game, $user, 'daily', $date);
                break;
                
            case 'monthly':
                $userRank = $this->leaderboardService->getUserRank($game, $user, 'monthly', $yearMonth);
                break;
                
            case 'all-time':
                $userRank = $this->leaderboardService->getUserRank($game, $user, 'all_time', null);
                break;
        }
        
        return response()->json([
            'userRank' => $userRank,
        ]);
    }
}
