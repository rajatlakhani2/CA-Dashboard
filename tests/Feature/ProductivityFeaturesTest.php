<?php

namespace Tests\Feature;

use App\Models\BillingRule;
use App\Models\Service;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductivityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_day_page_loads_for_staff_with_tasks_module(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'module_access' => ['tasks' => true, 'dashboard' => true]]);

        $this->actingAs($user)
            ->get(route('tasks.my-day'))
            ->assertOk();
    }

    public function test_billing_rules_page_requires_firm_manager(): void
    {
        $associate = User::factory()->create(['role' => 'associate']);

        $this->actingAs($associate)
            ->get(route('billing-rules.index'))
            ->assertForbidden();
    }

    public function test_partner_can_create_task_template(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $service = Service::create([
            'name' => 'Income Tax Return',
            'code' => 'ITR',
            'frequency' => 'Annually',
        ]);

        $this->actingAs($partner)
            ->post(route('services.task-templates.store', $service), [
                'title' => 'Collect documents',
                'due_days_offset' => 3,
                'priority' => 'High',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_templates', [
            'service_id' => $service->id,
            'title' => 'Collect documents',
        ]);
    }

    public function test_global_search_respects_module_access(): void
    {
        $user = User::factory()->create([
            'role' => 'article',
            'module_access' => \App\Support\ModuleAccess::defaultsForRole('article'),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('search.global', ['query' => 'Billing']));

        $response->assertOk();
        $titles = collect($response->json())->pluck('title');
        $this->assertFalse($titles->contains('Billing Queue'));
    }

    public function test_production_blocks_dangerous_system_middleware(): void
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
