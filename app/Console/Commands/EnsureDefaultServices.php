<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\DefaultServicesCatalog;
use Illuminate\Console\Command;

class EnsureDefaultServices extends Command
{
    protected $signature = 'services:ensure-defaults';

    protected $description = 'Create IT Return, GST Return, and other standard services if missing';

    public function handle(): int
    {
        DefaultServicesCatalog::ensureExists();

        Service::query()->orderBy('name')->each(function (Service $service) {
            $this->line("{$service->code} — {$service->name}");
        });

        $this->info('Standard services are ready for client import.');

        return self::SUCCESS;
    }
}
