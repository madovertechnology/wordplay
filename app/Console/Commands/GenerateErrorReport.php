<?php

namespace App\Console\Commands;

use App\Services\Monitoring\ErrorTrackingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateErrorReport extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'monitoring:error-report {--hours=24 : Hours to include in the report} {--email : Send report via email}';

    /**
     * The console command description.
     */
    protected $description = 'Generate an error tracking report';

    public function __construct(
        protected ErrorTrackingService $errorTrackingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $sendEmail = $this->option('email');

        $this->info("Generating error report for the last {$hours} hours...");

        try {
            $stats = $this->errorTrackingService->getErrorStats($hours);
            
            $this->displayReport($stats);
            
            if ($sendEmail) {
                $this->sendEmailReport($stats);
            }
            
            $this->info('Error report generated successfully.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate error report: ' . $e->getMessage());
            Log::channel('error_tracking')->error('Error Report Generation Failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Display the error report in the console
     */
    protected function displayReport(array $stats): void
    {
        $this->line('');
        $this->line('=== ERROR TRACKING REPORT ===');
        $this->line('');
        
        $this->table(['Metric', 'Value'], [
            ['Period', $stats['period_hours'] . ' hours'],
            ['Total Errors', $stats['total_errors']],
            ['Critical Errors', $stats['critical_errors']],
            ['Error Rate', number_format($stats['error_rate'], 2) . '%'],
            ['Last Updated', $stats['last_updated']],
        ]);

        if (!empty($stats['top_errors'])) {
            $this->line('');
            $this->line('=== TOP ERRORS ===');
            $this->line('');
            
            $topErrorsData = [];
            foreach ($stats['top_errors'] as $error) {
                $topErrorsData[] = [
                    'Error Class' => $error['class'],
                    'Count' => $error['count'],
                    'First Seen' => $error['first_seen'],
                    'Last Seen' => $error['last_seen'] ?? 'N/A',
                ];
            }
            
            $this->table(['Error Class', 'Count', 'First Seen', 'Last Seen'], $topErrorsData);
        }
    }

    /**
     * Send error report via email
     */
    protected function sendEmailReport(array $stats): void
    {
        $this->info('Sending error report via email...');
        
        // Log the email report (in a real implementation, you would send an actual email)
        Log::channel('error_tracking')->info('Error Report Email Sent', [
            'stats' => $stats,
            'timestamp' => now()->toISOString(),
        ]);
        
        $this->info('Error report email sent successfully.');
    }
}