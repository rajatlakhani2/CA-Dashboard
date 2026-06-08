<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirmRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_rajat_partner_has_full_client_and_finance_access(): void
    {
        $rajat = User::factory()->create(['role' => 'partner', 'name' => 'Rajat Lakhani']);
        $client = Client::create([
            'client_code' => 'R-1',
            'name' => 'Rajat Client',
            'pan' => 'RAJAT1234A',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'manager_id' => $rajat->id,
        ]);

        $this->actingAs($rajat)->get(route('invoices.index'))->assertOk();
        $this->actingAs($rajat)->get(route('staff.index'))->assertOk();
        $this->actingAs($rajat)->get(route('clients.show', $client))->assertOk();
        $this->actingAs($rajat)->get(route('clients.create'))->assertOk();
    }

    public function test_associate_sees_only_own_clients_and_read_only_portfolio_invoices(): void
    {
        $rajat = User::factory()->create(['role' => 'partner', 'name' => 'Rajat Lakhani']);
        $associate = User::factory()->create(['role' => 'associate', 'name' => 'Firm Associate']);

        $ownClient = Client::create([
            'client_code' => 'N-1',
            'name' => 'Associate Client',
            'pan' => 'NILE1234A',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'manager_id' => $associate->id,
            'group_name' => 'portfolio-b',
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);
        $otherClient = Client::create([
            'client_code' => 'R-2',
            'name' => 'Rajat Only Client',
            'pan' => 'RAJAT2345B',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'manager_id' => $rajat->id,
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);

        $ownInvoice = Invoice::create([
            'client_id' => $ownClient->id,
            'invoice_number' => 'INV-ASC-001',
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => Invoice::STATUS_DRAFT,
            'total_amount' => 1000,
        ]);
        $otherInvoice = Invoice::create([
            'client_id' => $otherClient->id,
            'invoice_number' => 'INV-RAJ-001',
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => Invoice::STATUS_DRAFT,
            'total_amount' => 2000,
        ]);

        $index = $this->actingAs($associate)->get(route('clients.index'));
        $index->assertOk();
        $index->assertSee($ownClient->name);
        $index->assertDontSee($otherClient->name);

        $this->actingAs($associate)->get(route('clients.show', $ownClient))->assertOk();
        $this->actingAs($associate)->get(route('clients.show', $otherClient))->assertForbidden();

        $invoiceIndex = $this->actingAs($associate)->get(route('invoices.index'));
        $invoiceIndex->assertOk();
        $invoiceIndex->assertSee('INV-ASC-001');
        $invoiceIndex->assertDontSee('INV-RAJ-001');
        $invoiceIndex->assertDontSee('Create Invoice');

        $this->actingAs($associate)->get(route('invoices.show', $ownInvoice))->assertOk();
        $this->actingAs($associate)->get(route('invoices.show', $otherInvoice))->assertForbidden();
        $this->actingAs($associate)->get(route('invoices.create'))->assertForbidden();

        $this->actingAs($associate)->get(route('staff.index'))->assertForbidden();
        $this->actingAs($associate)->get(route('payments.index'))->assertForbidden();
        $this->actingAs($associate)->get(route('dashboard'))->assertOk();
        $this->actingAs($associate)->get(route('dashboard'))->assertSee(route('invoices.index'), false);
    }

    public function test_article_clerk_can_only_update_assigned_task_status(): void
    {
        $article = User::factory()->create(['role' => 'article', 'name' => 'Articles']);
        $other = User::factory()->create(['role' => 'staff']);

        $ownTask = Task::create([
            'title' => 'Article Task',
            'assigned_to' => $article->id,
            'created_by' => $other->id,
            'status' => Task::STATUS_PENDING,
            'priority' => 'Medium',
        ]);
        $otherTask = Task::create([
            'title' => 'Hidden Task',
            'assigned_to' => $other->id,
            'created_by' => $other->id,
            'status' => Task::STATUS_PENDING,
            'priority' => 'Medium',
        ]);

        $this->actingAs($article)->get(route('dashboard'))->assertRedirect(route('tasks.index'));
        $this->actingAs($article)->get(route('clients.index'))->assertRedirect(route('tasks.index'));

        $index = $this->actingAs($article)->get(route('tasks.index'));
        $index->assertOk();
        $index->assertSee($ownTask->title);
        $index->assertDontSee($otherTask->title);

        $this->actingAs($article)
            ->patchJson(route('tasks.update-status', $ownTask), ['status' => 'Completed'])
            ->assertOk();

        $this->actingAs($article)
            ->get(route('tasks.edit', $ownTask))
            ->assertRedirect(route('tasks.index'));

        $this->actingAs($article)
            ->patchJson(route('tasks.update-status', $otherTask), ['status' => 'Completed'])
            ->assertForbidden();
    }

    public function test_login_redirects_article_to_tasks(): void
    {
        $article = User::factory()->create(['role' => 'article']);

        $article->forceFill(['password' => \Illuminate\Support\Facades\Hash::make('password')])->save();

        $this->post(route('login'), [
            'email' => $article->email,
            'password' => 'password',
        ])->assertRedirect(route('tasks.my-day'));
    }

    public function test_associate_can_create_client_in_own_portfolio(): void
    {
        $associate = User::factory()->create(['role' => 'associate', 'name' => 'Firm Associate']);

        $this->actingAs($associate)->get(route('clients.create'))->assertOk();

        $response = $this->actingAs($associate)->post(route('clients.store'), [
            'name' => 'New Associate Corp',
            'pan' => 'NILENEW01A',
            'category' => 'A',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('clients', [
            'name' => 'New Associate Corp',
            'manager_id' => $associate->id,
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);

        $this->actingAs($associate)->get(route('clients.index'))->assertSee('New Associate Corp');
    }

    public function test_article_submits_client_for_rajat_approval(): void
    {
        $rajat = User::factory()->create(['role' => 'partner', 'name' => 'Rajat Lakhani']);
        $article = User::factory()->create(['role' => 'article', 'name' => 'Articles']);

        $this->actingAs($article)->get(route('clients.index'))->assertRedirect(route('tasks.index'));
        $this->actingAs($article)->get(route('clients.create'))->assertOk();

        $this->actingAs($article)->post(route('clients.store'), [
            'name' => 'Pending Article Client',
            'pan' => 'ARTPEND01A',
            'category' => 'B',
            'status' => Client::STATUS_ACTIVE,
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('clients', [
            'name' => 'Pending Article Client',
            'approval_status' => Client::APPROVAL_PENDING,
            'created_by_user_id' => $article->id,
        ]);

        $this->actingAs($article)->get(route('clients.index'))->assertRedirect(route('tasks.index'));

        $rajatIndex = $this->actingAs($rajat)->get(route('clients.index'));
        $rajatIndex->assertOk();
        $rajatIndex->assertSee('Pending Article Client');

        $client = Client::where('pan', 'ARTPEND01A')->first();
        $this->actingAs($rajat)->post(route('clients.approve', $client))
            ->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'approval_status' => Client::APPROVAL_APPROVED,
            'approved_by_user_id' => $rajat->id,
        ]);

        $associate = User::factory()->create(['role' => 'associate']);
        $this->actingAs($associate)->get(route('clients.index'))->assertSee('Pending Article Client');
    }
}
