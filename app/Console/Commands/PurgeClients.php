<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;

class PurgeClients extends Command
{
    protected $signature = 'clients:purge
                            {--group= : Delete clients with this group_name / reference (e.g. imported-portfolio)}
                            {--all : Delete every client (requires --force)}
                            {--dry-run : Show count only, do not delete}
                            {--force : Skip interactive confirmation}';

    protected $description = 'One-time delete clients by group reference or all (soft delete)';

    public function handle(): int
    {
        if ($this->option('all')) {
            $query = Client::query();
            $label = 'ALL clients';
        } elseif ($group = $this->option('group')) {
            $query = Client::query()->where('group_name', $group);
            $label = "clients with group_name \"{$group}\"";
        } else {
            $this->error('Specify --group=<reference> or --all (with --force).');

            return self::FAILURE;
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info("No {$label} found.");

            return self::SUCCESS;
        }

        $this->warn("Will delete {$count} {$label}.");

        if ($this->option('dry-run')) {
            $this->info('Dry run — no records deleted.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('This cannot be undone. Continue?')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $query->chunkById(100, function ($clients) use (&$deleted) {
            foreach ($clients as $client) {
                $client->delete();
                $deleted++;
            }
        });

        $this->info("Deleted {$deleted} client(s).");

        return self::SUCCESS;
    }
}
