<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HTTP catalog: exercises GET handlers across modules (partner + manager + article).
 */
class FullApplicationCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_login_page_and_authentication_flow(): void
    {
        $user = User::factory()->create([
            'role' => 'manager',
            'email' => 'mgr@test.com',
            'password' => 'secret',
        ]);

        $this->get(route('login'))->assertOk();
        $this->post(route('login'), ['email' => 'wrong@test.com', 'password' => 'x'])
            ->assertSessionHasErrors('email');
        $this->post(route('login'), ['email' => $user->email, 'password' => 'secret'])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sign out', false);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_article_login_redirects_to_tasks(): void
    {
        $article = User::factory()->create([
            'role' => 'article',
            'email' => 'art@test.com',
            'password' => 'pass',
        ]);

        $this->post(route('login'), ['email' => $article->email, 'password' => 'pass'])
            ->assertRedirect(route('tasks.my-day'));
    }

    public function test_partner_can_load_full_get_route_catalog(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $branch = Branch::create(['name' => 'HQ', 'code' => 'HQ1']);
        $client = Client::factory()->create([
            'branch_id' => $branch->id,
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id]);
        $task = Task::create([
            'title' => 'Catalog task',
            'client_id' => $client->id,
            'assigned_to' => $staff->id,
            'created_by' => $partner->id,
            'status' => Task::STATUS_PENDING,
            'priority' => 'Medium',
            'due_date' => now()->addDay(),
        ]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'invoice_number' => 'CAT-001',
            'date' => now(),
            'due_date' => now()->addDays(7),
            'status' => Invoice::STATUS_OVERDUE,
            'subtotal' => 1000,
            'tax' => 0,
            'total_amount' => 1000,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Fee',
            'quantity' => 1,
            'rate' => 1000,
            'amount' => 1000,
        ]);

        $routes = [
            'dashboard',
            'partner.dashboard',
            'clients.index',
            'clients.create',
            'clients.export',
            'clients.template',
            'clients.import.nilesh',
            'tasks.index',
            'tasks.my-day',
            'tasks.create',
            'service-dues.index',
            'personal-renewals.index',
            'services.index',
            'compliance.index',
            'dscs.index',
            'tds.index',
            'billing.index',
            'billing-rules.index',
            'invoices.index',
            'invoices.create',
            'payments.index',
            'payments.create',
            'expenses.index',
            'expenses.create',
            'subscriptions.index',
            'subscriptions.create',
            'reports.index',
            'reports.financial',
            'reports.compliance',
            'reports.service',
            'reports.client',
            'reports.task',
            'reports.due-date',
            'reports.staff-productivity',
            'reports.client-profitability',
            'workload.index',
            'collections.index',
            'document-ingestions.index',
            'staff.index',
            'activity.index',
            'settings.index',
            'system.index',
            'branches.index',
            'users.index',
            'credentials.index',
            'smart-documents.index',
            'time-entries.index',
            'leaves.index',
            'recycle-bin.index',
            'whatsapp.index',
        ];

        foreach ($routes as $name) {
            $this->actingAs($partner)->get(route($name))->assertSuccessful();
        }

        $paramRoutes = [
            ['clients.show', $client],
            ['clients.edit', $client],
            ['tasks.edit', $task],
            ['invoices.show', $invoice],
            ['invoices.edit', $invoice],
            ['staff.show', $staff],
            ['onboarding.show', $client],
            ['smart-documents.show', $client],
            ['ledger.show', $client],
        ];

        foreach ($paramRoutes as [$name, $model]) {
            $this->actingAs($partner)->get(route($name, $model))->assertSuccessful();
        }
    }

    public function test_manager_can_load_branch_scoped_catalog(): void
    {
        $branch = Branch::create(['name' => 'M Branch', 'code' => 'MB1']);
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branch->id]);
        $client = Client::factory()->create(['branch_id' => $branch->id]);

        foreach ([
            'dashboard',
            'clients.index',
            'tasks.index',
            'billing.index',
            'invoices.index',
            'reports.index',
            'staff.index',
        ] as $route) {
            $this->actingAs($manager)->get(route($route))->assertSuccessful();
        }

        $this->actingAs($manager)->get(route('clients.show', $client))->assertSuccessful();
        $this->actingAs($manager)->get(route('partner.dashboard'))->assertForbidden();
        $this->actingAs($manager)->get(route('system.index'))->assertForbidden();
    }

    public function test_global_search_and_my_day_endpoints(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Client::factory()->create(['name' => 'Searchable Corp', 'pan' => 'SEARCH1234A']);

        $this->actingAs($manager)
            ->getJson(route('search.global', ['query' => 'Search']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Searchable Corp']);

        $this->actingAs($manager)
            ->get(route('tasks.my-day'))
            ->assertOk();
    }

    public function test_billing_rules_and_apply_rules_post_handlers(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)
            ->post(route('billing-rules.store'), [
                'name' => 'Standard GST',
                'rule_type' => 'fixed_fee',
                'fixed_amount' => 2000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('billing_rules', ['name' => 'Standard GST']);

        $this->actingAs($manager)
            ->post(route('billing.apply-rules'))
            ->assertRedirect();
    }
}
