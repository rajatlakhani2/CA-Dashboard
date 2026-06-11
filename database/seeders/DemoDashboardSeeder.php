<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
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
                'name' => 'Rajat Lakhani',
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
                'name' => 'Ajay Dalki',
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
                'industry' => 'Advisory',
            ]
        );

        $abc = Client::withoutGlobalScopes()->updateOrCreate(
            ['organization_id' => $organization->id, 'client_code' => 'DEMO-ABC'],
            [
                'name' => 'ABC Pvt Ltd',
                'status' => Client::STATUS_ACTIVE,
                'approval_status' => Client::APPROVAL_APPROVED,
                'category' => 'A',
                'industry' => 'GST',
                'gst_applicable' => true,
            ]
        );

        $xyz = Client::withoutGlobalScopes()->updateOrCreate(
            ['organization_id' => $organization->id, 'client_code' => 'DEMO-XYZ'],
            [
                'name' => 'XYZ LLP',
                'status' => Client::STATUS_ACTIVE,
                'approval_status' => Client::APPROVAL_APPROVED,
                'category' => 'B',
                'industry' => 'TDS',
            ]
        );

        $pqr = Client::withoutGlobalScopes()->updateOrCreate(
            ['organization_id' => $organization->id, 'client_code' => 'DEMO-PQR'],
            [
                'name' => 'PQR Ltd',
                'status' => Client::STATUS_ACTIVE,
                'approval_status' => Client::APPROVAL_APPROVED,
                'category' => 'B',
                'industry' => 'ROC',
            ]
        );

        $showcaseTasks = [
            [
                'title' => 'GSTR-3B Filing',
                'client_id' => $abc->id,
                'assigned_to' => $demoUser->id,
                'priority' => 'High',
                'status' => Task::STATUS_IN_PROGRESS,
                'due_date' => now()->toDateString(),
            ],
            [
                'title' => 'TDS Return',
                'client_id' => $xyz->id,
                'assigned_to' => $amit->id,
                'priority' => 'Medium',
                'status' => Task::STATUS_PENDING,
                'due_date' => now()->addDays(5)->toDateString(),
            ],
            [
                'title' => 'ROC Filing',
                'client_id' => $pqr->id,
                'assigned_to' => null,
                'priority' => 'Normal',
                'status' => Task::STATUS_PENDING,
                'due_date' => now()->addDays(10)->toDateString(),
            ],
        ];

        foreach ($showcaseTasks as $row) {
            Task::withoutGlobalScopes()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'title' => $row['title'],
                ],
                [
                    'client_id' => $row['client_id'],
                    'assigned_to' => $row['assigned_to'],
                    'status' => $row['status'],
                    'priority' => $row['priority'],
                    'due_date' => $row['due_date'],
                    'created_by' => $demoUser->id,
                    'is_billed' => false,
                ]
            );
        }

        $taskRows = [
            ['title' => 'Client proposal — Acme Corp', 'assignee' => $neha->id, 'days' => 3, 'client' => true],
            ['title' => 'Review contract — Brightline Ltd', 'assignee' => $neha->id, 'days' => 5, 'client' => false],
            ['title' => 'Follow-up call — Nova Systems', 'assignee' => $neha->id, 'days' => 7, 'client' => false],
            ['title' => 'Prepare board deck', 'assignee' => $neha->id, 'days' => 4, 'client' => false],
            ['title' => 'Data cleanup — internal', 'assignee' => $neha->id, 'days' => 2, 'client' => false],
            ['title' => 'Onboarding checklist', 'assignee' => $amit->id, 'days' => 6, 'client' => false],
            ['title' => 'Vendor reconciliation', 'assignee' => $amit->id, 'days' => 8, 'client' => false],
            ['title' => 'Team stand-up notes', 'assignee' => $amit->id, 'days' => 1, 'client' => false],
            ['title' => 'Quarterly compliance review', 'assignee' => $neha->id, 'days' => 10, 'client' => true],
            ['title' => 'Board meeting prep', 'assignee' => $amit->id, 'days' => 12, 'client' => false],
            ['title' => 'Client onboarding — Acme Corp', 'assignee' => $neha->id, 'days' => 0, 'client' => true],
            ['title' => 'Payment follow-up — Acme Corp', 'assignee' => $neha->id, 'days' => 14, 'client' => true],
        ];

        Task::withoutGlobalScopes()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'title' => 'Review client proposal — Acme Corp',
            ],
            [
                'client_id' => $client->id,
                'assigned_to' => $demoUser->id,
                'status' => Task::STATUS_PENDING,
                'priority' => 'High',
                'due_date' => now(),
                'created_by' => $demoUser->id,
                'is_billed' => false,
            ]
        );

        foreach ($taskRows as $row) {
            Task::withoutGlobalScopes()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'title' => $row['title'],
                ],
                [
                    'client_id' => ($row['client'] ?? false) ? $client->id : null,
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

        Invoice::withoutGlobalScopes()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'invoice_number' => 'DEMO-INV-001',
            ],
            [
                'client_id' => $client->id,
                'date' => now()->subDays(10),
                'due_date' => now()->addDays(5),
                'status' => Invoice::STATUS_OVERDUE,
                'subtotal' => 45000,
                'tax' => 8100,
                'cgst' => 4050,
                'sgst' => 4050,
                'igst' => 0,
                'total_amount' => 53100,
                'notes' => 'Demo invoice — quarterly advisory services',
            ]
        );

        OrganizationContext::clear();
    }
}
