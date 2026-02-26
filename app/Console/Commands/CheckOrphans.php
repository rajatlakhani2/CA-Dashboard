<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceDue;
use App\Models\Task;

class CheckOrphans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-orphans {--fix : Delete identified orphan records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for orphan ServiceDue and Task records in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Checking for orphan records...");

        // 1. ServiceDue missing clientService
        $orphans = ServiceDue::doesntHave('clientService')->count();
        $this->line("Orphan ServiceDues (missing clientService): <info>{$orphans}</info>");

        if ($orphans > 0) {
            $ids = ServiceDue::doesntHave('clientService')->pluck('id')->implode(', ');
            $this->comment("IDs: $ids");
        }

        // 2. ServiceDue -> clientService missing client
        $orphansClient = ServiceDue::whereHas('clientService', function ($q) {
            $q->doesntHave('client');
        })->count();
        $this->line("Orphan ServiceDues (clientService missing client): <info>{$orphansClient}</info>");

        // Fix Logic for Case #2
        if ($orphansClient > 0) {
            if ($this->option('fix')) {
                $deleted = ServiceDue::whereHas('clientService', function ($q) {
                    $q->doesntHave('client');
                })->delete();
                $this->success("Deleted {$deleted} orphan ServiceDue(s).");
            } else {
                $this->warn("Use --fix to delete these records.");
            }
        }

        // 3. ServiceDue -> clientService missing service
        $orphansService = ServiceDue::whereHas('clientService', function ($q) {
            $q->doesntHave('service');
        })->count();
        $this->line("Orphan ServiceDues (clientService missing service): <info>{$orphansService}</info>");

        // 4. Task missing client
        $orphansTask = Task::doesntHave('client')->count();
        $this->line("Orphan Tasks (missing client): <info>{$orphansTask}</info>");

        if ($orphansTask > 0) {
            $ids = Task::doesntHave('client')->pluck('id')->implode(', ');
            $this->comment("Task IDs: $ids");
        }

        $this->info("Done.");
    }

    private function success($message)
    {
        $this->output->writeln("<bg=green;fg=black> OK </> $message");
    }
}
