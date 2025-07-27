<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestWebRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:web-routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test web routes to ensure they are working';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌐 Testing Web Routes');
        $this->info('====================');
        $this->newLine();

        $baseUrl = 'http://daily-games-platform.test';
        
        $routes = [
            '/' => 'Welcome page',
            '/health' => 'Health check',
            '/leaderboards/word-scramble' => 'Leaderboard page',
            '/games/word-scramble' => 'Word Scramble game',
        ];

        $allPassed = true;

        foreach ($routes as $route => $description) {
            try {
                $response = Http::timeout(10)->get($baseUrl . $route);
                
                if ($response->successful()) {
                    $this->line("✅ {$description}: {$route} (Status: {$response->status()})");
                } else {
                    $this->error("❌ {$description}: {$route} (Status: {$response->status()})");
                    $allPassed = false;
                }
            } catch (\Exception $e) {
                $this->error("❌ {$description}: {$route} (Error: {$e->getMessage()})");
                $allPassed = false;
            }
        }

        $this->newLine();
        
        // Test API routes
        $this->info('🔌 Testing API Routes');
        $this->info('====================');
        
        $apiRoutes = [
            '/games/word-scramble/api/puzzle' => 'Today\'s puzzle API',
            '/games/word-scramble/api/leaderboard/daily' => 'Daily leaderboard API',
            '/games/word-scramble/api/leaderboard/monthly' => 'Monthly leaderboard API',
            '/games/word-scramble/api/leaderboard/all-time' => 'All-time leaderboard API',
            '/leaderboards/api/word-scramble' => 'Leaderboard API',
        ];

        foreach ($apiRoutes as $route => $description) {
            try {
                $response = Http::timeout(10)->get($baseUrl . $route);
                
                if ($response->successful()) {
                    $this->line("✅ {$description}: {$route} (Status: {$response->status()})");
                } else {
                    $this->error("❌ {$description}: {$route} (Status: {$response->status()})");
                    $allPassed = false;
                }
            } catch (\Exception $e) {
                $this->error("❌ {$description}: {$route} (Error: {$e->getMessage()})");
                $allPassed = false;
            }
        }

        $this->newLine();
        
        if ($allPassed) {
            $this->info('✅ All routes are working correctly!');
            $this->info('🚀 System is ready for use at: ' . $baseUrl);
        } else {
            $this->error('❌ Some routes failed. Check the errors above.');
        }

        return $allPassed ? 0 : 1;
    }
}