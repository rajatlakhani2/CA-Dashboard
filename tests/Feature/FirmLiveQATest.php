<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HTTP verification for firm QA roles (partner / associate / article).
 * Aligns with docs/DASHBOARD_FEATURE_TEST.md register.
 */
class FirmLiveQATest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_access_admin_finance_and_reporting_modules(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::create([
            'client_code' => 'P-LIVE-1',
            'name' => 'Partner Live Client',
            'pan' => 'PARTLIVE01A',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);

        $routes = [
            'dashboard',
            'clients.index',
            'clients.create',
            'invoices.index',
            'invoices.create',
            'billing.index',
            'payments.index',
            'expenses.index',
            'reports.index',
            'staff.index',
            'system.index',
            'branches.index',
            'users.index',
        ];

        foreach ($routes as $route) {
            $this->actingAs($partner)->get(route($route))->assertOk();
        }

        $this->actingAs($partner)->get(route('clients.show', $client))->assertOk();
    }

    public function test_associate_live_qa_matrix_matches_register(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $associate = User::factory()->create(['role' => 'associate', 'name' => 'Firm Associate']);

        $ownClient = Client::create([
            'client_code' => 'A-LIVE-1',
            'name' => 'Associate Own Client',
            'pan' => 'ASSOLIVE01A',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'manager_id' => $associate->id,
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);
        $otherClient = Client::create([
            'client_code' => 'A-LIVE-2',
            'name' => 'Partner Only Client',
            'pan' => 'ASSOLIVE02B',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'manager_id' => $partner->id,
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);

        $this->actingAs($associate)->get(route('dashboard'))->assertOk();
        $this->actingAs($associate)->get(route('clients.index'))->assertOk()->assertSee($ownClient->name);
        $this->actingAs($associate)->get(route('clients.show', $otherClient))->assertForbidden();
        $this->actingAs($associate)->get(route('clients.create'))->assertOk();

        $this->actingAs($associate)->get(route('invoices.index'))->assertOk();
        $this->actingAs($associate)->get(route('invoices.create'))->assertForbidden();

        $forbidden = [
            'billing.index',
            'payments.index',
            'expenses.index',
            'reports.index',
            'staff.index',
            'credentials.index',
            'tds.index',
            'dscs.index',
            'compliance.index',
        ];

        foreach ($forbidden as $route) {
            $this->actingAs($associate)->get(route($route))->assertForbidden();
        }
    }

    public function test_associate_sees_read_only_invoice_on_own_client_show(): void
    {
        $associate = User::factory()->create(['role' => 'associate']);
        $client = Client::create([
            'client_code' => 'A-LIVE-3',
            'name' => 'Invoice Summary Client',
            'pan' => 'ASSOLIVE03C',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'manager_id' => $associate->id,
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);
        Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-ASSO-LIVE',
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => Invoice::STATUS_DRAFT,
            'total_amount' => 1500,
        ]);

        $this->actingAs($associate)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('INV-ASSO-LIVE');
    }

    public function test_article_live_qa_matrix_matches_register(): void
    {
        $article = User::factory()->create(['role' => 'article']);

        $this->actingAs($article)->get(route('tasks.index'))->assertOk();
        $this->actingAs($article)->get(route('clients.create'))->assertOk();
        $this->actingAs($article)->get(route('clients.index'))->assertRedirect(route('tasks.index'));
        $this->actingAs($article)->get(route('dashboard'))->assertRedirect(route('tasks.index'));
        $this->actingAs($article)->get(route('invoices.index'))->assertRedirect(route('tasks.index'));
        $this->actingAs($article)->get(route('billing.index'))->assertRedirect(route('tasks.index'));
    }
}
