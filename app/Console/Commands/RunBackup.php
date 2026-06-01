<?php

namespace App\Console\Commands;

use App\Services\SensitiveActionLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class RunBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup SQLite database and upload assets, then prune backups older than 1 month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting application backup...');
        Log::info('Backup Process: Started manual or scheduled run.');

        // 1. Prepare backup directory
        $backupDir = storage_path('app/private/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        // 2. Validate DB configuration
        $connection = config('database.default');
        if ($connection !== 'sqlite') {
            $msg = "Backup failed: Currently, only SQLite databases are supported. Connection is: {$connection}";
            $this->error($msg);
            Log::error($msg);
            return Command::FAILURE;
        }

        $dbConfigPath = config('database.connections.sqlite.database');
        $isMemory = ($dbConfigPath === ':memory:');
        $dbPath = '';

        if (!$isMemory) {
            $dbPath = File::exists($dbConfigPath) ? $dbConfigPath : base_path($dbConfigPath);

            if (!File::exists($dbPath)) {
                $msg = "Backup failed: SQLite database file not found at: {$dbPath}";
                $this->error($msg);
                Log::error($msg);
                return Command::FAILURE;
            }
        }

        // 3. Prepare ZIP file
        $timestamp = date('Y-m-d_H-i-s');
        $zipFilename = "backup-{$timestamp}.zip";
        $zipPath = "{$backupDir}/{$zipFilename}";

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $msg = "Backup failed: Cannot create zip file at: {$zipPath}";
            $this->error($msg);
            Log::error($msg);
            return Command::FAILURE;
        }

        // 4. Add Database to ZIP
        // In SQLite, copy of DB is fine. To prevent locking issues we can just copy to a temp location and zip.
        $tempDbCopy = tempnam(sys_get_temp_dir(), 'sqlite_backup_');
        if ($isMemory) {
            File::put($tempDbCopy, 'MOCK SQLITE MEMORY DATABASE CONTENT');
        } else {
            File::copy($dbPath, $tempDbCopy);
        }
        $zip->addFile($tempDbCopy, 'database.sqlite');
        $this->info('Database added to zip.');

        // 5. Add uploaded assets to ZIP (from storage/app/public)
        $publicUploadsDir = storage_path('app/public');
        $assetsCount = 0;
        if (File::exists($publicUploadsDir)) {
            $files = File::allFiles($publicUploadsDir);
            foreach ($files as $file) {
                // Ensure we don't accidentally zip backup directory if it's there
                $relativePath = 'uploads/' . $file->getRelativePathname();
                $zip->addFile($file->getRealPath(), $relativePath);
                $assetsCount++;
            }
        }
        $this->info("Uploaded assets added: {$assetsCount} file(s).");

        // Close ZIP and clean up temp copy
        $zip->close();
        if (File::exists($tempDbCopy)) {
            File::delete($tempDbCopy);
        }

        $formattedSize = $this->formatSize(File::size($zipPath));
        $msg = "Backup created successfully: {$zipFilename} (Size: {$formattedSize}, Assets: {$assetsCount})";
        $this->info($msg);
        Log::info("Backup Process: {$msg}");

        // 6. Prune old backups (Retention: 1 month / 30 days)
        $this->pruneOldBackups($backupDir);

        app(SensitiveActionLogger::class)->systemBackup('cli');

        return Command::SUCCESS;
    }

    /**
     * Prune backups older than 30 days (1 month).
     */
    protected function pruneOldBackups(string $backupDir)
    {
        $this->info('Pruning backups older than 30 days...');
        $files = File::files($backupDir);
        $oneMonthAgo = now()->subDays(30)->timestamp;
        $prunedCount = 0;

        foreach ($files as $file) {
            if ($file->getExtension() === 'zip' && str_starts_with($file->getFilename(), 'backup-')) {
                $lastModified = File::lastModified($file->getRealPath());
                if ($lastModified < $oneMonthAgo) {
                    File::delete($file->getRealPath());
                    $this->info("Pruned old backup file: {$file->getFilename()}");
                    Log::info("Backup Process: Pruned old backup file {$file->getFilename()}");
                    $prunedCount++;
                }
            }
        }

        $this->info("Prune complete. Deleted {$prunedCount} file(s).");
    }

    /**
     * Format file bytes to human readable sizes.
     */
    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
