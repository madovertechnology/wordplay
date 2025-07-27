<?php

namespace Tests\Unit;

use App\Models\WordScramblePuzzle;
use App\Models\WordScrambleWord;
use App\Services\Game\DictionaryService;
use App\Services\Game\WordScramblePuzzleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordScramblePuzzleServiceTest extends TestCase
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

    public function test_generate_daily_puzzle_creates_new_puzzle()
    {
        $date = '2025-07-23';
        $puzzle = $this->puzzleService->generateDailyPuzzle($date, true); // Use forTesting=true
        
        $this->assertInstanceOf(WordScramblePuzzle::class, $puzzle);
        $this->assertEquals($date, $puzzle->date->toDateString());
        $this->assertNotEmpty($puzzle->letters);
        $this->assertEquals(7, strlen($puzzle->letters));
        $this->assertGreaterThan(0, $puzzle->possible_words_count);
    }

    public function test_generate_daily_puzzle_returns_existing_puzzle()
    {
        $date = '2025-07-24';
        
        // Create a puzzle for the date
        $existingPuzzle = WordScramblePuzzle::create([
            'letters' => 'ABCDEFG',
            'date' => $date,
            'possible_words_count' => 10,
        ]);
        
        // Try to generate a puzzle for the same date
        $puzzle = $this->puzzleService->generateDailyPuzzle($date);
        
        // Should return the existing puzzle
        $this->assertEquals($existingPuzzle->id, $puzzle->id);
        $this->assertEquals('ABCDEFG', $puzzle->letters);
    }

    public function test_generate_daily_puzzle_creates_possible_words()
    {
        $date = '2025-07-25';
        $puzzle = $this->puzzleService->generateDailyPuzzle($date, true); // Use forTesting=true
        
        // Check that possible words were created
        $words = WordScrambleWord::where('puzzle_id', $puzzle->id)->get();
        $this->assertGreaterThan(0, $words->count());
        
        // Check that all words can be formed from the puzzle letters
        foreach ($words as $word) {
            $this->assertTrue(
                $this->dictionaryService->canFormWord($puzzle->letters, $word->word),
                "Word '{$word->word}' cannot be formed from letters '{$puzzle->letters}'"
            );
        }
    }

    public function test_generate_letters_creates_valid_letter_set()
    {
        // Use reflection to access protected method
        $reflectionMethod = new \ReflectionMethod(WordScramblePuzzleService::class, 'generateLetters');
        $reflectionMethod->setAccessible(true);
        
        $letters = $reflectionMethod->invoke($this->puzzleService);
        
        $this->assertEquals(7, strlen($letters));
        
        // Check that there are at least 2 vowels
        $vowels = ['a', 'e', 'i', 'o', 'u'];
        $vowelCount = 0;
        
        for ($i = 0; $i < strlen($letters); $i++) {
            if (in_array($letters[$i], $vowels)) {
                $vowelCount++;
            }
        }
        
        $this->assertGreaterThanOrEqual(2, $vowelCount);
    }

    public function test_generate_future_puzzles_creates_multiple_puzzles()
    {
        $days = 3;
        $puzzles = $this->puzzleService->generateFuturePuzzles($days, true); // Use forTesting=true
        
        $this->assertCount($days, $puzzles);
        
        // Check that puzzles were created for consecutive days
        for ($i = 0; $i < $days; $i++) {
            $expectedDate = now()->addDays($i)->toDateString();
            $this->assertEquals($expectedDate, $puzzles[$i]->date->toDateString());
        }
    }
}