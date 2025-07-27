<?php

namespace App\Console\Commands;

use App\Services\Monitoring\PerformanceMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PerformanceReport extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'monitoring:performance-report {--hours=24 : Hours to include in the report} {--health : Include system health check}';

    /**
     * The console command description.
     */
    protected $description = 'Generate a performance monitoring report';

    public function __construct(
        protected PerformanceMonitoringService $performanceMonitoringService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $includeHealth = $this->option('health');

        $this->info("Generating performance report for the last {$hours} hours...");

        try {
            $stats = $this->performanceMonitoringService->getPerformanceStats($hours);
            
            $this->displayPerformanceReport($stats);
            
            if ($includeHealth) {
                $this->displayHealthCheck();
            }
            
            $this->info('Performance report generated successfully.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate performance report: ' . $e->getMessage());
            Log::channel('error_tracking')->error('Performance Report Generation Failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Display the performance report in the console
     */
    protected function displayPerformanceReport(array $stats): void
    {
        $this->line('');
        $this->line('=== PERFORMANCE MONITORING REPORT ===');
        $this->line('');
        
        // Response Times
        $this->line('=== API RESPONSE TIMES ===');
        $this->table(['Metric', 'Value'], [
            ['Average Response Time', round($stats['response_times']['average_ms'], 2) . ' ms'],
            ['Total Requests', number_format($stats['response_times']['total_requests'])],
            ['Slow Requests', number_format($stats['slow_requests'])],
        ]);

        $this->line('');
        
        // Database Queries
        $this->line('=== DATABASE PERFORMANCE ===');
        $this->table(['Metric', 'Value'], [
            ['Average Query Time', round($stats['database_queries']['average_ms'], 2) . ' ms'],
            ['Total Queries', number_format($stats['database_queries']['total_queries'])],
            ['Slow Queries', number_format($stats['slow_queries'])],
        ]);

        $this->line('');
        
        // Memory Usage
        $this->line('=== MEMORY USAGE ===');
        $this->table(['Metric', 'Value'], [
            ['Current Memory Usage', $stats['memory_usage']['current_mb'] . ' MB'],
            ['Peak Memory Usage', $stats['memory_usage']['peak_mb'] . ' MB'],
        ]);

        $this->line('');
        $this->line('Report Period: ' . $stats['period_hours'] . ' hours');
        $this->line('Last Updated: ' . $stats['last_updated']);
    }

    /**
     * Display system health check
     */
    protected function displayHealthCheck(): void
    {
        $this->line('');
        $this->line('=== SYSTEM HEALTH CHECK ===');
        $this->line('');

        $health = $this->performanceMonitoringService->checkSystemHealth();
        
        $statusColor = $health['status'] === 'healthy' ? 'green' : 'red';
        $this->line('<fg=' . $statusColor . '>Overall Status: ' . strtoupper($health['status']) . '</>');
        $this->line('');

        $healthData = [];
        foreach ($health['checks'] as $component => $check) {
            $status = $check['status'] === 'healthy' ? '✓' : '✗';
            $statusColor = $check['status'] === 'healthy' ? 'green' : 'red';
            
            $healthData[] = [
                'Component' => ucfirst(str_replace('_', ' ', $component)),
                'Status' => "<fg={$statusColor}>{$status} " . ucfirst($check['status']) . '</>',
                'Message' => $check['message'] ?? 'N/A',
            ];
        }
        
        $this->table(['Component', 'Status', 'Message'], $healthData);
        
        $this->line('');
        $this->line('Health Check Time: ' . $health['timestamp']);
    }
}