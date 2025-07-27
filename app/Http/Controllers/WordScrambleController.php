<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Services\Game\WordScrambleGameService;
use App\Services\User\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WordScrambleController extends Controller
{
    /**
     * The Word Scramble game service instance.
     *
     * @var WordScrambleGameService
     */
    protected $gameService;

    /**
     * The guest service instance.
     *
     * @var GuestService
     */
    protected $guestService;

    /**
     * Create a new controller instance.
     *
     * @param WordScrambleGameService $gameService
     * @param GuestService $guestService
     */
    public function __construct(WordScrambleGameService $gameService, GuestService $guestService)
    {
        $this->gameService = $gameService;
        $this->guestService = $guestService;
    }

    /**
     * Show the Word Scramble game page.
     *
     * @return \Inertia\Response
     */
    public function show()
    {
        $puzzle = $this->gameService->getTodaysPuzzle();
        
        return Inertia::render('Games/WordScramble', [
            'puzzle' => $puzzle,
        ]);
    }

    /**
     * Get today's puzzle.
     *
     * @param \App\Http\Requests\WordScramble\GetTodaysPuzzleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTodaysPuzzle(\App\Http\Requests\WordScramble\GetTodaysPuzzleRequest $request)
    {
        $date = $request->input('date');
        $puzzle = $this->gameService->getTodaysPuzzle($date);
        
        return response()->json($puzzle);
    }

    /**
     * Submit a word.
     *
     * @param \App\Http\Requests\WordScramble\SubmitWordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitWord(\App\Http\Requests\WordScramble\SubmitWordRequest $request)
    {
        $word = $request->input('word');
        
        try {
            if (Auth::check()) {
                // User is authenticated
                $user = Auth::user();
                $result = $this->gameService->submitWordForUser($user, $word);
            } else {
                // User is a guest - create or get guest token
                $guest = $this->guestService->getOrCreateGuest($request);
                $result = $this->gameService->submitWordForGuest($guest, $word);
            }
            
            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Error submitting word', [
                'word' => $word,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting the word. Please try again.'
            ], 500);
        }
    }

    /**
     * Get user submissions.
     *
     * @param \App\Http\Requests\WordScramble\GetSubmissionsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubmissions(\App\Http\Requests\WordScramble\GetSubmissionsRequest $request)
    {
        $date = $request->input('date');
        
        try {
            if (Auth::check()) {
                // User is authenticated
                $user = Auth::user();
                $submissions = $this->gameService->getUserSubmissions($user, $date);
            } else {
                // User is a guest - try to get existing guest, don't create new one
                $guest = $this->guestService->getGuestFromRequest($request);
                if ($guest) {
                    $submissions = $this->gameService->getGuestSubmissions($guest, $date);
                } else {
                    // No guest token found, return empty submissions
                    $submissions = [
                        'success' => true,
                        'submissions' => [],
                        'total_score' => 0
                    ];
                }
            }
            
            return response()->json($submissions);
        } catch (\Exception $e) {
            \Log::error('Error getting submissions', [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading submissions.'
            ], 500);
        }
    }

    /**
     * Get daily leaderboard.
     *
     * @param \App\Http\Requests\WordScramble\GetLeaderboardRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDailyLeaderboard(\App\Http\Requests\WordScramble\GetLeaderboardRequest $request)
    {
        $game = $this->gameService->getGameBySlug('word-scramble');
        $limit = (int) $request->input('limit', 10);
        
        $leaderboard = $this->gameService->getDailyLeaderboard($game, $limit);
        
        return response()->json($leaderboard);
    }

    /**
     * Get monthly leaderboard.
     *
     * @param \App\Http\Requests\WordScramble\GetLeaderboardRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonthlyLeaderboard(\App\Http\Requests\WordScramble\GetLeaderboardRequest $request)
    {
        $game = $this->gameService->getGameBySlug('word-scramble');
        $limit = (int) $request->input('limit', 10);
        
        $leaderboard = $this->gameService->getMonthlyLeaderboard($game, $limit);
        
        return response()->json($leaderboard);
    }

    /**
     * Get all-time leaderboard.
     *
     * @param \App\Http\Requests\WordScramble\GetLeaderboardRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllTimeLeaderboard(\App\Http\Requests\WordScramble\GetLeaderboardRequest $request)
    {
        $game = $this->gameService->getGameBySlug('word-scramble');
        $limit = (int) $request->input('limit', 10);
        
        $leaderboard = $this->gameService->getAllTimeLeaderboard($game, $limit);
        
        return response()->json($leaderboard);
    }
}