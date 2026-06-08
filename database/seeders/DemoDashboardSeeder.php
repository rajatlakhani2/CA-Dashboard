<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Organization;
use App\Models\PersonalRenewal;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Support\DemoWorkspace;
use App\Support\ModuleAccess;
use App\Support\OrganizationContext;
use Illuminate\Database\Seeder;

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

        Setting::set('company_name', 'Apex Advisory');
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

        $neha = User::withoutGlobalScopes()->updateOrCreate(
            [
                'email' => 'neha@demo.vouchex.in',
                'organization_id' => $organization->id,
            ],
            [
                'name' => 'Neha Kapoor',
                'role' => 'associate',
                'password' => DemoWorkspace::PASSWORD,
                'mobile' => '919888000101',
                'module_access' => ModuleAccess::defaultsForRole('associate'),
            ]
        );

        $amit = User::withoutGlobalScopes()->updateOrCreate(
            [
                'email' => 'amit@demo.vouchex.in',
                'organization_id' => $organization->id,
            ],
            [
                'name' => 'Amit Verma',
                'role' => 'associate',
                'password' => DemoWorkspace::PASSWORD,
                'mobile' => '919888000102',
                'module_access' => ModuleAccess::defaultsForRole('associate'),
            ]
        );

        $client = Client::withoutGlobalScopes()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'client_code' => 'DEMO-ACME',
            ],
            [
                'name' => 'Acme Corp',
                'status' => Client::STATUS_ACTIVE,
                'approval_status' => Client::APPROVAL_APPROVED,
                'primary_contact_email' => 'contact@acme.demo',
                'primary_contact_phone' => '9876543210',
                'category' => 'A',
            ]
        );

        $taskTitles = [
            ['title' => 'Client proposal — Acme Corp', 'assignee' => $neha->id, 'days' => 3],
            ['title' => 'Review contract — Brightline Ltd', 'assignee' => $neha->id, 'days' => 5],
            ['title' => 'Follow-up call — Nova Systems', 'assignee' => $neha->id, 'days' => 7],
            ['title' => 'Prepare board deck', 'assignee' => $neha->id, 'days' => 4],
            ['title' => 'Data cleanup — internal', 'assignee' => $neha->id, 'days' => 2],
            ['title' => 'Onboarding checklist', 'assignee' => $amit->id, 'days' => 6],
            ['title' => 'Vendor reconciliation', 'assignee' => $amit->id, 'days' => 8],
            ['title' => 'Team stand-up notes', 'assignee' => $amit->id, 'days' => 1],
        ];

        foreach ($taskTitles as $row) {
            Task::withoutGlobalScopes()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'title' => $row['title'],
                ],
                [
                    'client_id' => str_contains($row['title'], 'Acme') ? $client->id : null,
                    'assigned_to' => $row['assignee'],
                    'status' => Task::STATUS_PENDING,
                    'priority' => 'High',
                    'due_date' => now()->addDays($row['days']),
                    'created_by' => $demoUser->id,
                    'is_billed' => false,
                ]
            );
        }

        Task::withoutGlobalScopes()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'title' => 'Quarterly review — Acme Corp',
            ],
            [
                'client_id' => $client->id,
                'assigned_to' => $neha->id,
                'status' => Task::STATUS_COMPLETED,
                'priority' => 'Normal',
                'due_date' => now()->subDays(2),
                'created_by' => $demoUser->id,
                'is_billed' => false,
            ]
        );

        PersonalRenewal::updateOrCreate(
            ['title' => 'Professional indemnity insurance', 'user_id' => $demoUser->id],
            [
                'category' => 'Other',
                'due_date' => now()->addDays(14),
                'amount' => 25000,
                'frequency' => 'Annual',
                'status' => PersonalRenewal::STATUS_PENDING,
            ]
        );

        OrganizationContext::clear();
    }
}
