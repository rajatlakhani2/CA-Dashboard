<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Support\DemoWorkspace;
use App\Support\ModuleAccess;
use App\Support\OrganizationContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDashboardSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::withoutGlobalScopes()->updateOrCreate(
            ['slug' => DemoWorkspace::SLUG],
            [
                'name' => 'Vouchex Demo Firm',
                'plan' => Organization::PLAN_PROFESSIONAL,
                'seat_limit' => 25,
                'is_active' => true,
                'is_demo' => true,
            ]
        );

        OrganizationContext::set($organization->id);

        Setting::set('company_name', 'Vouchex Demo Firm');
        Setting::set('workspace_type', 'ca_firm');

        $demoUser = User::withoutGlobalScopes()->updateOrCreate(
            [
                'email' => DemoWorkspace::EMAIL,
                'organization_id' => $organization->id,
            ],
            [
                'name' => 'Demo Partner',
                'role' => 'partner',
                'password' => DemoWorkspace::PASSWORD,
                'mobile' => '919999000099',
                'module_access' => ModuleAccess::defaultsForRole('partner'),
                'demo_tour_completed_at' => null,
            ]
        );

        $demoUser->forceFill(['password' => DemoWorkspace::PASSWORD, 'demo_tour_completed_at' => null])->save();

        $client = Client::withoutGlobalScopes()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'ABC Technologies Pvt Ltd',
            ],
            [
                'client_code' => 'DEMO-ABC',
                'status' => Client::STATUS_ACTIVE,
                'primary_contact_email' => 'contact@abctech.demo',
            ]
        );

        Task::withoutGlobalScopes()->firstOrCreate(
            ['title' => 'GSTR-3B filing — June 2026'],
            [
                'client_id' => $client->id,
                'assigned_to' => null,
                'status' => Task::STATUS_PENDING,
                'priority' => 'High',
                'due_date' => now()->addDays(7),
                'created_by' => $demoUser->id,
            ]
        );

        OrganizationContext::clear();
    }
}
