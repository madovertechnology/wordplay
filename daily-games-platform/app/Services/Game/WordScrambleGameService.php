<?php

namespace App\Services\Game;

use App\Models\Game;
use App\Models\Guest;
use App\Models\User;
use App\Models\WordScramblePuzzle;
use App\Models\WordScrambleSubmission;
use App\Models\WordScrambleWord;
use App\Repositories\GameRepository;
use App\Services\Core\BaseGameService;
use App\Services\Core\GamificationService;
use App\Services\Core\LeaderboardService;
use App\Services\Core\StreakService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WordScrambleGameService extends BaseGameService
{
    /**
     * The dictionary service instance.
     *
     * @var DictionaryService
     */
    protected $dictionaryService;

    /**
     * The puzzle service instance.
     *
     * @var WordScramblePuzzleService
     */
    protected $puzzleService;

    /**
     * The streak service instance.
     *
     * @var StreakService
     */
    protected $streakService;

    /**
     * The leaderboard service instance.
     *
     * @var LeaderboardService
     */
    protected $leaderboardService;

    /**
     * The gamification service instance.
     *
     * @var GamificationService
     */
    protected $gamificationService;

    /**
     * Cache TTL in seconds (5 minutes).
     *
     * @var int
     */
    protected $cacheTtl = 300;

    /**
     * WordScrambleGameService constructor.
     *
     * @param GameRepository $gameRepository
     * @param DictionaryService $dictionaryService
     * @param WordScramblePuzzleService $puzzleService
     * @param StreakService $streakService
     * @param LeaderboardService $leaderboardService
     * @param GamificationService $gamificationService
     */
    public function __construct(
        GameRepository $gameRepository,
        DictionaryService $dictionaryService,
        WordScramblePuzzleService $puzzleService,
        StreakService $streakService,
        LeaderboardService $leaderboardService,
        GamificationService $gamificationService
    ) {
        parent::__construct($gameRepository);
        $this->dictionaryService = $dictionaryService;
        $this->puzzleService = $puzzleService;
        $this->streakService = $streakService;
        $this->leaderboardService = $leaderboardService;
        $this->gamificationService = $gamificationService;
    }

    /**
     * Get today's puzzle.
     *
     * @return array
     */
    public function getTodaysPuzzle(): array
    {
        $cacheKey = 'word_scramble.game.puzzle.today';

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            // Use the puzzle service's caching mechanism to get or generate today's puzzle
            $puzzle = $this->puzzleService->getPuzzleByDate();

            if (!$puzzle) {
                // Generate today's puzzle if it doesn't exist
                $puzzle = $this->puzzleService->generateDailyPuzzle();
            }

            return [
                'id' => $puzzle->id,
                'letters' => $puzzle->letters,
                'date' => $puzzle->date->toDateString(),
                'possible_words_count' => $puzzle->possible_words_count,
            ];
        });
    }

    /**
     * Submit a word for a user.
     *
     * @param User $user
     * @param string $word
     * @param string|null $date
     * @return array
     */
    public function submitWordForUser(User $user, string $word, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $puzzle = WordScramblePuzzle::forDate($date);

        if (!$puzzle) {
            return [
                'success' => false,
                'message' => 'No puzzle found for this date.',
            ];
        }

        // Normalize the word
        $word = strtolower(trim($word));

        // Check if the word has already been submitted by this user
        $existingSubmission = WordScrambleSubmission::where('puzzle_id', $puzzle->id)
            ->where('user_id', $user->id)
            ->where('word', $word)
            ->first();

        if ($existingSubmission) {
            return [
                'success' => false,
                'message' => 'You have already found this word.',
                'word' => $word,
            ];
        }

        // Check if the word is valid
        if (!$this->isValidWord($puzzle, $word)) {
            return [
                'success' => false,
                'message' => 'Invalid word.',
                'word' => $word,
            ];
        }

        // Get the score for this word
        $score = $this->getWordScore($puzzle, $word);

        // Create the submission
        $submission = WordScrambleSubmission::create([
            'puzzle_id' => $puzzle->id,
            'user_id' => $user->id,
            'word' => $word,
            'score' => $score,
        ]);

        // Get the game
        $game = $this->getGameBySlug('word-scramble');

        // Update streak
        $streak = $this->streakService->updateStreak($game, $user, $date);

        // Update leaderboard
        $this->leaderboardService->updateScore($game, $user, $score, $date);

        // Check for badges
        $awardedBadges = $this->gamificationService->checkAndAwardBadges($user);

        // Update user rank
        $rank = $this->gamificationService->updateUserRank($user);

        // Clear cache
        $this->clearUserSubmissionsCache($user->id, $puzzle->id);

        return [
            'success' => true,
            'message' => 'Word submitted successfully.',
            'word' => $word,
            'score' => $score,
            'total_score' => $puzzle->getUserTotalScore($user->id),
            'found_words_count' => $puzzle->getUserSubmissions($user->id)->count(),
            'streak' => [
                'current' => $streak->current_streak,
                'longest' => $streak->longest_streak,
            ],
            'awarded_badges' => count($awardedBadges) > 0 ? $awardedBadges : null,
            'rank' => $rank ? $rank->name : null,
        ];
    }

    /**
     * Submit a word for a guest.
     *
     * @param Guest $guest
     * @param string $word
     * @param string|null $date
     * @return array
     */
    public function submitWordForGuest(Guest $guest, string $word, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $puzzle = WordScramblePuzzle::forDate($date);

        if (!$puzzle) {
            return [
                'success' => false,
                'message' => 'No puzzle found for this date.',
            ];
        }

        // Normalize the word
        $word = strtolower(trim($word));

        // Check if the word has already been submitted by this guest
        $existingSubmission = WordScrambleSubmission::where('puzzle_id', $puzzle->id)
            ->where('guest_id', $guest->guest_token)
            ->where('word', $word)
            ->first();

        if ($existingSubmission) {
            return [
                'success' => false,
                'message' => 'You have already found this word.',
                'word' => $word,
            ];
        }

        // Check if the word is valid
        if (!$this->isValidWord($puzzle, $word)) {
            return [
                'success' => false,
                'message' => 'Invalid word.',
                'word' => $word,
            ];
        }

        // Get the score for this word
        $score = $this->getWordScore($puzzle, $word);

        // Create the submission
        $submission = WordScrambleSubmission::create([
            'puzzle_id' => $puzzle->id,
            'guest_id' => $guest->guest_token,
            'word' => $word,
            'score' => $score,
        ]);

        // Store guest data
        $totalScore = $puzzle->getGuestTotalScore($guest->guest_token);
        $foundWordsCount = $puzzle->getGuestSubmissions($guest->guest_token)->count();

        $guest->storeData('word_scramble_total_score', $totalScore);
        $guest->storeData('word_scramble_found_words_count', $foundWordsCount);

        // Clear cache
        $this->clearGuestSubmissionsCache($guest->guest_token, $puzzle->id);

        return [
            'success' => true,
            'message' => 'Word submitted successfully.',
            'word' => $word,
            'score' => $score,
            'total_score' => $totalScore,
            'found_words_count' => $foundWordsCount,
        ];
    }

    /**
     * Get user submissions for a puzzle.
     *
     * @param User $user
     * @param string|null $date
     * @return array
     */
    public function getUserSubmissions(User $user, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $puzzle = WordScramblePuzzle::forDate($date);

        if (!$puzzle) {
            return [
                'success' => false,
                'message' => 'No puzzle found for this date.',
            ];
        }

        $cacheKey = "word_scramble.submissions.user.{$user->id}.puzzle.{$puzzle->id}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user, $puzzle) {
            $submissions = $puzzle->getUserSubmissions($user->id);

            return [
                'success' => true,
                'submissions' => $submissions->map(function ($submission) {
                    return [
                        'word' => $submission->word,
                        'score' => $submission->score,
                        'submitted_at' => $submission->created_at->toDateTimeString(),
                    ];
                })->toArray(),
                'total_score' => $puzzle->getUserTotalScore($user->id),
                'found_words_count' => $submissions->count(),
                'possible_words_count' => $puzzle->possible_words_count,
            ];
        });
    }

    /**
     * Get guest submissions for a puzzle.
     *
     * @param Guest $guest
     * @param string|null $date
     * @return array
     */
    public function getGuestSubmissions(Guest $guest, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $puzzle = WordScramblePuzzle::forDate($date);

        if (!$puzzle) {
            return [
                'success' => false,
                'message' => 'No puzzle found for this date.',
            ];
        }

        $cacheKey = "word_scramble.submissions.guest.{$guest->guest_token}.puzzle.{$puzzle->id}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($guest, $puzzle) {
            $submissions = $puzzle->getGuestSubmissions($guest->guest_token);

            return [
                'success' => true,
                'submissions' => $submissions->map(function ($submission) {
                    return [
                        'word' => $submission->word,
                        'score' => $submission->score,
                        'submitted_at' => $submission->created_at->toDateTimeString(),
                    ];
                })->toArray(),
                'total_score' => $puzzle->getGuestTotalScore($guest->guest_token),
                'found_words_count' => $submissions->count(),
                'possible_words_count' => $puzzle->possible_words_count,
            ];
        });
    }

    /**
     * Get daily leaderboard for the Word Scramble game.
     *
     * @param Game $game
     * @param int $limit
     * @return array
     */
    public function getDailyLeaderboard(Game $game, int $limit = 10): array
    {
        return $this->leaderboardService->getDailyLeaderboard($game, $limit);
    }

    /**
     * Get monthly leaderboard for the Word Scramble game.
     *
     * @param Game $game
     * @param int $limit
     * @return array
     */
    public function getMonthlyLeaderboard(Game $game, int $limit = 10): array
    {
        return $this->leaderboardService->getMonthlyLeaderboard($game, $limit);
    }

    /**
     * Get all-time leaderboard for the Word Scramble game.
     *
     * @param Game $game
     * @param int $limit
     * @return array
     */
    public function getAllTimeLeaderboard(Game $game, int $limit = 10): array
    {
        return $this->leaderboardService->getAllTimeLeaderboard($game, $limit);
    }

    /**
     * Check if a word is valid for a puzzle.
     *
     * @param WordScramblePuzzle $puzzle
     * @param string $word
     * @return bool
     */
    protected function isValidWord(WordScramblePuzzle $puzzle, string $word): bool
    {
        // Check if the word exists in the puzzle's valid words (case-insensitive)
        return WordScrambleWord::where('puzzle_id', $puzzle->id)
            ->whereRaw('LOWER(word) = ?', [strtolower($word)])
            ->exists();
    }

    /**
     * Get the score for a word in a puzzle.
     *
     * @param WordScramblePuzzle $puzzle
     * @param string $word
     * @return int
     */
    protected function getWordScore(WordScramblePuzzle $puzzle, string $word): int
    {
        $wordModel = WordScrambleWord::where('puzzle_id', $puzzle->id)
            ->whereRaw('LOWER(word) = ?', [strtolower($word)])
            ->first();

        return $wordModel ? $wordModel->score : 0;
    }

    /**
     * Clear the user submissions cache.
     *
     * @param int $userId
     * @param int $puzzleId
     * @return void
     */
    protected function clearUserSubmissionsCache(int $userId, int $puzzleId): void
    {
        Cache::forget("word_scramble.submissions.user.{$userId}.puzzle.{$puzzleId}");
    }

    /**
     * Clear the guest submissions cache.
     *
     * @param string $guestToken
     * @param int $puzzleId
     * @return void
     */
    protected function clearGuestSubmissionsCache(string $guestToken, int $puzzleId): void
    {
        Cache::forget("word_scramble.submissions.guest.{$guestToken}.puzzle.{$puzzleId}");
    }
}