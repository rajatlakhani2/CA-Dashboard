<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Service;
use App\Models\ServiceDocumentRequirement;
use App\Models\ServiceDue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceDocumentChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_define_and_remove_document_requirements(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $service = Service::create([
            'name' => 'IT Return',
            'code' => 'ITR-DOC',
            'frequency' => 'Annually',
            'due_day' => 31,
            'due_month' => 7,
        ]);

        $this->actingAs($partner)
            ->post(route('services.document-requirements.store', $service), ['name' => 'Form 16'])
            ->assertRedirect(route('services.index'));

        $requirement = ServiceDocumentRequirement::where('service_id', $service->id)->first();
        $this->assertNotNull($requirement);
        $this->assertSame('Form 16', $requirement->name);

        $this->actingAs($partner)
            ->delete(route('document-requirements.destroy', $requirement))
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseMissing('service_document_requirements', ['id' => $requirement->id]);
    }

    public function test_client_document_toggle_updates_checklist_summary(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create();
        $service = Service::create([
            'name' => 'GST Return',
            'code' => 'GST-DOC',
            'frequency' => 'Monthly',
            'due_day' => 20,
        ]);
        $requirement = ServiceDocumentRequirement::create([
            'service_id' => $service->id,
            'name' => 'Sales register',
            'sort_order' => 1,
        ]);
        $clientService = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager)
            ->patch(route('clients.service-documents.toggle', [$client, $clientService, $requirement]), [
                'received' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('client_service_document_checks', [
            'client_service_id' => $clientService->id,
            'service_document_requirement_id' => $requirement->id,
            'is_received' => true,
        ]);

        $summary = app(\App\Services\ServiceDocumentChecklistService::class)
            ->summaryForClientService($clientService->fresh(['service.documentRequirements', 'documentChecks']));

        $this->assertSame(1, $summary['received']);
        $this->assertSame(0, $summary['missing']);
    }

    public function test_service_dues_index_shows_missing_document_badge(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10));
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create(['name' => 'Doc Badge Client']);
        $service = Service::create([
            'name' => 'Audit',
            'code' => 'AUD-DOC',
            'frequency' => 'Annually',
            'due_day' => 30,
            'due_month' => 9,
        ]);
        ServiceDocumentRequirement::create(['service_id' => $service->id, 'name' => 'Trial balance', 'sort_order' => 1]);
        ServiceDocumentRequirement::create(['service_id' => $service->id, 'name' => 'Bank statements', 'sort_order' => 2]);
        $clientService = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);
        ServiceDue::create([
            'client_service_id' => $clientService->id,
            'due_date' => now()->addDays(5),
            'status' => ServiceDue::STATUS_PENDING,
        ]);

        $response = $this->actingAs($manager)->get(route('service-dues.index'));

        $response->assertOk();
        $response->assertSee('2/2 missing', false);
        $response->assertSee('Doc Badge Client');
        $response->assertSee(route('clients.show', $client) . '?tab=work#document-checklists', false);
    }
}
