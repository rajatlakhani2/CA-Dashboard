<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Service;
use Illuminate\Console\Command;

class EnsureIncomeTaxReturnService extends Command
{
    protected $signature = 'services:ensure-income-tax-return {--assign-all : Assign to every client}';

    protected $description = 'Create Income Tax Return service and optionally assign to all clients';

    public function handle(): int
    {
        $service = Service::updateOrCreate(
            ['code' => 'ITR'],
            [
                'name' => 'Income Tax Return',
                'frequency' => 'Annually',
                'due_day' => 31,
                'is_statutory' => true,
            ]
        );

        $this->info("Service ready: {$service->name} (ID {$service->id})");

        if (! $this->option('assign-all')) {
            return self::SUCCESS;
        }

        $assigned = 0;
        Client::query()->chunkById(100, function ($clients) use ($service, &$assigned) {
            foreach ($clients as $client) {
                ClientService::firstOrCreate(
                    [
                        'client_id' => $client->id,
                        'service_id' => $service->id,
                    ],
                    ['status' => ClientService::STATUS_ACTIVE]
                );
                $assigned++;
            }
        });

        $this->info("Assigned Income Tax Return to {$assigned} client records.");

        return self::SUCCESS;
    }
}
