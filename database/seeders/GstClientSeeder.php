<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Service;
use App\Models\ClientService;
use Carbon\Carbon;

class GstClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            "Amul",
            "Ketanbhai",
            "Dhavalbhai",
            "AMUL 2",
            "Manibhai",
            "Drashanaben- Ketanbhai",
            "Harsh adani",
            "Mubrak bhai",
            "Sadikbhai",
            "Fulchand",
            "Rekhaben",
            "GLR",
            "Shakiti Enterprise-Batukbhai",
            "Pareshbhai- Nileshbhai",
            "Ankitbhai Rajkot",
            "Nehaben",
            "miteshbhai- Nileshbhai",
            "parth auto- Nileshbhai",
            "Pravin bariya",
            "Givyesh Ram",
            "Bhoomi ben",
            "K & B websol pvt ltd",
            "R J Enterprise",
            "Harshit bhai",
            "Raghuvir Medical",
            "Kamdhennu",
            "gani HALA"
        ];

        // Ensure GST Service Exists
        $gstService = Service::where('code', 'GST')->first();

        if (!$gstService) {
            $this->command->error("GST Service not found! Please run ServiceSeeder first.");
            return;
        }

        foreach ($clients as $clientName) {
            // Create Client if not exists
            $client = Client::firstOrCreate(
                ['name' => $clientName],
                [
                    'client_code' => $this->generateClientCode(),
                    'pan' => 'PAN' . strtoupper(substr(md5($clientName), 0, 7)), // Dummy PAN
                    'category' => 'B', // Default Category
                    'status' => 'Active'
                ]
            );

            // Assign GST Service
            $clientService = ClientService::firstOrCreate(
                [
                    'client_id' => $client->id,
                    'service_id' => $gstService->id
                ],
                [
                    'status' => 'Active'
                ]
            );

            // Generate Service Due for Current Month (Feb 2026)
            $dueDate = Carbon::now()->setDay(20); // GST is usually due on 20th

            \App\Models\ServiceDue::firstOrCreate(
                [
                    'client_service_id' => $clientService->id,
                    'due_date' => $dueDate->format('Y-m-d')
                ],
                [
                    'status' => 'Pending'
                ]
            );
        }

        $this->command->info('GST Clients imported and assigned to GST Service.');
    }

    private function generateClientCode()
    {
        $lastClient = Client::latest('id')->first();
        $nextId = $lastClient ? $lastClient->id + 1 : 1;
        return 'CL-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
