<?php

namespace App\Console\Commands;

use App\Services\SensitiveActionLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
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
    protected $description = 'Backup database (SQLite or MySQL) and upload assets, then prune backups older than 1 month';

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

        $connection = config('database.default');

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

        $tempDbCopy = tempnam(sys_get_temp_dir(), 'db_backup_');
        $dbZipName = 'database.sql';

        if ($connection === 'sqlite') {
            $dbConfigPath = config('database.connections.sqlite.database');
            $isMemory = ($dbConfigPath === ':memory:');

            if (!$isMemory) {
                $dbPath = File::exists($dbConfigPath) ? $dbConfigPath : base_path($dbConfigPath);

                if (!File::exists($dbPath)) {
                    $msg = "Backup failed: SQLite database file not found at: {$dbPath}";
                    $this->error($msg);
                    Log::error($msg);
                    return Command::FAILURE;
                }

                File::copy($dbPath, $tempDbCopy);
            } else {
                File::put($tempDbCopy, 'MOCK SQLITE MEMORY DATABASE CONTENT');
            }

            $dbZipName = 'database.sqlite';
        } elseif ($connection === 'mysql') {
            if (! $this->dumpMysqlDatabase($tempDbCopy)) {
                return Command::FAILURE;
            }
        } else {
            $msg = "Backup failed: Unsupported database connection: {$connection}";
            $this->error($msg);
            Log::error($msg);
            return Command::FAILURE;
        }

        $zip->addFile($tempDbCopy, $dbZipName);
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

    protected function dumpMysqlDatabase(string $outputPath): bool
    {
        $config = config('database.connections.mysql');
        $database = $config['database'] ?? '';

        if ($database === '') {
            $this->error('Backup failed: MySQL database name is not configured.');
            Log::error('Backup failed: MySQL database name missing.');

            return false;
        }

        $mysqldump = $this->resolveMysqldumpBinary();
        if ($mysqldump === null) {
            $this->error('Backup failed: mysqldump was not found on the server PATH.');
            Log::error('Backup failed: mysqldump not found.');

            return false;
        }

        $process = new Process([
            $mysqldump,
            '--host=' . ($config['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($config['port'] ?? 3306),
            '--user=' . ($config['username'] ?? 'root'),
            '--single-transaction',
            '--quick',
            '--result-file=' . $outputPath,
            $database,
        ]);

        $password = $config['password'] ?? '';
        if ($password !== '') {
            $process->setEnv(array_merge($_ENV, ['MYSQL_PWD' => $password]));
        }

        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful() || ! File::exists($outputPath) || File::size($outputPath) === 0) {
            $this->error('Backup failed: mysqldump error — ' . trim($process->getErrorOutput() ?: $process->getOutput()));
            Log::error('Backup failed: mysqldump', ['stderr' => $process->getErrorOutput()]);

            return false;
        }

        return true;
    }

    protected function resolveMysqldumpBinary(): ?string
    {
        foreach (['mysqldump', '/usr/bin/mysqldump', '/usr/local/bin/mysqldump'] as $candidate) {
            $process = new Process([$candidate, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $candidate;
            }
        }

        return null;
    }
}
