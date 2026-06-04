<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SystemController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isPartner(), 403);
    }

    public function index()
    {
        $this->ensureAdmin();

        // System Info
        $phpVersion = phpversion();
        $laravelVersion = app()->version();
        $environment = app()->environment();

        // Database Check
        try {
            DB::connection()->getPdo();
            $dbStatus = 'Connected';
            $dbName = DB::connection()->getDatabaseName();
        } catch (\Exception $e) {
            $dbStatus = 'Error: ' . $e->getMessage();
            $dbName = 'Unknown';
        }

        // Log Reader
        $logPath = storage_path('logs/laravel.log');
        $logs = [];
        if (File::exists($logPath)) {
            // Read only last 20KB to avoid memory issues with large logs
            $fp = fopen($logPath, 'r');
            fseek($fp, -20000, SEEK_END);
            $content = fread($fp, 20000);
            fclose($fp);

            $lines = explode("\n", $content);
            $logs = array_slice($lines, -50);
            $logs = array_reverse($logs);
        }

        // Fetch existing backups
        $backupDir = storage_path('app/private/backups');
        $backups = [];
        if (File::exists($backupDir)) {
            $files = File::files($backupDir);
            foreach ($files as $file) {
                if ($file->getExtension() === 'zip' && str_starts_with($file->getFilename(), 'backup-')) {
                    $mtime = File::lastModified($file->getRealPath());
                    $size = File::size($file->getRealPath());
                    
                    // Format size
                    $units = ['B', 'KB', 'MB', 'GB'];
                    $formattedSize = $size;
                    for ($i = 0; $formattedSize >= 1024 && $i < count($units) - 1; $i++) {
                        $formattedSize /= 1024;
                    }
                    $formattedSize = round($formattedSize, 2) . ' ' . $units[$i];

                    // Age calculations
                    $diff = time() - $mtime;
                    $daysOld = floor($diff / 86400);
                    if ($daysOld < 1) {
                        $hoursOld = floor($diff / 3600);
                        if ($hoursOld < 1) {
                            $ageStr = 'Just now';
                        } else {
                            $ageStr = $hoursOld . ' hour' . ($hoursOld > 1 ? 's' : '') . ' old';
                        }
                    } else {
                        $ageStr = $daysOld . ' day' . ($daysOld > 1 ? 's' : '') . ' old';
                    }

                    $backups[] = [
                        'filename' => $file->getFilename(),
                        'size' => $formattedSize,
                        'created_at' => date('d M Y H:i:s', $mtime),
                        'days_old' => $daysOld,
                        'age' => $ageStr,
                        'mtime' => $mtime, // to sort by
                    ];
                }
            }
            // Sort by mtime descending (newest first)
            usort($backups, function ($a, $b) {
                return $b['mtime'] <=> $a['mtime'];
            });
        }

        $taskCreateView = resource_path('views/tasks/create.blade.php');
        $taskCreateUi = 'missing';
        if (File::exists($taskCreateView)) {
            $snippet = File::get($taskCreateView);
            $taskCreateUi = str_contains($snippet, 'Task UI v4') && str_contains($snippet, 'task-form-table')
                ? 'v4-table (latest)'
                : (str_contains($snippet, 'Task UI v3') && str_contains($snippet, 'taskCreateForm')
                ? 'v3-single-screen'
                : (str_contains($snippet, 'taskCreateForm')
                    ? 'older-searchable (pull latest + sync script)'
                    : 'v1-legacy (old form — run sync script)'));
        }

        $appPath = base_path();
        $gitHead = base_path('.git/HEAD');
        $deployRef = File::exists($gitHead) ? trim(File::get($gitHead)) : 'not a git clone';

        return view('system.index', compact(
            'phpVersion',
            'laravelVersion',
            'environment',
            'dbStatus',
            'dbName',
            'logs',
            'backups',
            'taskCreateUi',
            'appPath',
            'deployRef',
        ));
    }

    public function clearCache()
    {
        $this->ensureAdmin();

        Artisan::call('optimize:clear');

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        return back()->with('success', 'All caches cleared (views, config, routes). Refresh the browser with Ctrl+F5.');
    }

    public function optimize()
    {
        $this->ensureAdmin();

        Artisan::call('optimize');

        return back()->with('success', 'System optimized successfully!');
    }

    public function migrate()
    {
        $this->ensureAdmin();

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            return back()->with('success', 'Migrations run successfully! Output: ' . $output);
        } catch (\Exception $e) {
            return back()->with('warning', 'Migration failed: ' . $e->getMessage());
        }
    }

    public function runBackup(\App\Services\SensitiveActionLogger $audit)
    {
        $this->ensureAdmin();

        try {
            Artisan::call('backup:run');
            $audit->systemBackup('manual_ui');

            return back()->with('success', 'Manual backup completed successfully!');
        } catch (\Exception $e) {
            return back()->with('warning', 'Backup run failed: ' . $e->getMessage());
        }
    }

    public function downloadBackup(string $filename)
    {
        $this->ensureAdmin();

        // Prevent path traversal attacks
        if (basename($filename) !== $filename) {
            abort(400, 'Invalid filename.');
        }

        $filePath = storage_path('app/private/backups/' . $filename);

        if (!File::exists($filePath)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($filePath);
    }

    public function deleteBackup(string $filename)
    {
        $this->ensureAdmin();

        // Prevent path traversal attacks
        if (basename($filename) !== $filename) {
            abort(400, 'Invalid filename.');
        }

        $filePath = storage_path('app/private/backups/' . $filename);

        if (File::exists($filePath)) {
            File::delete($filePath);
            return back()->with('success', 'Backup file deleted successfully.');
        }

        return back()->with('warning', 'Backup file not found.');
    }
}
