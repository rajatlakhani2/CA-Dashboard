<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Service;
use App\Models\ClientService;

class AssignNileshServices extends Command
{
    protected $signature = 'assign:nilesh-services';
    protected $description = 'Assign IT and GST services to Nileshbhai clients';

    public function handle()
    {
        // 1. Find Clients
        // We look for clients where 'tags' JSON contains "Nileshbhai client"
        // SQLite doesn't support JSON_CONTAINS efficiently in all versions, 
        // so we'll fetch all and filter in PHP for safety or use LIKE if simple.

        $clients = Client::where('tags', 'like', '%"Nileshbhai client"%')->get();

        $this->info("Found " . $clients->count() . " Nileshbhai clients.");

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
                        'status' => 'Active',
                        'custom_due_day' => null // Use service default
                    ]);
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Assigned IT & GST Services to all Nileshbhai clients.");
    }
}
