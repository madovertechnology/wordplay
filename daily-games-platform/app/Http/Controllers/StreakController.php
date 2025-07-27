<?php

namespace App\Http\Controllers;

use App\Http\Requests\Streak\GetTopStreaksRequest;
use App\Models\Game;
use App\Services\Core\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StreakController extends Controller
{
    /**
     * The streak service instance.
     *
     * @var StreakService
     */
    protected $streakService;

    /**
     * Create a new controller instance.
     *
     * @param StreakService $streakService
     * @return void
     */
    public function __construct(StreakService $streakService)
    {
        $this->streakService = $streakService;
    }

    /**
     * Get the user's streak for a game.
     *
     * @param Request $request
     * @param Game $game
     * @return JsonResponse
     */
    public function getUserStreak(Request $request, Game $game): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'streak' => null,
            ]);
        }

        $streak = $this->streakService->getUserStreak($game, $user);

        return response()->json([
            'streak' => $streak,
        ]);
    }

    /**
     * Get the top streaks for a game.
     *
     * @param Request $request
     * @param Game $game
     * @return JsonResponse
     */
    public function getTopStreaks(Request $request, Game $game): JsonResponse
    {
        $limit = $request->input('limit');

        $streaks = $this->streakService->getTopStreaks($game, $limit);

        return response()->json([
            'streaks' => $streaks,
        ]);
    }
}
