<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Guest;
use App\Models\Streak;
use App\Models\User;
use App\Models\WordScramblePuzzle;
use App\Models\WordScrambleSubmission;
use App\Models\WordScrambleWord;
use App\Repositories\GameRepository;
use App\Services\Core\GamificationService;
use App\Services\Core\LeaderboardService;
use App\Services\Core\StreakService;
use App\Services\Game\DictionaryService;
use App\Services\Game\WordScrambleGameService;
use App\Services\Game\WordScramblePuzzleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WordScrambleGameServiceTest extends TestCase
{
    protected $gameService;
    protected $gameRepository;
    protected $dictionaryService;
    protected $puzzleService;
    protected $streakService;
    protected $leaderboardService;
    protected $gamificationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for dependencies
        $this->gameRepository = Mockery::mock(GameRepository::class);
        $this->dictionaryService = Mockery::mock(DictionaryService::class);
        $this->puzzleService = Mockery::mock(WordScramblePuzzleService::class);
        $this->streakService = Mockery::mock(StreakService::class);
        $this->leaderboardService = Mockery::mock(LeaderboardService::class);
        $this->gamificationService = Mockery::mock(GamificationService::class);

        // Create the game service with mocked dependencies
        $this->gameService = new WordScrambleGameService(
            $this->gameRepository,
            $this->dictionaryService,
            $this->puzzleService,
            $this->streakService,
            $this->leaderboardService,
            $this->gamificationService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_word_scramble_game_service_can_be_instantiated()
    {
        $this->assertInstanceOf(WordScrambleGameService::class, $this->gameService);
    }
}