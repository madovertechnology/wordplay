<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--compress} {--retention=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a backup of the PostgreSQL database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database backup...');

        $config = config('database.connections.' . config('database.default'));
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$timestamp}.sql";
        
        if ($this->option('compress')) {
            $filename .= '.gz';
        }

        // Create backup directory if it doesn't exist
        $backupPath = storage_path('app/backups');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $fullPath = $backupPath . '/' . $filename;

        // Build pg_dump command
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -d %s --no-owner --no-privileges',
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database'])
        );

        if ($this->option('compress')) {
            $command .= ' | gzip';
        }

        $command .= ' > ' . escapeshellarg($fullPath);

        // Execute backup command
        $this->info("Creating backup: {$filename}");
        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            $size = $this->formatBytes(filesize($fullPath));
            $this->info("Backup created successfully: {$filename} ({$size})");
            
            // Clean up old backups based on retention policy
            $this->cleanupOldBackups($this->option('retention'));
            
            return Command::SUCCESS;
        } else {
            $this->error('Backup failed!');
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            return Command::FAILURE;
        }
    }

    /**
     * Clean up old backup files based on retention policy
     */
    private function cleanupOldBackups(int $retentionDays): void
    {
        $backupPath = storage_path('app/backups');
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        
        $files = glob($backupPath . '/backup_*.sql*');
        $deletedCount = 0;
        
        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(filemtime($file));
            if ($fileTime->lt($cutoffDate)) {
                unlink($file);
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old backup(s)");
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
