<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class DiagnoseCookieIssue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:diagnose-cookie-issue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose cookie and header size issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Diagnosing cookie and header size issues...');

        // Check session configuration
        $this->checkSessionConfiguration();

        // Check database sessions
        $this->checkDatabaseSessions();

        // Check guest data
        $this->checkGuestData();

        // Check cache usage
        $this->checkCacheUsage();

        // Check file system
        $this->checkFileSystem();

        $this->info('✅ Diagnosis completed!');
    }

    /**
     * Check session configuration
     */
    private function checkSessionConfiguration(): void
    {
        $this->info('📋 Session Configuration:');
        $this->line('Driver: ' . config('session.driver'));
        $this->line('Lifetime: ' . config('session.lifetime') . ' minutes');
        $this->line('Cookie Name: ' . config('session.cookie'));
        $this->line('Secure: ' . (config('session.secure') ? 'Yes' : 'No'));
        $this->line('HTTP Only: ' . (config('session.http_only') ? 'Yes' : 'No'));
        $this->line('Same Site: ' . config('session.same_site'));
        $this->line('');
    }

    /**
     * Check database sessions
     */
    private function checkDatabaseSessions(): void
    {
        $this->info('🗄️ Database Sessions:');

        try {
            $totalSessions = DB::table('sessions')->count();
            $this->line("Total sessions: {$totalSessions}");

            if ($totalSessions > 0) {
                $oldSessions = DB::table('sessions')
                    ->where('last_activity', '<', now()->subDays(7)->timestamp)
                    ->count();
                $this->line("Old sessions (7+ days): {$oldSessions}");

                // Check for large sessions (PostgreSQL compatible)
                $largeSessions = DB::table('sessions')
                    ->whereRaw('LENGTH(payload::text) > 1000')
                    ->count();
                $this->line("Large sessions (>1KB): {$largeSessions}");

                // Get the largest session
                $largestSession = DB::table('sessions')
                    ->selectRaw('id, LENGTH(payload::text) as size, last_activity')
                    ->orderByRaw('LENGTH(payload::text) DESC')
                    ->first();

                if ($largestSession) {
                    $this->line("Largest session: {$largestSession->size} bytes (ID: {$largestSession->id})");
                    $this->line("Last activity: {$largestSession->last_activity}");
                }
            }
        } catch (\Exception $e) {
            $this->error("Error checking database sessions: " . $e->getMessage());
        }
        $this->line('');
    }

    /**
     * Check guest data
     */
    private function checkGuestData(): void
    {
        $this->info('👥 Guest Data:');

        try {
            $totalGuests = DB::table('guests')->count();
            $this->line("Total guests: {$totalGuests}");

            if ($totalGuests > 0) {
                $oldGuests = DB::table('guests')
                    ->where('created_at', '<', now()->subDays(30))
                    ->count();
                $this->line("Old guests (30+ days): {$oldGuests}");

                $totalGuestData = DB::table('guest_data')->count();
                $this->line("Total guest data records: {$totalGuestData}");

                if ($totalGuestData > 0) {
                    $largeGuestData = DB::table('guest_data')
                        ->whereRaw('LENGTH(value::text) > 500')
                        ->count();
                    $this->line("Large guest data (>500 bytes): {$largeGuestData}");

                    // Get the largest guest data
                    $largestGuestData = DB::table('guest_data')
                        ->selectRaw('id, guest_id, key, LENGTH(value::text) as size')
                        ->orderByRaw('LENGTH(value::text) DESC')
                        ->first();

                    if ($largestGuestData) {
                        $this->line("Largest guest data: {$largestGuestData->size} bytes");
                        $this->line("Key: {$largestGuestData->key}");
                        $this->line("Guest ID: {$largestGuestData->guest_id}");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("Error checking guest data: " . $e->getMessage());
        }
        $this->line('');
    }

    /**
     * Check cache usage
     */
    private function checkCacheUsage(): void
    {
        $this->info('💾 Cache Usage:');

        try {
            $driver = config('cache.default');
            $this->line("Cache driver: {$driver}");

            if ($driver === 'redis') {
                // Try to get Redis info
                $redis = Cache::getRedis();
                $info = $redis->info();

                if (isset($info['used_memory_human'])) {
                    $this->line("Redis memory usage: " . $info['used_memory_human']);
                }

                if (isset($info['db0'])) {
                    $this->line("Redis keys: " . $info['db0']);
                }
            }
        } catch (\Exception $e) {
            $this->error("Error checking cache: " . $e->getMessage());
        }
        $this->line('');
    }

    /**
     * Check file system
     */
    private function checkFileSystem(): void
    {
        $this->info('📁 File System:');

        try {
            // Check session files if using file driver
            if (config('session.driver') === 'file') {
                $sessionPath = storage_path('framework/sessions');
                if (is_dir($sessionPath)) {
                    $files = glob($sessionPath . '/*');
                    $this->line("Session files: " . count($files));

                    $totalSize = 0;
                    foreach ($files as $file) {
                        $totalSize += filesize($file);
                    }
                    $this->line("Total session file size: " . $this->formatBytes($totalSize));
                }
            }

            // Check for cookie files
            $cookieFiles = glob(base_path('cookies*.txt'));
            $this->line("Cookie files in root: " . count($cookieFiles));

            if (count($cookieFiles) > 0) {
                $this->warn("⚠️  Cookie files found in repository root!");
                foreach ($cookieFiles as $file) {
                    $size = filesize($file);
                    $this->line("  - " . basename($file) . " (" . $this->formatBytes($size) . ")");
                }
            }
        } catch (\Exception $e) {
            $this->error("Error checking file system: " . $e->getMessage());
        }
        $this->line('');
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
