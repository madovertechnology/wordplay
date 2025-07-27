<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class CleanupSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-sessions {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old sessions and optimize database to prevent cookie size issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Starting session cleanup and database optimization...');

        // Clean up old sessions
        $this->cleanupOldSessions();

        // Clean up old guest data
        $this->cleanupOldGuestData();

        // Optimize database tables
        $this->optimizeDatabase();

        // Clear application caches
        $this->clearCaches();

        $this->info('✅ Session cleanup and database optimization completed!');
    }

    /**
     * Clean up old sessions
     */
    private function cleanupOldSessions(): void
    {
        $this->info('Cleaning up old sessions...');

        // Clean up database sessions (if using database driver)
        if (config('session.driver') === 'database') {
            $deleted = DB::table('sessions')
                ->where('last_activity', '<', now()->subDays(7))
                ->delete();

            $this->info("Deleted {$deleted} old database sessions");
        }

        // Clean up Redis sessions (if using Redis driver)
        if (config('session.driver') === 'redis') {
            // Redis sessions are automatically expired, but we can clear any orphaned keys
            $this->info('Redis sessions are automatically managed');
        }

        // Clean up file sessions (if using file driver)
        if (config('session.driver') === 'file') {
            $sessionPath = storage_path('framework/sessions');
            if (is_dir($sessionPath)) {
                $files = glob($sessionPath . '/*');
                $deleted = 0;

                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < now()->subDays(7)->timestamp) {
                        unlink($file);
                        $deleted++;
                    }
                }

                $this->info("Deleted {$deleted} old session files");
            }
        }
    }

    /**
     * Clean up old guest data
     */
    private function cleanupOldGuestData(): void
    {
        $this->info('Cleaning up old guest data...');

        // Delete old guest records (older than 30 days)
        $deleted = DB::table('guests')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $this->info("Deleted {$deleted} old guest records");

        // Clean up old guest data
        $deleted = DB::table('guest_data')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $this->info("Deleted {$deleted} old guest data records");
    }

    /**
     * Optimize database tables
     */
    private function optimizeDatabase(): void
    {
        $this->info('Optimizing database tables...');

        $tables = [
            'sessions',
            'guests',
            'guest_data',
            'users',
            'games',
            'word_scramble_puzzles',
            'word_scramble_submissions',
            'word_scramble_words',
            'streaks',
            'leaderboards',
            'badges',
            'user_game_stats'
        ];

        foreach ($tables as $table) {
            try {
                // PostgreSQL uses VACUUM instead of OPTIMIZE
                DB::statement("VACUUM ANALYZE {$table}");
                $this->info("Optimized table: {$table}");
            } catch (\Exception $e) {
                $this->warn("Could not optimize table {$table}: " . $e->getMessage());
            }
        }
    }

    /**
     * Clear application caches
     */
    private function clearCaches(): void
    {
        $this->info('Clearing application caches...');

        // Clear Laravel caches
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Clear Redis cache if using Redis
        if (config('cache.default') === 'redis') {
            Cache::flush();
            $this->info('Cleared Redis cache');
        }

        $this->info('Application caches cleared');
    }
}
