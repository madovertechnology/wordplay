<?php

namespace Tests\Unit;

use App\Services\Game\DictionaryService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DictionaryServiceTest extends TestCase
{
    protected DictionaryService $dictionaryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dictionaryService = new DictionaryService();
        
        // Clear the dictionary cache before each test
        Cache::forget(DictionaryService::DICTIONARY_CACHE_KEY);
    }

    public function test_is_valid_word_returns_true_for_valid_words()
    {
        // These words should be in our sample dictionary
        $this->assertTrue($this->dictionaryService->isValidWord('cat'));
        $this->assertTrue($this->dictionaryService->isValidWord('dog'));
        $this->assertTrue($this->dictionaryService->isValidWord('bat'));
    }

    public function test_is_valid_word_returns_false_for_invalid_words()
    {
        // These words should not be in our dictionary
        $this->assertFalse($this->dictionaryService->isValidWord('xyz'));
        $this->assertFalse($this->dictionaryService->isValidWord('qwerty'));
        $this->assertFalse($this->dictionaryService->isValidWord('zzz'));
    }

    public function test_is_valid_word_returns_false_for_short_words()
    {
        // Words shorter than MIN_WORD_LENGTH should be invalid
        $this->assertFalse($this->dictionaryService->isValidWord('a'));
        $this->assertFalse($this->dictionaryService->isValidWord('ab'));
        $this->assertTrue($this->dictionaryService->isValidWord('cat')); // 3 letters, should be valid
    }

    public function test_can_form_word_validates_correctly()
    {
        // Test with the letters 'catdog'
        $letters = 'catdog';
        
        // Words that can be formed
        $this->assertTrue($this->dictionaryService->canFormWord($letters, 'cat'));
        $this->assertTrue($this->dictionaryService->canFormWord($letters, 'dog'));
        $this->assertTrue($this->dictionaryService->canFormWord($letters, 'act'));
        $this->assertTrue($this->dictionaryService->canFormWord($letters, 'taco'));
        
        // Words that cannot be formed
        $this->assertFalse($this->dictionaryService->canFormWord($letters, 'bat')); // No 'b' in 'catdog'
        $this->assertFalse($this->dictionaryService->canFormWord($letters, 'catt')); // Only one 't' in 'catdog'
        $this->assertFalse($this->dictionaryService->canFormWord($letters, 'doggy')); // No 'y' in 'catdog'
    }

    public function test_get_possible_words_returns_valid_words()
    {
        // Use a limited set of letters that can form words from our sample dictionary
        $letters = 'catbad';
        $possibleWords = $this->dictionaryService->getPossibleWords($letters);
        
        // Words that should be in the result
        $this->assertContains('cat', $possibleWords);
        $this->assertContains('bat', $possibleWords);
        $this->assertContains('bad', $possibleWords);
        $this->assertContains('cab', $possibleWords);
        $this->assertContains('tab', $possibleWords);
        
        // Words that should not be in the result
        $this->assertNotContains('dog', $possibleWords);
        $this->assertNotContains('rat', $possibleWords);
    }

    public function test_calculate_word_score_returns_correct_scores()
    {
        // Test scoring based on word length
        $this->assertEquals(1, $this->dictionaryService->calculateWordScore('cat')); // 3 letters
        $this->assertEquals(2, $this->dictionaryService->calculateWordScore('dogs')); // 4 letters
        $this->assertEquals(4, $this->dictionaryService->calculateWordScore('house')); // 5 letters
        $this->assertEquals(7, $this->dictionaryService->calculateWordScore('garden')); // 6 letters
        $this->assertEquals(10, $this->dictionaryService->calculateWordScore('kitchen')); // 7 letters
        $this->assertEquals(15, $this->dictionaryService->calculateWordScore('computer')); // 8 letters
    }
}