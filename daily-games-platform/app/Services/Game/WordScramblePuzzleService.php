<?php

namespace App\Services\Game;

use App\Models\WordScramblePuzzle;
use App\Models\WordScrambleWord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WordScramblePuzzleService
{
    /**
     * The dictionary service
     */
    protected DictionaryService $dictionaryService;
    
    /**
     * Cache TTL for puzzles in seconds (24 hours)
     */
    const PUZZLE_CACHE_TTL = 86400;
    
    /**
     * Cache key prefix for puzzles
     */
    const PUZZLE_CACHE_PREFIX = 'word_scramble.puzzle';

    /**
     * Minimum number of possible words for a valid puzzle
     */
    const MIN_POSSIBLE_WORDS = 10;

    /**
     * Number of letters in a puzzle
     */
    const PUZZLE_LETTERS_COUNT = 7;

    /**
     * Letter frequency in English language
     */
    const LETTER_FREQUENCY = [
        'a' => 8.17, 'b' => 1.49, 'c' => 2.78, 'd' => 4.25, 'e' => 12.70,
        'f' => 2.23, 'g' => 2.02, 'h' => 6.09, 'i' => 6.97, 'j' => 0.15,
        'k' => 0.77, 'l' => 4.03, 'm' => 2.41, 'n' => 6.75, 'o' => 7.51,
        'p' => 1.93, 'q' => 0.10, 'r' => 5.99, 's' => 6.33, 't' => 9.06,
        'u' => 2.76, 'v' => 0.98, 'w' => 2.36, 'x' => 0.15, 'y' => 1.97,
        'z' => 0.07
    ];

    /**
     * Constructor
     */
    public function __construct(DictionaryService $dictionaryService)
    {
        $this->dictionaryService = $dictionaryService;
    }

    /**
     * Generate a daily puzzle for the given date
     *
     * @param string|null $date
     * @param bool $forTesting
     * @return WordScramblePuzzle
     */
    public function generateDailyPuzzle(?string $date = null, bool $forTesting = false): WordScramblePuzzle
    {
        $date = $date ?? now()->toDateString();
        
        // Check cache first
        $cacheKey = $this->getPuzzleCacheKey($date);
        $puzzleId = Cache::get($cacheKey);
        
        if ($puzzleId) {
            $puzzle = WordScramblePuzzle::find($puzzleId);
            if ($puzzle) {
                Log::info("Puzzle cache hit for date: {$date}");
                return $puzzle;
            }
        }
        
        // Check if a puzzle already exists for this date
        $existingPuzzle = WordScramblePuzzle::forDate($date);
        if ($existingPuzzle) {
            // Cache the puzzle ID
            Cache::put($cacheKey, $existingPuzzle->id, self::PUZZLE_CACHE_TTL);
            Log::info("Cached existing puzzle for date: {$date}");
            return $existingPuzzle;
        }
        
        // For testing, use a fixed set of letters that we know will generate words
        if ($forTesting) {
            $letters = 'artesni'; // This should match many words in our sample dictionary
        } else {
            // Generate a new puzzle
            $letters = $this->generateLetters();
            $possibleWords = $this->dictionaryService->getPossibleWords($letters);
            
            // If there aren't enough possible words, try again with new letters
            $attempts = 0;
            while (count($possibleWords) < self::MIN_POSSIBLE_WORDS && $attempts < 10) {
                $letters = $this->generateLetters();
                $possibleWords = $this->dictionaryService->getPossibleWords($letters);
                $attempts++;
            }
        }
        
        // Get possible words for the letters
        $possibleWords = $this->dictionaryService->getPossibleWords($letters);
        
        // Create the puzzle
        $puzzle = WordScramblePuzzle::create([
            'letters' => strtoupper($letters),
            'date' => $date,
            'possible_words_count' => count($possibleWords),
        ]);
        
        // Store all possible words
        foreach ($possibleWords as $word) {
            WordScrambleWord::create([
                'puzzle_id' => $puzzle->id,
                'word' => $word,
                'score' => $this->dictionaryService->calculateWordScore($word),
            ]);
        }
        
        // Cache the puzzle ID
        Cache::put($cacheKey, $puzzle->id, self::PUZZLE_CACHE_TTL);
        Log::info("Generated and cached new puzzle for date: {$date}");
        
        return $puzzle;
    }
    
    /**
     * Get a puzzle by date, using cache if available
     *
     * @param string|null $date
     * @return WordScramblePuzzle|null
     */
    public function getPuzzleByDate(?string $date = null): ?WordScramblePuzzle
    {
        $date = $date ?? now()->toDateString();
        $cacheKey = $this->getPuzzleCacheKey($date);
        
        // Try to get puzzle ID from cache
        $puzzleId = Cache::get($cacheKey);
        
        if ($puzzleId) {
            $puzzle = WordScramblePuzzle::find($puzzleId);
            if ($puzzle) {
                Log::info("Puzzle cache hit for date: {$date}");
                return $puzzle;
            }
        }
        
        // If not in cache, try to get from database
        $puzzle = WordScramblePuzzle::forDate($date);
        
        if ($puzzle) {
            // Cache the puzzle ID
            Cache::put($cacheKey, $puzzle->id, self::PUZZLE_CACHE_TTL);
            Log::info("Cached puzzle for date: {$date} after cache miss");
        }
        
        return $puzzle;
    }

    /**
     * Generate a set of letters for a puzzle
     *
     * @return string
     */
    protected function generateLetters(): string
    {
        // Ensure we have at least 2 vowels
        $vowels = ['a', 'e', 'i', 'o', 'u'];
        $letters = [];
        
        // Add 2-3 vowels
        $vowelCount = rand(2, 3);
        for ($i = 0; $i < $vowelCount; $i++) {
            $letters[] = $vowels[array_rand($vowels)];
        }
        
        // Fill the rest with consonants, weighted by frequency
        while (count($letters) < self::PUZZLE_LETTERS_COUNT) {
            $letter = $this->getWeightedRandomLetter();
            
            // Avoid too many of the same letter
            if (count(array_keys($letters, $letter)) < 2) {
                $letters[] = $letter;
            }
        }
        
        // Shuffle the letters
        shuffle($letters);
        
        return implode('', $letters);
    }

    /**
     * Get a random letter weighted by frequency in English
     *
     * @return string
     */
    protected function getWeightedRandomLetter(): string
    {
        $consonants = array_diff(array_keys(self::LETTER_FREQUENCY), ['a', 'e', 'i', 'o', 'u']);
        $totalWeight = 0;
        
        foreach ($consonants as $letter) {
            $totalWeight += self::LETTER_FREQUENCY[$letter];
        }
        
        $randomWeight = mt_rand(0, $totalWeight * 100) / 100;
        $currentWeight = 0;
        
        foreach ($consonants as $letter) {
            $currentWeight += self::LETTER_FREQUENCY[$letter];
            if ($randomWeight <= $currentWeight) {
                return $letter;
            }
        }
        
        // Fallback to a random consonant
        return $consonants[array_rand($consonants)];
    }

    /**
     * Generate puzzles for the next few days
     *
     * @param int $days
     * @param bool $forTesting
     * @return array
     */
    public function generateFuturePuzzles(int $days = 7, bool $forTesting = false): array
    {
        $puzzles = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = now()->addDays($i)->toDateString();
            $puzzles[] = $this->generateDailyPuzzle($date, $forTesting);
        }
        
        return $puzzles;
    }
    
    /**
     * Get the cache key for a puzzle by date
     *
     * @param string $date
     * @return string
     */
    protected function getPuzzleCacheKey(string $date): string
    {
        return self::PUZZLE_CACHE_PREFIX . ".date.{$date}";
    }
    
    /**
     * Clear the puzzle cache for a specific date
     *
     * @param string $date
     * @return void
     */
    public function clearPuzzleCache(string $date): void
    {
        $cacheKey = $this->getPuzzleCacheKey($date);
        Cache::forget($cacheKey);
        Log::info("Cleared puzzle cache for date: {$date}");
    }
}