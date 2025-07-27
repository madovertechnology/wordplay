<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Game;
use App\Models\WordScramblePuzzle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class TestRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all application routes and functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Application Routes');
        $this->info('============================');
        $this->newLine();

        // Test basic routes
        $this->testBasicRoutes();
        
        // Test API routes
        $this->testApiRoutes();
        
        // Test game functionality
        $this->testGameFunctionality();
        
        $this->newLine();
        $this->info('✅ Route testing completed!');
        
        return 0;
    }

    private function testBasicRoutes()
    {
        $this->info('🌐 Testing Basic Routes:');
        
        $routes = [
            '/' => 'Welcome page',
            '/leaderboards/word-scramble' => 'Leaderboard page',
            '/games/word-scramble' => 'Word Scramble game',
            '/health' => 'Health check',
        ];

        foreach ($routes as $route => $description) {
            try {
                $response = $this->get($route);
                if ($response->getStatusCode() === 200) {
                    $this->line("✅ {$description}: {$route}");
                } else {
                    $this->error("❌ {$description}: {$route} (Status: {$response->getStatusCode()})");
                }
            } catch (\Exception $e) {
                $this->error("❌ {$description}: {$route} (Error: {$e->getMessage()})");
            }
        }
    }

    private function testApiRoutes()
    {
        $this->newLine();
        $this->info('🔌 Testing API Routes:');
        
        $apiRoutes = [
            '/leaderboards/api/word-scramble' => 'Leaderboard API',
            '/games/word-scramble/api/puzzle' => 'Today\'s puzzle API',
            '/games/word-scramble/api/leaderboard/daily' => 'Daily leaderboard API',
            '/health/detailed' => 'Detailed health check',
        ];

        foreach ($apiRoutes as $route => $description) {
            try {
                $response = $this->get($route);
                if ($response->getStatusCode() === 200) {
                    $this->line("✅ {$description}: {$route}");
                } else {
                    $this->error("❌ {$description}: {$route} (Status: {$response->getStatusCode()})");
                }
            } catch (\Exception $e) {
                $this->error("❌ {$description}: {$route} (Error: {$e->getMessage()})");
            }
        }
    }

    private function testGameFunctionality()
    {
        $this->newLine();
        $this->info('🎮 Testing Game Functionality:');
        
        // Test puzzle service
        try {
            $puzzle = WordScramblePuzzle::today();
            if ($puzzle) {
                $this->line("✅ Today's puzzle loaded: {$puzzle->letters}");
                
                $words = $puzzle->words()->count();
                $this->line("✅ Puzzle has {$words} available words");
                
                // Test word validation
                $firstWord = $puzzle->words()->first();
                if ($firstWord) {
                    $this->line("✅ Sample word: '{$firstWord->word}' ({$firstWord->score} points)");
                }
            } else {
                $this->error("❌ No puzzle available for today");
            }
        } catch (\Exception $e) {
            $this->error("❌ Puzzle functionality error: {$e->getMessage()}");
        }

        // Test user stats
        try {
            $testUser = User::where('email', 'test@example.com')->first();
            if ($testUser) {
                $stats = $testUser->gameStats()->first();
                if ($stats) {
                    $this->line("✅ User stats working: {$stats->total_score} points");
                }
                
                $streak = $testUser->streaks()->first();
                if ($streak) {
                    $this->line("✅ Streak system working: {$streak->current_streak} current");
                }
                
                $badges = $testUser->badges()->count();
                $this->line("✅ Badge system working: {$badges} badges earned");
            }
        } catch (\Exception $e) {
            $this->error("❌ User functionality error: {$e->getMessage()}");
        }

        // Test services
        $this->testServices();
    }

    private function testServices()
    {
        $this->newLine();
        $this->info('⚙️ Testing Services:');
        
        try {
            // Test DashboardService
            $dashboardService = app(\App\Services\Core\DashboardService::class);
            $testUser = User::where('email', 'test@example.com')->first();
            
            if ($testUser) {
                $dashboardData = $dashboardService->getDashboardData($testUser);
                $this->line("✅ Dashboard service working");
            }
        } catch (\Exception $e) {
            $this->error("❌ Dashboard service error: {$e->getMessage()}");
        }

        try {
            // Test LeaderboardService
            $leaderboardService = app(\App\Services\Core\LeaderboardService::class);
            $game = Game::where('slug', 'word-scramble')->first();
            
            if ($game) {
                $leaderboard = $leaderboardService->getAllTimeLeaderboard($game);
                $this->line("✅ Leaderboard service working: " . count($leaderboard) . " entries");
            }
        } catch (\Exception $e) {
            $this->error("❌ Leaderboard service error: {$e->getMessage()}");
        }

        try {
            // Test WordScrambleGameService
            $gameService = app(\App\Services\Game\WordScrambleGameService::class);
            $puzzle = WordScramblePuzzle::today();
            
            if ($puzzle) {
                $puzzleData = $gameService->getTodaysPuzzle();
                $this->line("✅ Word Scramble service working");
            }
        } catch (\Exception $e) {
            $this->error("❌ Word Scramble service error: {$e->getMessage()}");
        }
    }

    private function get($uri)
    {
        $request = \Illuminate\Http\Request::create($uri, 'GET');
        $response = app()->handle($request);
        return $response;
    }
}
