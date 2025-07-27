<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DatabaseHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:health-check {--alert-threshold=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform database health checks and report issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database health check...');
        
        $issues = [];
        $alertThreshold = (int) $this->option('alert-threshold');

        // Check database connection
        if (!$this->checkConnection()) {
            $issues[] = 'Database connection failed';
        }

        // Check table sizes
        $tableSizes = $this->checkTableSizes();
        foreach ($tableSizes as $table => $size) {
            if ($size > $alertThreshold * 1000) { // Convert to MB
                $issues[] = "Table {$table} is large: {$size} MB";
            }
        }

        // Check for slow queries
        $slowQueries = $this->checkSlowQueries();
        if (count($slowQueries) > 0) {
            $issues[] = count($slowQueries) . ' slow queries detected';
        }

        // Check database locks
        $locks = $this->checkDatabaseLocks();
        if (count($locks) > 0) {
            $issues[] = count($locks) . ' database locks detected';
        }

        // Check connection pool
        $connections = $this->checkConnectionPool();
        if ($connections['active'] > $connections['max'] * 0.8) {
            $issues[] = 'High connection pool usage: ' . $connections['active'] . '/' . $connections['max'];
        }

        // Report results
        if (empty($issues)) {
            $this->info('✅ Database health check passed - no issues detected');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Database health check failed:');
            foreach ($issues as $issue) {
                $this->error("  - {$issue}");
            }
            
            // Log issues for monitoring
            Log::warning('Database health check failed', [
                'issues' => $issues,
                'timestamp' => Carbon::now(),
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Check database connection
     */
    private function checkConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            $this->info('✅ Database connection: OK');
            return true;
        } catch (\Exception $e) {
            $this->error('❌ Database connection: FAILED - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check table sizes
     */
    private function checkTableSizes(): array
    {
        try {
            $results = DB::select("
                SELECT 
                    schemaname,
                    tablename,
                    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size,
                    pg_total_relation_size(schemaname||'.'||tablename) as size_bytes
                FROM pg_tables 
                WHERE schemaname = 'public'
                ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
            ");

            $sizes = [];
            foreach ($results as $result) {
                $sizeInMB = round($result->size_bytes / 1024 / 1024, 2);
                $sizes[$result->tablename] = $sizeInMB;
                $this->info("📊 Table {$result->tablename}: {$result->size}");
            }

            return $sizes;
        } catch (\Exception $e) {
            $this->error('❌ Failed to check table sizes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check for slow queries
     */
    private function checkSlowQueries(): array
    {
        try {
            $results = DB::select("
                SELECT query, calls, total_time, mean_time
                FROM pg_stat_statements 
                WHERE mean_time > 1000
                ORDER BY mean_time DESC
                LIMIT 10
            ");

            if (!empty($results)) {
                $this->warn('⚠️  Slow queries detected:');
                foreach ($results as $query) {
                    $this->warn("  - Mean time: {$query->mean_time}ms, Calls: {$query->calls}");
                }
            } else {
                $this->info('✅ No slow queries detected');
            }

            return $results;
        } catch (\Exception $e) {
            // pg_stat_statements might not be enabled
            $this->info('ℹ️  Slow query check skipped (pg_stat_statements not available)');
            return [];
        }
    }

    /**
     * Check database locks
     */
    private function checkDatabaseLocks(): array
    {
        try {
            $results = DB::select("
                SELECT 
                    pg_class.relname,
                    pg_locks.locktype,
                    pg_locks.mode,
                    pg_locks.granted
                FROM pg_locks
                JOIN pg_class ON pg_locks.relation = pg_class.oid
                WHERE NOT pg_locks.granted
            ");

            if (!empty($results)) {
                $this->warn('⚠️  Database locks detected:');
                foreach ($results as $lock) {
                    $this->warn("  - Table: {$lock->relname}, Type: {$lock->locktype}, Mode: {$lock->mode}");
                }
            } else {
                $this->info('✅ No database locks detected');
            }

            return $results;
        } catch (\Exception $e) {
            $this->error('❌ Failed to check database locks: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check connection pool status
     */
    private function checkConnectionPool(): array
    {
        try {
            $result = DB::selectOne("
                SELECT 
                    count(*) as active_connections,
                    setting as max_connections
                FROM pg_stat_activity, pg_settings 
                WHERE pg_settings.name = 'max_connections'
                GROUP BY setting
            ");

            $active = $result->active_connections ?? 0;
            $max = $result->max_connections ?? 100;

            $this->info("🔗 Connection pool: {$active}/{$max} active connections");

            return [
                'active' => $active,
                'max' => $max,
            ];
        } catch (\Exception $e) {
            $this->error('❌ Failed to check connection pool: ' . $e->getMessage());
            return ['active' => 0, 'max' => 100];
        }
    }
}
