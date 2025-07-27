<?php

namespace Tests\Unit;

use App\Http\Requests\Leaderboard\GetLeaderboardRequest;
use App\Http\Requests\Streak\GetTopStreaksRequest;
use App\Http\Requests\Streak\GetUserStreakRequest;
use App\Http\Requests\WordScramble\GetSubmissionsRequest;
use App\Http\Requests\WordScramble\GetTodaysPuzzleRequest;
use App\Http\Requests\WordScramble\SubmitWordRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationRulesTest extends TestCase
{
    /**
     * Test the validation rules for GetTodaysPuzzleRequest.
     */
    public function test_get_todays_puzzle_request_validation(): void
    {
        $request = new GetTodaysPuzzleRequest();
        
        // Test valid data
        $validData = [
            'date' => '2023-01-01',
        ];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());
        
        // Test invalid date format
        $invalidData = [
            'date' => '01-01-2023',
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
        
        // Test future date
        $invalidData = [
            'date' => now()->addDay()->format('Y-m-d'),
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
    }
    
    /**
     * Test the validation rules for SubmitWordRequest.
     */
    public function test_submit_word_request_validation(): void
    {
        $request = new SubmitWordRequest();
        
        // Test valid data
        $validData = [
            'word' => 'test',
        ];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());
        
        // Test word too short
        $invalidData = [
            'word' => 'ab',
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
        
        // Test word too long
        $invalidData = [
            'word' => str_repeat('a', 21),
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
        
        // Test word with non-letter characters
        $invalidData = [
            'word' => 'test123',
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
        
        // Test word with spaces
        $invalidData = [
            'word' => 'test word',
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
    }
    
    /**
     * Test the validation rules for GetSubmissionsRequest.
     */
    public function test_get_submissions_request_validation(): void
    {
        $request = new GetSubmissionsRequest();
        
        // Test valid data
        $validData = [
            'date' => '2023-01-01',
        ];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());
        
        // Test invalid date format
        $invalidData = [
            'date' => '01-01-2023',
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
        
        // Test future date
        $invalidData = [
            'date' => now()->addDay()->format('Y-m-d'),
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
    }
    
    /**
     * Test the validation rules for GetLeaderboardRequest.
     */
    public function test_get_leaderboard_request_validation(): void
    {
        $request = new GetLeaderboardRequest();
        
        // Test valid data
        $validData = [
            'game' => 'word-scramble',
            'period' => 'daily',
            'limit' => 10,
            'offset' => 0,
            'date' => '2023-01-01',
            'yearMonth' => '2023-01',
        ];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());
        
        // Test invalid game format
        $invalidData = [
            'game' => 'Word Scramble',
            'period' => 'daily',
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
        
        // Test invalid period
        $invalidData = [
            'game' => 'word-scramble',
            'period' => 'invalid',
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
        
        // Test invalid limit
        $invalidData = [
            'game' => 'word-scramble',
            'period' => 'daily',
            'limit' => -1,
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
        
        // Test limit too high
        $invalidData = [
            'game' => 'word-scramble',
            'period' => 'daily',
            'limit' => 101,
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
        
        // Test invalid offset
        $invalidData = [
            'game' => 'word-scramble',
            'period' => 'daily',
            'offset' => -1,
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
    }
    
    /**
     * Test the validation rules for GetTopStreaksRequest.
     */
    public function test_get_top_streaks_request_validation(): void
    {
        $request = new GetTopStreaksRequest();
        
        // Test valid data
        $validData = [
            'limit' => 10,
        ];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());
        
        // Test invalid limit
        $invalidData = [
            'limit' => -1,
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
        
        // Test limit too high
        $invalidData = [
            'limit' => 51,
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
    }
    
    /**
     * Test the validation rules for GetUserStreakRequest.
     */
    public function test_get_user_streak_request_validation(): void
    {
        $request = new GetUserStreakRequest();
        
        // Test valid data
        $validData = [
            'game' => 'word-scramble',
        ];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());
        
        // Test invalid game format
        $invalidData = [
            'game' => 'Word Scramble',
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
        
        // Test game name too long
        $invalidData = [
            'game' => str_repeat('a', 51),
        ];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
    }
}