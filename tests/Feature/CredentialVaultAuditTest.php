<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CredentialVaultAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_credential_create_and_delete_write_vault_activity_without_password(): void
    {
        [$branchA] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $client = $this->clientForBranch($branchA, 'AUDIT-A');

        $this->actingAs($manager)
            ->post(route('credentials.store'), [
                'client_id' => $client->id,
                'portal_name' => 'Income Tax Portal',
                'username' => 'audit-user',
                'password' => 'super-secret',
                'notes' => 'Quarterly filing',
            ])
            ->assertRedirect();

        $credential = ClientCredential::where('portal_name', 'Income Tax Portal')->first();
        $this->assertNotNull($credential);

        $created = Activity::where('subject_type', ClientCredential::class)
            ->where('subject_id', $credential->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($created);
        $this->assertSame('credential_vault', $created->log_name);
        $this->assertStringNotContainsString('super-secret', json_encode($created->properties));
        $this->assertStringContainsString('added credential vault entry', $created->description);

        $this->actingAs($manager)
            ->delete(route('credentials.destroy', $credential))
            ->assertRedirect();

        $deleted = Activity::where('subject_type', ClientCredential::class)
            ->where('subject_id', $credential->id)
            ->where('event', 'deleted')
            ->first();

        $this->assertNotNull($deleted);
        $this->assertStringContainsString('deleted credential vault entry', $deleted->description);
    }

    public function test_reveal_and_copy_actions_are_audited_for_authorized_manager(): void
    {
        [$branchA] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $credential = $this->credentialForBranch($branchA, 'GST Portal Audit');

        $this->actingAs($manager)
            ->postJson(route('credentials.audit', $credential), [
                'action' => ClientCredential::AUDIT_REVEALED_PASSWORD,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->actingAs($manager)
            ->postJson(route('credentials.audit', $credential), [
                'action' => ClientCredential::AUDIT_COPIED_USERNAME,
            ])
            ->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => ClientCredential::class,
            'subject_id' => $credential->id,
            'event' => ClientCredential::AUDIT_REVEALED_PASSWORD,
            'causer_id' => $manager->id,
        ]);

        $reveal = Activity::where('event', ClientCredential::AUDIT_REVEALED_PASSWORD)->first();
        $this->assertSame('password', $reveal->properties['field']);
        $this->assertSame('GST Portal Audit', $reveal->properties['portal_name']);
        $this->assertStringNotContainsString('vault-secret', json_encode($reveal->properties));

        $credential->refresh();
        $this->assertNotNull($credential->last_accessed_at);
        $this->assertSame($manager->id, $credential->last_accessed_by);
    }

    public function test_credential_store_accepts_category(): void
    {
        [$branchA] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $client = $this->clientForBranch($branchA, 'CAT-A');

        $this->actingAs($manager)
            ->post(route('credentials.store'), [
                'client_id' => $client->id,
                'portal_name' => 'GST Portal',
                'category' => ClientCredential::CATEGORY_GST,
                'username' => 'gst-user',
                'password' => 'secret',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('client_credentials', [
            'client_id' => $client->id,
            'portal_name' => 'GST Portal',
            'category' => ClientCredential::CATEGORY_GST,
        ]);
    }

    public function test_cross_branch_credential_audit_is_denied(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $otherCredential = $this->credentialForBranch($branchB, 'Other Branch Portal');

        $this->actingAs($manager)
            ->postJson(route('credentials.audit', $otherCredential), [
                'action' => ClientCredential::AUDIT_COPIED_PASSWORD,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('activity_log', [
            'subject_id' => $otherCredential->id,
            'event' => ClientCredential::AUDIT_COPIED_PASSWORD,
        ]);
    }

    public function test_invalid_audit_action_is_rejected(): void
    {
        [$branchA] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $credential = $this->credentialForBranch($branchA, 'Invalid Audit Portal');

        $this->actingAs($manager)
            ->postJson(route('credentials.audit', $credential), [
                'action' => 'leaked_password',
            ])
            ->assertUnprocessable();
    }

    private function branches(): array
    {
        $branchA = Branch::create(['name' => 'Branch A', 'code' => 'A']);
        $branchB = Branch::create(['name' => 'Branch B', 'code' => 'B']);

        return [$branchA, $branchB];
    }

    private function clientForBranch(Branch $branch, string $code): Client
    {
        return Client::create([
            'client_code' => $code,
            'name' => "Client {$code}",
            'status' => Client::STATUS_ACTIVE,
            'branch_id' => $branch->id,
        ]);
    }

    private function credentialForBranch(Branch $branch, string $portalName): ClientCredential
    {
        return ClientCredential::create([
            'client_id' => $this->clientForBranch($branch, strtoupper(substr($portalName, 0, 6)))->id,
            'portal_name' => $portalName,
            'username' => 'vault-user',
            'password' => 'vault-secret',
        ]);
    }
}
