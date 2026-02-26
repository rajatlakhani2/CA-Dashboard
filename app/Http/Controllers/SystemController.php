<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SystemController extends Controller
{
    public function index()
    {
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

        return view('system.index', compact('phpVersion', 'laravelVersion', 'environment', 'dbStatus', 'dbName', 'logs'));
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');

        return back()->with('success', 'System cache cleared successfully!');
    }

    public function optimize()
    {
        Artisan::call('optimize');

        return back()->with('success', 'System optimized successfully!');
    }

    public function migrate()
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            return back()->with('success', 'Migrations run successfully! Output: ' . $output);
        } catch (\Exception $e) {
            return back()->with('warning', 'Migration failed: ' . $e->getMessage());
        }
    }
}
