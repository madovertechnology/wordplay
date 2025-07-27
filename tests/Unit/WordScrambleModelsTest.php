<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\WordScramblePuzzle;
use App\Models\WordScrambleWord;
use App\Models\WordScrambleSubmission;
use App\Models\User;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WordScrambleModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_scramble_puzzle_can_be_created()
    {
        $puzzle = WordScramblePuzzle::create([
            'letters' => 'ABCDEFG',
            'date' => '2025-07-22',
            'possible_words_count' => 10,
        ]);

        $this->assertInstanceOf(WordScramblePuzzle::class, $puzzle);
        $this->assertEquals('ABCDEFG', $puzzle->letters);
        $this->assertEquals('2025-07-22', $puzzle->date->toDateString());
        $this->assertEquals(10, $puzzle->possible_words_count);
    }

    public function test_word_scramble_word_belongs_to_puzzle()
    {
        $puzzle = WordScramblePuzzle::create([
            'letters' => 'ABCDEFG',
            'date' => '2025-07-22',
            'possible_words_count' => 10,
        ]);

        $word = WordScrambleWord::create([
            'puzzle_id' => $puzzle->id,
            'word' => 'CAB',
            'score' => 5,
        ]);

        $this->assertInstanceOf(WordScramblePuzzle::class, $word->puzzle);
        $this->assertEquals($puzzle->id, $word->puzzle->id);
    }

    public function test_puzzle_has_many_words()
    {
        $puzzle = WordScramblePuzzle::create([
            'letters' => 'ABCDEFG',
            'date' => '2025-07-22',
            'possible_words_count' => 10,
        ]);

        WordScrambleWord::create([
            'puzzle_id' => $puzzle->id,
            'word' => 'CAB',
            'score' => 5,
        ]);

        WordScrambleWord::create([
            'puzzle_id' => $puzzle->id,
            'word' => 'BAD',
            'score' => 6,
        ]);

        $this->assertCount(2, $puzzle->words);
    }

    public function test_user_submission_relationships()
    {
        $user = User::factory()->create();
        $puzzle = WordScramblePuzzle::create([
            'letters' => 'ABCDEFG',
            'date' => '2025-07-22',
            'possible_words_count' => 10,
        ]);

        $submission = WordScrambleSubmission::create([
            'puzzle_id' => $puzzle->id,
            'user_id' => $user->id,
            'word' => 'CAB',
            'score' => 5,
        ]);

        $this->assertInstanceOf(User::class, $submission->user);
        $this->assertInstanceOf(WordScramblePuzzle::class, $submission->puzzle);
        $this->assertTrue($submission->isUserSubmission());
        $this->assertFalse($submission->isGuestSubmission());
    }

    public function test_guest_submission_relationships()
    {
        $guest = Guest::create(['guest_token' => 'test-guest-token']);
        $puzzle = WordScramblePuzzle::create([
            'letters' => 'ABCDEFG',
            'date' => '2025-07-22',
            'possible_words_count' => 10,
        ]);

        $submission = WordScrambleSubmission::create([
            'puzzle_id' => $puzzle->id,
            'guest_id' => 'test-guest-token',
            'word' => 'CAB',
            'score' => 5,
        ]);

        $this->assertInstanceOf(Guest::class, $submission->guest);
        $this->assertInstanceOf(WordScramblePuzzle::class, $submission->puzzle);
        $this->assertFalse($submission->isUserSubmission());
        $this->assertTrue($submission->isGuestSubmission());
    }

    public function test_puzzle_helper_methods()
    {
        $user = User::factory()->create();
        $puzzle = WordScramblePuzzle::create([
            'letters' => 'ABCDEFG',
            'date' => '2025-07-22',
            'possible_words_count' => 10,
        ]);

        // Create user submissions
        WordScrambleSubmission::create([
            'puzzle_id' => $puzzle->id,
            'user_id' => $user->id,
            'word' => 'CAB',
            'score' => 5,
        ]);

        WordScrambleSubmission::create([
            'puzzle_id' => $puzzle->id,
            'user_id' => $user->id,
            'word' => 'BAD',
            'score' => 6,
        ]);

        // Create guest submission
        WordScrambleSubmission::create([
            'puzzle_id' => $puzzle->id,
            'guest_id' => 'test-guest',
            'word' => 'ACE',
            'score' => 7,
        ]);

        $this->assertCount(2, $puzzle->getUserSubmissions($user->id));
        $this->assertCount(1, $puzzle->getGuestSubmissions('test-guest'));
        $this->assertEquals(11, $puzzle->getUserTotalScore($user->id));
        $this->assertEquals(7, $puzzle->getGuestTotalScore('test-guest'));
        $this->assertEquals(3, $puzzle->getTotalUniqueWordsFound());
    }

    public function test_puzzle_for_date_methods()
    {
        $date = '2025-07-22';
        $puzzle = WordScramblePuzzle::create([
            'letters' => 'ABCDEFG',
            'date' => $date,
            'possible_words_count' => 10,
        ]);

        // Refresh the model to ensure the date is properly cast
        $puzzle->refresh();

        $foundPuzzle = WordScramblePuzzle::forDate($date);
        $this->assertInstanceOf(WordScramblePuzzle::class, $foundPuzzle);
        $this->assertEquals($puzzle->id, $foundPuzzle->id);

        $notFoundPuzzle = WordScramblePuzzle::forDate('2025-07-23');
        $this->assertNull($notFoundPuzzle);
    }
}