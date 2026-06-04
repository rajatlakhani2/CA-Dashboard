<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class NewFeaturesBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_module_middleware_blocks_staff_without_access(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['billing' => false, 'dashboard' => true],
        ]);

        $this->actingAs($staff)
            ->get(route('billing.index'))
            ->assertForbidden();
    }

    public function test_partner_dashboard_is_partner_only(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $partner = User::factory()->create(['role' => 'partner']);

        $this->actingAs($manager)
            ->get(route('partner.dashboard'))
            ->assertForbidden();

        $this->actingAs($partner)
            ->get(route('partner.dashboard'))
            ->assertRedirect(route('dashboard', ['tab' => 'firm']));

        $this->actingAs($partner)
            ->get(route('dashboard', ['tab' => 'firm']))
            ->assertOk()
            ->assertSee('MTD Invoiced', false);
    }

    public function test_create_draft_invoice_from_billing_queue(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create();
        $service = Service::create([
            'name' => 'GST Return',
            'code' => 'GSTR',
            'frequency' => 'Monthly',
        ]);
        $clientService = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);
        ServiceDue::create([
            'client_service_id' => $clientService->id,
            'due_date' => now(),
            'status' => ServiceDue::STATUS_COMPLETED,
            'billing_status' => ServiceDue::BILLING_STATUS_UNBILLED,
            'billing_amount' => 5000,
        ]);

        $this->actingAs($manager)
            ->post(route('billing.create-draft'), ['client_id' => $client->id])
            ->assertRedirect();

        $invoice = Invoice::where('client_id', $client->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame(Invoice::STATUS_DRAFT, $invoice->status);
    }

    public function test_spawn_tasks_from_service_templates(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create();
        $service = Service::create([
            'name' => 'Income Tax Return',
            'code' => 'ITR',
            'frequency' => 'Annually',
        ]);
        ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);
        TaskTemplate::create([
            'service_id' => $service->id,
            'title' => 'Collect Form 16',
            'due_days_offset' => 2,
            'priority' => 'High',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($partner)
            ->post(route('services.spawn-tasks', [$service, $client]))
            ->assertRedirect(route('tasks.index', ['client_id' => $client->id]));

        $this->assertDatabaseHas('tasks', [
            'client_id' => $client->id,
            'title' => 'Collect Form 16',
        ]);
    }

    public function test_daily_task_digest_command_sends_whatsapp_for_overdue_tasks(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'mobile' => '919876543210',
            'module_access' => ['tasks' => true],
        ]);
        $client = Client::factory()->create();
        Task::create([
            'title' => 'Overdue filing',
            'client_id' => $client->id,
            'assigned_to' => $user->id,
            'status' => Task::STATUS_PENDING,
            'priority' => 'High',
            'due_date' => now()->subDay(),
            'created_by' => $user->id,
        ]);

        $mock = Mockery::mock(WhatsAppService::class);
        $mock->shouldReceive('sendMessage')->once()->andReturn(['success' => true]);
        $this->app->instance(WhatsAppService::class, $mock);

        $this->assertSame(0, Artisan::call('tasks:send-daily-digest'));
    }

    public function test_report_export_includes_non_draft_branch_invoices(): void
    {
        [$branchA, $branchB] = [
            Branch::create(['name' => 'Branch A', 'code' => 'NBA']),
            Branch::create(['name' => 'Branch B', 'code' => 'NBB']),
        ];
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $ownClient = Client::factory()->create(['branch_id' => $branchA->id, 'client_code' => 'OWN-1', 'pan' => 'AAAAA1111A']);
        $otherClient = Client::factory()->create(['branch_id' => $branchB->id, 'client_code' => 'OTH-1', 'pan' => 'BBBBB2222B']);

        Invoice::create([
            'client_id' => $ownClient->id,
            'branch_id' => $branchA->id,
            'invoice_number' => 'EXPORT-OWN',
            'date' => now(),
            'due_date' => now()->addDays(7),
            'status' => Invoice::STATUS_OVERDUE,
            'subtotal' => 1000,
            'tax' => 0,
            'total_amount' => 1000,
        ]);
        Invoice::create([
            'client_id' => $otherClient->id,
            'branch_id' => $branchB->id,
            'invoice_number' => 'EXPORT-HIDDEN',
            'date' => now(),
            'due_date' => now()->addDays(7),
            'status' => Invoice::STATUS_OVERDUE,
            'subtotal' => 1000,
            'tax' => 0,
            'total_amount' => 1000,
        ]);

        $csv = $this->actingAs($manager)
            ->get(route('reports.financial.export'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('EXPORT-OWN', $csv);
        $this->assertStringNotContainsString('EXPORT-HIDDEN', $csv);
    }
}
