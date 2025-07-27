<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\Game\WordScramblePuzzleService;
use App\Services\Core\LeaderboardService;
use App\Services\Game\DictionaryService;
use Carbon\Carbon;

class CacheWarm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application caches with frequently accessed data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔥 Starting cache warming...');

        $warmed = 0;

        // Warm up today's Word Scramble puzzle
        $warmed += $this->warmWordScramblePuzzle();

        // Warm up leaderboards
        $warmed += $this->warmLeaderboards();

        // Warm up dictionary cache
        $warmed += $this->warmDictionary();

        // Warm up configuration cache
        $warmed += $this->warmConfiguration();

        $this->info("✅ Cache warming completed! Warmed {$warmed} cache entries.");

        return Command::SUCCESS;
    }

    /**
     * Warm up Word Scramble puzzle cache
     */
    private function warmWordScramblePuzzle(): int
    {
        try {
            $this->info('📝 Warming Word Scramble puzzle cache...');
            
            $puzzleService = app(WordScramblePuzzleService::class);
            $today = Carbon::today();
            
            // Get today's puzzle (this will cache it)
            $puzzle = $puzzleService->getTodaysPuzzle();
            
            if ($puzzle) {
                $this->info("   ✓ Cached today's puzzle: {$puzzle->letters}");
                return 1;
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->warn("   ⚠️ Failed to warm Word Scramble puzzle cache: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Warm up leaderboard caches
     */
    private function warmLeaderboards(): int
    {
        try {
            $this->info('🏆 Warming leaderboard caches...');
            
            $leaderboardService = app(LeaderboardService::class);
            $warmed = 0;
            
            $games = ['word-scramble'];
            $periods = ['daily', 'monthly', 'all-time'];
            
            foreach ($games as $gameSlug) {
                foreach ($periods as $period) {
                    try {
                        $leaderboard = $leaderboardService->getLeaderboard($gameSlug, $period, 10);
                        $this->info("   ✓ Cached {$gameSlug} {$period} leaderboard");
                        $warmed++;
                    } catch (\Exception $e) {
                        $this->warn("   ⚠️ Failed to cache {$gameSlug} {$period} leaderboard: {$e->getMessage()}");
                    }
                }
            }
            
            return $warmed;
        } catch (\Exception $e) {
            $this->warn("   ⚠️ Failed to warm leaderboard caches: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Warm up dictionary cache
     */
    private function warmDictionary(): int
    {
        try {
            $this->info('📚 Warming dictionary cache...');
            
            $dictionaryService = app(DictionaryService::class);
            
            // Cache some common words to warm up the dictionary
            $commonWords = ['the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was', 'one', 'our', 'out', 'day', 'get', 'has', 'him', 'his', 'how', 'man', 'new', 'now', 'old', 'see', 'two', 'way', 'who', 'boy', 'did', 'its', 'let', 'put', 'say', 'she', 'too', 'use'];
            
            $warmed = 0;
            foreach ($commonWords as $word) {
                if ($dictionaryService->isValidWord($word)) {
                    $warmed++;
                }
            }
            
            $this->info("   ✓ Cached {$warmed} dictionary entries");
            return 1; // Count as 1 cache entry
        } catch (\Exception $e) {
            $this->warn("   ⚠️ Failed to warm dictionary cache: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Warm up configuration cache
     */
    private function warmConfiguration(): int
    {
        try {
            $this->info('⚙️ Warming configuration cache...');
            
            // Cache frequently accessed configuration values
            $configs = [
                'app.name',
                'app.env',
                'app.debug',
                'database.default',
                'cache.default',
                'queue.default',
                'session.driver',
                'deployment.current',
            ];
            
            $warmed = 0;
            foreach ($configs as $config) {
                try {
                    config($config);
                    $warmed++;
                } catch (\Exception $e) {
                    // Ignore individual config failures
                }
            }
            
            $this->info("   ✓ Cached {$warmed} configuration values");
            return 1; // Count as 1 cache entry
        } catch (\Exception $e) {
            $this->warn("   ⚠️ Failed to warm configuration cache: {$e->getMessage()}");
            return 0;
        }
    }
}
