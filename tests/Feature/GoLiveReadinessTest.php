<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * End-to-end go-live gate: route catalog, role matrix, APIs, and artisan health.
 */
class GoLiveReadinessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function partnerGetCatalog(): array
    {
        $routes = [
            'dashboard',
            'partner.dashboard',
            'workload.index',
            'collections.index',
            'clients.index',
            'clients.create',
            'clients.export',
            'clients.template',
            'clients.import.folder',
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
            'staff.index',
            'activity.index',
            'settings.index',
            'system.index',
            'branches.index',
            'users.index',
            'credentials.index',
            'smart-documents.index',
            'document-ingestions.index',
            'time-entries.index',
            'leaves.index',
            'recycle-bin.index',
            'whatsapp.index',
        ];

        return array_combine($routes, array_map(fn ($r) => [$r], $routes));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('partnerGetCatalog')]
    public function test_partner_get_route_returns_success(string $routeName): void
    {
        $partner = User::factory()->create(['role' => 'partner']);

        $this->actingAs($partner)
            ->get(route($routeName))
            ->assertSuccessful();
    }

    public function test_partner_param_routes_and_json_endpoints(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $branch = Branch::create(['name' => 'GoLive HQ', 'code' => 'GL1']);
        $client = Client::factory()->create([
            'branch_id' => $branch->id,
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id]);
        $task = Task::create([
            'title' => 'Go-live task',
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
            'invoice_number' => 'GL-INV-1',
            'date' => now(),
            'due_date' => now()->addDays(7),
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 1000,
            'tax' => 0,
            'total_amount' => 1000,
        ]);

        foreach ([
            ['clients.show', $client],
            ['clients.edit', $client],
            ['tasks.edit', $task],
            ['invoices.show', $invoice],
            ['invoices.edit', $invoice],
            ['staff.show', $staff],
            ['onboarding.show', $client],
            ['smart-documents.show', $client],
            ['ledger.show', $client],
        ] as [$name, $model]) {
            $this->actingAs($partner)->get(route($name, $model))->assertSuccessful();
        }

        $this->actingAs($partner)
            ->getJson(route('search.palette'))
            ->assertOk()
            ->assertJsonStructure(['actions', 'navigation']);

        $this->actingAs($partner)
            ->getJson(route('calendar.events'))
            ->assertOk();
    }

    public function test_associate_go_live_access_matrix(): void
    {
        $associate = User::factory()->create(['role' => 'associate']);

        foreach ([
            'dashboard',
            'clients.index',
            'tasks.index',
            'tasks.my-day',
            'invoices.index',
            'smart-documents.index',
            'time-entries.index',
        ] as $allowed) {
            $this->actingAs($associate)->get(route($allowed))->assertOk();
        }

        foreach ([
            'billing.index',
            'payments.index',
            'reports.index',
            'staff.index',
            'credentials.index',
            'partner.dashboard',
            'workload.index',
            'collections.index',
            'system.index',
        ] as $blocked) {
            $this->actingAs($associate)->get(route($blocked))->assertForbidden();
        }
    }

    public function test_article_go_live_access_matrix(): void
    {
        $article = User::factory()->create(['role' => 'article']);

        $this->actingAs($article)->get(route('tasks.my-day'))->assertOk();
        $this->actingAs($article)->get(route('clients.create'))->assertOk();

        $this->actingAs($article)->get(route('clients.index'))->assertRedirect();
        $this->actingAs($article)->get(route('dashboard'))->assertRedirect();
        $this->actingAs($article)->get(route('billing.index'))->assertRedirect();
    }

    public function test_staff_scoped_modules_and_workload_denied(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['tasks' => true, 'dashboard' => true, 'clients' => true],
        ]);

        $this->actingAs($staff)->get(route('tasks.index'))->assertOk();
        $this->actingAs($staff)->get(route('tasks.my-day'))->assertOk();
        $this->actingAs($staff)->get(route('workload.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('billing.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('reports.staff-productivity'))->assertForbidden();
    }

    public function test_critical_artisan_commands_exit_successfully(): void
    {
        $this->assertSame(0, Artisan::call('services:generate-dues'));
        $this->assertSame(0, Artisan::call('anomaly:scan'));
        $this->assertSame(0, Artisan::call('schedule:list'));
    }

    public function test_install_db_route_not_registered(): void
    {
        $this->get('/install-db')->assertNotFound();
    }

    public function test_production_env_blocks_dangerous_system_migrate_via_middleware(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.allow_dangerous_system_actions' => false]);

        $middleware = new \App\Http\Middleware\RestrictDangerousSystemActions;
        $request = \Illuminate\Http\Request::create('/system/migrate', 'POST');

        try {
            $middleware->handle($request, fn () => response('ok'));
            $this->fail('Expected HttpException 403');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
