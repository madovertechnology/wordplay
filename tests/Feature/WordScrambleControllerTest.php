<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use App\Models\WordScramblePuzzle;
use App\Models\WordScrambleWord;
use App\Services\Game\WordScrambleGameService;
use App\Services\User\GuestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class WordScrambleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_todays_puzzle_endpoint()
    {
        // Create a puzzle for today
        $puzzle = WordScramblePuzzle::create([
            'letters' => 'ABCDEFG',
            'date' => now()->toDateString(),
            'possible_words_count' => 10,
        ]);

        // Make the request
        $response = $this->getJson(route('games.word-scramble.api.puzzle'));

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'letters',
                'date',
                'possible_words_count',
            ])
            ->assertJson([
                'letters' => 'ABCDEFG',
                'possible_words_count' => 10,
            ]);
    }

    public function test_get_submissions_endpoint_for_authenticated_user()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a puzzle for today
        $puzzle = WordScramblePuzzle::create([
            'letters' => 'ABCDEFG',
            'date' => now()->toDateString(),
            'possible_words_count' => 10,
        ]);

        // Create a submission for the user
        $submission = \App\Models\WordScrambleSubmission::create([
            'puzzle_id' => $puzzle->id,
            'user_id' => $user->id,
            'word' => 'cab',
            'score' => 1,
        ]);

        // Make the request as an authenticated user
        $response = $this->actingAs($user)
            ->getJson(route('games.word-scramble.api.submissions'));

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'submissions' => [
                    '*' => [
                        'word',
                        'score',
                        'submitted_at',
                    ],
                ],
                'total_score',
                'found_words_count',
                'possible_words_count',
            ])
            ->assertJson([
                'success' => true,
                'total_score' => 1,
                'found_words_count' => 1,
            ]);
    }

    public function test_get_daily_leaderboard_endpoint()
    {
        // Create a game
        $game = Game::create([
            'name' => 'Word Scramble',
            'slug' => 'word-scramble',
            'description' => 'A word scramble game',
            'is_active' => true,
        ]);

        // Make the request
        $response = $this->getJson(route('games.word-scramble.api.leaderboard.daily'));

        // Assert the response
        $response->assertStatus(200);
    }
}