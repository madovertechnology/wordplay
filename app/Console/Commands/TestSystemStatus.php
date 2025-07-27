<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Game;
use App\Models\WordScramblePuzzle;
use App\Models\WordScrambleSubmission;
use App\Models\Badge;
use App\Models\Leaderboard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestSystemStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test system status and verify all functionality is working';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎮 Daily Games Platform - System Status Check');
        $this->info('=============================================');
        $this->newLine();

        // Test database connectivity and data
        $this->info('📊 Database Status:');
        try {
            $users = User::count();
            $games = Game::count();
            $puzzles = WordScramblePuzzle::count();
            $submissions = WordScrambleSubmission::count();
            $badges = Badge::count();
            $leaderboards = Leaderboard::count();
            
            $this->line("✅ Users: {$users}");
            $this->line("✅ Games: {$games}");
            $this->line("✅ Word Scramble Puzzles: {$puzzles}");
            $this->line("✅ Submissions: {$submissions}");
            $this->line("✅ Badges: {$badges}");
            $this->line("✅ Leaderboard Entries: {$leaderboards}");
        } catch (\Exception $e) {
            $this->error("❌ Database Error: " . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('🎯 Game Data Status:');

        // Check today's puzzle
        $todaysPuzzle = WordScramblePuzzle::today();
        if ($todaysPuzzle) {
            $this->line("✅ Today's Puzzle: {$todaysPuzzle->letters} ({$todaysPuzzle->possible_words_count} words)");
            $wordsCount = $todaysPuzzle->words()->count();
            $this->line("✅ Available Words: {$wordsCount}");
        } else {
            $this->error("❌ No puzzle for today");
        }

        // Check user stats
        $testUser = User::where('email', 'test@example.com')->first();
        if ($testUser) {
            $stats = $testUser->gameStats()->first();
            if ($stats) {
                $this->line("✅ Test User Stats: {$stats->total_score} points, {$stats->plays_count} plays");
            }
            
            $streak = $testUser->streaks()->first();
            if ($streak) {
                $this->line("✅ Test User Streak: {$streak->current_streak} current, {$streak->longest_streak} longest");
            }
            
            $badges = $testUser->badges()->count();
            $this->line("✅ Test User Badges: {$badges}");
        }

        $this->newLine();
        $this->info('🏆 Leaderboard Status:');
        $topUsers = Leaderboard::where('period_type', 'all_time')
            ->orderBy('score', 'desc')
            ->with('user')
            ->take(3)
            ->get();

        foreach ($topUsers as $index => $entry) {
            $position = $index + 1;
            $this->line("#{$position} {$entry->user->name}: {$entry->score} points");
        }

        $this->newLine();
        $this->info('🔥 Recent Activity:');
        $recentSubmissions = WordScrambleSubmission::with(['user', 'puzzle'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        foreach ($recentSubmissions as $submission) {
            $userName = $submission->user ? $submission->user->name : 'Guest';
            $date = $submission->puzzle->date;
            $this->line("• {$userName} found '{$submission->word}' ({$submission->score} pts) on {$date}");
        }

        $this->newLine();
        $this->info('🎖️ Badge System:');
        $badgeStats = DB::table('user_badges')
            ->join('badges', 'user_badges.badge_id', '=', 'badges.id')
            ->select('badges.name', DB::raw('count(*) as awarded_count'))
            ->groupBy('badges.id', 'badges.name')
            ->orderBy('awarded_count', 'desc')
            ->get();

        foreach ($badgeStats as $badge) {
            $this->line("🏅 {$badge->name}: {$badge->awarded_count} users");
        }

        $this->newLine();
        $this->info('✨ System Ready for Testing!');
        $this->info('==========================================');
        $this->line('🌐 Visit: http://daily-games-platform.test');
        $this->line('👤 Login: test@example.com / password');
        $this->line('🎮 Try playing today\'s word scramble puzzle');
        $this->line('📊 Check leaderboards and user stats');
        $this->line('🏆 View badges and achievements');
        $this->info('==========================================');

        return 0;
    }
}
