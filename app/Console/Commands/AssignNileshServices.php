<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Service;
use App\Models\ClientService;

class AssignNileshServices extends Command
{
    protected $signature = 'assign:folder-import-services';
    protected $description = 'Assign IT and GST services to clients imported from folders';

    public function handle()
    {
        $clients = Client::query()
            ->where(function ($query) {
                $query->where('tags', 'like', '%"folder-import"%')
                    ->orWhere('tags', 'like', '%"Nileshbhai client"%');
            })
            ->get();

        $this->info('Found '.$clients->count().' folder-import clients.');

        // 2. Define Services
        // ID 3 = IT Return (Non-Audit)
        // ID 1 = GSTR-1
        // ID 2 = GSTR-3B
        $serviceIds = [3, 1, 2];

        $bar = $this->output->createProgressBar($clients->count());
        $bar->start();

        foreach ($clients as $client) {
            foreach ($serviceIds as $sId) {
                // Check if already assigned
                $exists = ClientService::where('client_id', $client->id)
                    ->where('service_id', $sId)
                    ->exists();

                if (!$exists) {
                    ClientService::create([
                        'client_id' => $client->id,
                        'service_id' => $sId,
                        'status' => ClientService::STATUS_ACTIVE,
                        'custom_due_day' => null // Use service default
                    ]);
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Assigned IT & GST services to folder-import clients.');
    }
}
