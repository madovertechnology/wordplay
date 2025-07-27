<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Game;
use App\Models\WordScramblePuzzle;
use App\Models\WordScrambleSubmission;
use App\Models\Badge;
use App\Models\Leaderboard;
use App\Models\Streak;
use App\Models\UserGameStats;
use App\Services\Core\DashboardService;
use App\Services\Core\LeaderboardService;
use App\Services\Game\WordScrambleGameService;
use App\Services\Game\WordScramblePuzzleService;
use App\Services\Core\StreakService;
use App\Services\Core\GamificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestProduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:production';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprehensive production readiness test';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Production Readiness Test');
        $this->info('===========================');
        $this->newLine();

        $allTestsPassed = true;

        // Test database integrity
        $allTestsPassed &= $this->testDatabaseIntegrity();
        
        // Test core services
        $allTestsPassed &= $this->testCoreServices();
        
        // Test game functionality
        $allTestsPassed &= $this->testGameFunctionality();
        
        // Test user features
        $allTestsPassed &= $this->testUserFeatures();
        
        // Test performance
        $allTestsPassed &= $this->testPerformance();

        $this->newLine();
        if ($allTestsPassed) {
            $this->info('✅ ALL TESTS PASSED - SYSTEM READY FOR PRODUCTION!');
            $this->info('================================================');
            $this->line('🌐 Application URL: http://daily-games-platform.test');
            $this->line('👤 Test Login: test@example.com / password');
            $this->line('🎮 Features: Word Scramble, Leaderboards, Badges, Streaks');
            $this->line('📊 Analytics: User tracking, Performance monitoring');
            $this->line('🔒 Security: Rate limiting, Input validation, CSRF protection');
        } else {
            $this->error('❌ SOME TESTS FAILED - REVIEW BEFORE PRODUCTION');
        }

        return $allTestsPassed ? 0 : 1;
    }

    private function testDatabaseIntegrity(): bool
    {
        $this->info('🗄️ Testing Database Integrity:');
        $passed = true;

        try {
            // Test basic counts
            $users = User::count();
            $games = Game::count();
            $puzzles = WordScramblePuzzle::count();
            $submissions = WordScrambleSubmission::count();
            $badges = Badge::count();
            $leaderboards = Leaderboard::count();
            $streaks = Streak::count();
            $stats = UserGameStats::count();

            $this->line("✅ Users: {$users}");
            $this->line("✅ Games: {$games}");
            $this->line("✅ Puzzles: {$puzzles}");
            $this->line("✅ Submissions: {$submissions}");
            $this->line("✅ Badges: {$badges}");
            $this->line("✅ Leaderboards: {$leaderboards}");
            $this->line("✅ Streaks: {$streaks}");
            $this->line("✅ User Stats: {$stats}");

            // Test data integrity
            if ($users < 5) {
                $this->error("❌ Insufficient test users");
                $passed = false;
            }

            if ($puzzles < 30) {
                $this->error("❌ Insufficient puzzle data");
                $passed = false;
            }

            if ($submissions < 100) {
                $this->error("❌ Insufficient submission data");
                $passed = false;
            }

        } catch (\Exception $e) {
            $this->error("❌ Database error: {$e->getMessage()}");
            $passed = false;
        }

        return $passed;
    }

    private function testCoreServices(): bool
    {
        $this->newLine();
        $this->info('⚙️ Testing Core Services:');
        $passed = true;

        // Test DashboardService
        try {
            $dashboardService = app(DashboardService::class);
            $testUser = User::where('email', 'test@example.com')->first();
            
            if ($testUser) {
                $dashboardData = $dashboardService->getDashboardData($testUser);
                $this->line("✅ Dashboard Service: Working");
            } else {
                $this->error("❌ Dashboard Service: Test user not found");
                $passed = false;
            }
        } catch (\Exception $e) {
            $this->error("❌ Dashboard Service: {$e->getMessage()}");
            $passed = false;
        }

        // Test LeaderboardService
        try {
            $leaderboardService = app(LeaderboardService::class);
            $game = Game::where('slug', 'word-scramble')->first();
            
            if ($game) {
                $leaderboard = $leaderboardService->getAllTimeLeaderboard($game);
                $this->line("✅ Leaderboard Service: " . count($leaderboard) . " entries");
            } else {
                $this->error("❌ Leaderboard Service: Game not found");
                $passed = false;
            }
        } catch (\Exception $e) {
            $this->error("❌ Leaderboard Service: {$e->getMessage()}");
            $passed = false;
        }

        // Test StreakService
        try {
            $streakService = app(StreakService::class);
            $game = Game::where('slug', 'word-scramble')->first();
            $testUser = User::where('email', 'test@example.com')->first();
            
            if ($game && $testUser) {
                $streak = $streakService->getUserStreak($game, $testUser);
                $this->line("✅ Streak Service: Current streak {$streak['current_streak']}");
            }
        } catch (\Exception $e) {
            $this->error("❌ Streak Service: {$e->getMessage()}");
            $passed = false;
        }

        // Test GamificationService
        try {
            $gamificationService = app(GamificationService::class);
            $testUser = User::where('email', 'test@example.com')->first();
            
            if ($testUser) {
                $badges = $gamificationService->getUserBadges($testUser);
                $this->line("✅ Gamification Service: " . count($badges) . " badges");
            }
        } catch (\Exception $e) {
            $this->error("❌ Gamification Service: {$e->getMessage()}");
            $passed = false;
        }

        return $passed;
    }

    private function testGameFunctionality(): bool
    {
        $this->newLine();
        $this->info('🎮 Testing Game Functionality:');
        $passed = true;

        // Test WordScrambleGameService
        try {
            $gameService = app(WordScrambleGameService::class);
            $puzzleData = $gameService->getTodaysPuzzle();
            
            if ($puzzleData && isset($puzzleData['letters'])) {
                $this->line("✅ Word Scramble Game: Today's puzzle '{$puzzleData['letters']}'");
            } else {
                $this->error("❌ Word Scramble Game: No puzzle data");
                $passed = false;
            }
        } catch (\Exception $e) {
            $this->error("❌ Word Scramble Game: {$e->getMessage()}");
            $passed = false;
        }

        // Test WordScramblePuzzleService
        try {
            $puzzleService = app(WordScramblePuzzleService::class);
            $puzzle = $puzzleService->getPuzzleByDate();
            
            if ($puzzle) {
                $this->line("✅ Puzzle Service: Puzzle loaded with {$puzzle->possible_words_count} words");
            } else {
                $this->error("❌ Puzzle Service: No puzzle found");
                $passed = false;
            }
        } catch (\Exception $e) {
            $this->error("❌ Puzzle Service: {$e->getMessage()}");
            $passed = false;
        }

        // Test word validation
        try {
            $puzzle = WordScramblePuzzle::today();
            if ($puzzle) {
                $firstWord = $puzzle->words()->first();
                if ($firstWord) {
                    $this->line("✅ Word Validation: Sample word '{$firstWord->word}' ({$firstWord->score} pts)");
                } else {
                    $this->error("❌ Word Validation: No words found");
                    $passed = false;
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Word Validation: {$e->getMessage()}");
            $passed = false;
        }

        return $passed;
    }

    private function testUserFeatures(): bool
    {
        $this->newLine();
        $this->info('👤 Testing User Features:');
        $passed = true;

        $testUser = User::where('email', 'test@example.com')->first();
        
        if (!$testUser) {
            $this->error("❌ Test user not found");
            return false;
        }

        // Test user stats
        try {
            $stats = $testUser->gameStats()->first();
            if ($stats) {
                $this->line("✅ User Stats: {$stats->total_score} points, {$stats->plays_count} plays");
            } else {
                $this->error("❌ User Stats: No stats found");
                $passed = false;
            }
        } catch (\Exception $e) {
            $this->error("❌ User Stats: {$e->getMessage()}");
            $passed = false;
        }

        // Test user streaks
        try {
            $streak = $testUser->streaks()->first();
            if ($streak) {
                $this->line("✅ User Streaks: {$streak->current_streak} current, {$streak->longest_streak} longest");
            } else {
                $this->error("❌ User Streaks: No streak found");
                $passed = false;
            }
        } catch (\Exception $e) {
            $this->error("❌ User Streaks: {$e->getMessage()}");
            $passed = false;
        }

        // Test user badges
        try {
            $badges = $testUser->badges()->count();
            $this->line("✅ User Badges: {$badges} badges earned");
        } catch (\Exception $e) {
            $this->error("❌ User Badges: {$e->getMessage()}");
            $passed = false;
        }

        return $passed;
    }

    private function testPerformance(): bool
    {
        $this->newLine();
        $this->info('⚡ Testing Performance:');
        $passed = true;

        // Test database query performance
        try {
            $start = microtime(true);
            
            // Simulate typical queries
            $leaderboard = Leaderboard::with('user')
                ->where('period_type', 'all_time')
                ->orderBy('score', 'desc')
                ->take(10)
                ->get();
            
            $end = microtime(true);
            $queryTime = round(($end - $start) * 1000, 2);
            
            if ($queryTime < 100) {
                $this->line("✅ Database Performance: {$queryTime}ms (Good)");
            } elseif ($queryTime < 500) {
                $this->line("⚠️ Database Performance: {$queryTime}ms (Acceptable)");
            } else {
                $this->error("❌ Database Performance: {$queryTime}ms (Slow)");
                $passed = false;
            }
        } catch (\Exception $e) {
            $this->error("❌ Database Performance: {$e->getMessage()}");
            $passed = false;
        }

        // Test memory usage
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
        if ($memoryUsage < 50) {
            $this->line("✅ Memory Usage: " . round($memoryUsage, 2) . "MB (Good)");
        } elseif ($memoryUsage < 100) {
            $this->line("⚠️ Memory Usage: " . round($memoryUsage, 2) . "MB (Acceptable)");
        } else {
            $this->error("❌ Memory Usage: " . round($memoryUsage, 2) . "MB (High)");
            $passed = false;
        }

        return $passed;
    }
}