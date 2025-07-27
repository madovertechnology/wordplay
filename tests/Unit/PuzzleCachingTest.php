<?php

namespace Tests\Unit;

use App\Models\WordScramblePuzzle;
use App\Services\Game\DictionaryService;
use App\Services\Game\WordScramblePuzzleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PuzzleCachingTest extends TestCase
{
    use RefreshDatabase;

    protected WordScramblePuzzleService $puzzleService;
    protected DictionaryService $dictionaryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dictionaryService = new DictionaryService();
        $this->puzzleService = new WordScramblePuzzleService($this->dictionaryService);
    }

    #[Test]
    public function it_caches_daily_puzzle()
    {
        $date = '2025-07-26';
        
        // Clear any existing cache and delete any existing puzzles for this date
        $cacheKey = "word_scramble.puzzle.date.{$date}";
        Cache::forget($cacheKey);
        WordScramblePuzzle::where('date', $date)->delete();
        
        // First call should generate and cache the puzzle
        $puzzle1 = $this->puzzleService->generateDailyPuzzle($date, true);
        
        // Modify the puzzle directly in the database
        WordScramblePuzzle::where('id', $puzzle1->id)
            ->update(['letters' => 'MODIFIED']);
        
        // Second call should return the cached puzzle ID, but with updated data from DB
        $puzzle2 = $this->puzzleService->generateDailyPuzzle($date, true);
        
        // The IDs should be the same
        $this->assertEquals($puzzle1->id, $puzzle2->id);
        
        // But the letters should be updated since we're fetching from DB with the cached ID
        $this->assertEquals('MODIFIED', $puzzle2->fresh()->letters);
        
        // Delete the puzzle and clear the cache to test creating a new one
        WordScramblePuzzle::where('date', $date)->delete();
        Cache::forget($cacheKey);
        
        // Create a new puzzle with different letters for a different date
        $differentDate = '2025-07-28';
        $newPuzzle = WordScramblePuzzle::create([
            'letters' => 'NEWLTRS',
            'date' => $differentDate,
            'possible_words_count' => 10,
        ]);
        
        // Call should generate a new puzzle for our original date
        $puzzle3 = $this->puzzleService->generateDailyPuzzle($date, true);
        
        // Should be a different puzzle than the one we created for the different date
        $this->assertNotEquals($newPuzzle->id, $puzzle3->id);
    }

    #[Test]
    public function it_caches_possible_words()
    {
        // We'll use a unique set of letters to avoid conflicts with existing dictionary words
        $letters = 'xyzabcd';
        
        // Clear any existing cache
        $sortedLetters = str_split($letters);
        sort($sortedLetters);
        $sortedLetters = implode('', $sortedLetters);
        $cacheKey = "dictionary.possible_words.{$sortedLetters}";
        Cache::forget($cacheKey);
        
        // Also clear the dictionary cache to ensure we're working with a fresh dictionary
        Cache::forget(DictionaryService::DICTIONARY_CACHE_KEY);
        
        // First call should calculate and cache possible words
        $words1 = $this->dictionaryService->getPossibleWords($letters);
        
        // Create a new word that can be formed from these letters
        $newWord = 'cab'; // Can be formed from 'xyzabcd'
        
        // Add the word to the dictionary if it's not already there
        $reflectionClass = new \ReflectionClass(DictionaryService::class);
        $reflectionMethod = $reflectionClass->getMethod('getDictionary');
        $reflectionMethod->setAccessible(true);
        
        $dictionary = $reflectionMethod->invoke($this->dictionaryService);
        
        // Make sure the word isn't already in the dictionary
        if (in_array($newWord, $dictionary)) {
            // If it's already there, we'll remove it for this test
            $dictionary = array_diff($dictionary, [$newWord]);
            Cache::put(DictionaryService::DICTIONARY_CACHE_KEY, $dictionary, 60);
            
            // Recalculate words1 without the word
            Cache::forget($cacheKey);
            $words1 = $this->dictionaryService->getPossibleWords($letters);
        }
        
        // Now add the word to the dictionary
        $dictionary[] = $newWord;
        Cache::put(DictionaryService::DICTIONARY_CACHE_KEY, $dictionary, 60);
        
        // Second call should return cached result, not including the new word
        $words2 = $this->dictionaryService->getPossibleWords($letters);
        
        // Results should be identical despite dictionary change
        $this->assertEquals($words1, $words2);
        
        // Clear the possible words cache but keep the dictionary cache
        Cache::forget($cacheKey);
        
        // Third call should recalculate using the updated dictionary
        $words3 = $this->dictionaryService->getPossibleWords($letters);
        
        // The new word should be in the results now
        $this->assertContains($newWord, $words3);
        $this->assertGreaterThan(count($words1), count($words3));
    }

    #[Test]
    public function it_caches_word_validation()
    {
        $word = 'test';
        
        // Clear any existing cache
        $cacheKey = "dictionary.word_validation." . md5($word);
        Cache::forget($cacheKey);
        
        // First call should check dictionary and cache result
        $isValid1 = $this->dictionaryService->isValidWord($word);
        
        // Add or remove the word from the dictionary
        // This would normally be done by updating the dictionary file, but we'll simulate it
        $reflectionClass = new \ReflectionClass(DictionaryService::class);
        $reflectionMethod = $reflectionClass->getMethod('getDictionary');
        $reflectionMethod->setAccessible(true);
        
        $dictionary = $reflectionMethod->invoke($this->dictionaryService);
        
        if (in_array($word, $dictionary)) {
            // Remove the word
            $dictionary = array_diff($dictionary, [$word]);
        } else {
            // Add the word
            $dictionary[] = $word;
        }
        
        Cache::put(DictionaryService::DICTIONARY_CACHE_KEY, $dictionary, 60);
        
        // Second call should return cached result, not reflecting dictionary change
        $isValid2 = $this->dictionaryService->isValidWord($word);
        
        // Results should be identical despite dictionary change
        $this->assertEquals($isValid1, $isValid2);
        
        // Clear the cache
        Cache::forget($cacheKey);
        
        // Third call should check dictionary again and get updated result
        $isValid3 = $this->dictionaryService->isValidWord($word);
        
        // Result should be different now
        $this->assertNotEquals($isValid1, $isValid3);
    }

    #[Test]
    public function it_gets_puzzle_by_date_using_cache()
    {
        $date = '2025-07-27';
        
        // Clear any existing cache
        $cacheKey = "word_scramble.puzzle.date.{$date}";
        Cache::forget($cacheKey);
        
        // Create a puzzle for the date
        $puzzle = WordScramblePuzzle::create([
            'letters' => 'TESTPZL',
            'date' => $date,
            'possible_words_count' => 10,
        ]);
        
        // First call should find the puzzle and cache it
        $result1 = $this->puzzleService->getPuzzleByDate($date);
        
        // Should be the puzzle we created
        $this->assertEquals($puzzle->id, $result1->id);
        
        // Modify the puzzle directly in the database
        WordScramblePuzzle::where('id', $puzzle->id)
            ->update(['letters' => 'MODIFIED']);
        
        // Second call should return the cached puzzle ID, but with updated data from DB
        $result2 = $this->puzzleService->getPuzzleByDate($date);
        
        // The IDs should be the same
        $this->assertEquals($puzzle->id, $result2->id);
        
        // But the letters should be updated since we're fetching from DB with the cached ID
        $this->assertEquals('MODIFIED', $result2->letters);
    }
}