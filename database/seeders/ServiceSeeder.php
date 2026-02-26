<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\Client;
use App\Models\ClientService;
use Carbon\Carbon;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Define Standard Services
        $services = [
            [
                'name' => 'IT Return',
                'code' => 'ITR',
                'description' => 'Income Tax Return Filing',
                'frequency' => 'Annually',
                'due_day' => 31,
                'due_month' => 7, // July
                'is_statutory' => true,
            ],
            [
                'name' => 'Tax Audit',
                'code' => 'TAX_AUDIT',
                'description' => 'Tax Audit under Income Tax Act',
                'frequency' => 'Annually',
                'due_day' => 30,
                'due_month' => 9, // September
                'is_statutory' => true,
            ],
            [
                'name' => 'Statutory Audit',
                'code' => 'STAT_AUDIT',
                'description' => 'Company Statutory Audit',
                'frequency' => 'Annually',
                'due_day' => 30,
                'due_month' => 9, // September
                'is_statutory' => true,
            ],
            [
                'name' => 'GST Return',
                'code' => 'GST',
                'description' => 'Monthly GST Return (GSTR-3B)',
                'frequency' => 'Monthly',
                'due_day' => 20,
                'due_month' => null, // Monthly
                'is_statutory' => true,
            ],
            [
                'name' => 'GSTR-1 (Monthly)',
                'code' => 'GSTR1-M',
                'description' => 'Monthly GSTR-1 Return',
                'frequency' => 'Monthly',
                'due_day' => 11,
                'due_month' => null,
                'is_statutory' => true,
            ],
            [
                'name' => 'Other Services',
                'code' => 'OTHER',
                'description' => 'Consultancy and other ad-hoc services',
                'frequency' => 'One-Time',
                'due_day' => null,
                'due_month' => null,
                'is_statutory' => false,
            ],
        ];

        foreach ($services as $svc) {
            Service::updateOrCreate(['code' => $svc['code']], $svc);
        }

        // 2. Assign "IT Return" to ALL Clients (One-time consideration)
        $itReturn = Service::where('code', 'ITR')->first();
        $clients = Client::all();

        foreach ($clients as $client) {
            // Check if already assigned
            $exists = ClientService::where('client_id', $client->id)
                ->where('service_id', $itReturn->id)
                ->exists();

            if (!$exists) {
                ClientService::create([
                    'client_id' => $client->id,
                    'service_id' => $itReturn->id,
                    'status' => 'Active',
                ]);
            }
        }
    }
}
