<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\PersonalRenewal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Priority board, client master fields, LIC/insurance and daily WhatsApp reminders. */
class UiRebuildFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private Organization $firm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firm = Organization::create([
            'name' => 'UI Rebuild Firm',
            'slug' => 'ui-rebuild-'.fake()->unique()->numerify('####'),
            'plan' => 'starter',
            'seat_limit' => 10,
        ]);
    }

    public function test_normal_priority_is_saved_as_medium(): void
    {
        $task = $this->createTask(['priority' => 'Normal']);

        $this->assertSame('Medium', $task->fresh()->priority);
    }

    public function test_priority_board_groups_tasks_by_priority_and_deadline(): void
    {
        $partner = $this->firmUser(['role' => 'partner']);
        $this->createTask(['title' => 'Late GST filing', 'priority' => 'High', 'due_date' => now()->subDays(2), 'assigned_to' => $partner->id]);
        $this->createTask(['title' => 'Audit file later', 'priority' => 'Low', 'due_date' => now()->addDays(20), 'assigned_to' => $partner->id]);
        $this->createTask(['title' => 'Finished thing', 'priority' => 'High', 'status' => 'Completed', 'assigned_to' => $partner->id]);

        $response = $this->actingAs($partner)->get(route('tasks.priority'));

        $response->assertOk();
        $response->assertSee('Priority Board');
        $response->assertSee('Late GST filing');
        $response->assertSee('Audit file later');
        $response->assertSee('Overdue');
        $response->assertDontSee('Finished thing');
    }

    public function test_client_page_has_all_tasks_tab_and_master_fields(): void
    {
        $partner = $this->firmUser(['role' => 'partner']);
        $task = $this->createTask(['title' => 'Client 360 task']);
        $client = $task->client;
        $client->update(['city' => 'Ahmedabad', 'whatsapp_opt_in' => true]);

        $this->actingAs($partner)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('All Tasks')
            ->assertSee('Client 360 task')
            ->assertSee('Ahmedabad');
    }

    public function test_insurance_policy_can_be_added_with_policy_details(): void
    {
        $partner = $this->firmUser(['role' => 'partner']);

        $this->actingAs($partner)->post(route('personal-renewals.store'), [
            'category' => 'Vehicle',
            'title' => 'Car insurance',
            'insurer' => 'HDFC Ergo',
            'policy_number' => 'POL123',
            'amount' => 12000,
            'due_date' => now()->addDays(10)->toDateString(),
            'frequency' => 'Yearly',
            'remind_days_before' => 15,
            'whatsapp_reminder' => 1,
        ])->assertRedirect(route('personal-renewals.index'));

        $this->assertDatabaseHas('personal_renewals', [
            'title' => 'Car insurance',
            'category' => 'Vehicle',
            'policy_number' => 'POL123',
            'remind_days_before' => 15,
            'user_id' => $partner->id,
        ]);
    }

    public function test_someone_else_cannot_edit_my_policy(): void
    {
        $owner = $this->firmUser(['role' => 'partner']);
        $other = $this->firmUser(['role' => 'partner']);
        $policy = PersonalRenewal::create([
            'organization_id' => $this->firm->id,
            'user_id' => $owner->id,
            'title' => 'LIC Jeevan',
            'category' => 'LIC',
            'amount' => 5000,
            'due_date' => now()->addDays(5),
            'status' => 'Pending',
        ]);

        $this->actingAs($other)->get(route('personal-renewals.edit', $policy))->assertForbidden();
    }

    public function test_daily_whatsapp_dry_run_builds_messages(): void
    {
        $partner = $this->firmUser(['role' => 'partner', 'mobile' => '9876543210', 'name' => 'Rajat']);
        $this->createTask(['title' => 'File ITR', 'priority' => 'High', 'due_date' => now()->subDay(), 'assigned_to' => $partner->id]);

        $this->artisan('whatsapp:daily-reminders', ['--dry-run' => true])
            ->expectsOutputToContain('Good morning Rajat')
            ->assertSuccessful();
    }

    private function firmUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['organization_id' => $this->firm->id], $attrs));
    }

    private function createTask(array $overrides = []): Task
    {
        $client = Client::create([
            'organization_id' => $this->firm->id,
            'name' => fake()->company(),
            'client_code' => fake()->unique()->bothify('UIR-###'),
            'pan' => fake()->unique()->regexify('[A-Z]{5}[0-9]{4}[A-Z]'),
            'status' => 'Active',
            'category' => 'A',
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);

        return Task::create(array_merge([
            'organization_id' => $this->firm->id,
            'title' => fake()->sentence(3),
            'client_id' => $client->id,
            'priority' => 'Medium',
            'status' => 'Pending',
            'due_date' => now()->addDays(5),
        ], $overrides));
    }
}
