<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MobileAppTest extends TestCase
{
    use RefreshDatabase;

    private function firm(): Organization
    {
        return Organization::create([
            'name' => 'Phone Firm',
            'slug' => 'phonefirm',
            'plan' => 'professional',
            'seat_limit' => 10,
        ]);
    }

    private function user(Organization $org, string $role, array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'organization_id' => $org->id,
        ], $extra));
    }

    /** Service-account JSON with a throwaway key, so the sender can sign its token request. */
    private function fakeFirebase(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);

        config(['services.fcm.credentials' => json_encode([
            'type' => 'service_account',
            'project_id' => 'ca-dashboard-test',
            'client_email' => 'push@ca-dashboard-test.iam.gserviceaccount.com',
            'private_key' => $pem,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ])]);
        $this->app->forgetInstance(PushNotificationService::class);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'test-token', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/x/messages/1']),
        ]);
    }

    public function test_home_sends_staff_to_my_day_and_partners_to_dashboard(): void
    {
        $org = $this->firm();

        $this->actingAs($this->user($org, 'staff'))
            ->get('/home')
            ->assertRedirect(route('tasks.my-day'));

        $this->actingAs($this->user($org, 'partner'))
            ->get('/home')
            ->assertRedirect(route('dashboard'));
    }

    public function test_home_requires_sign_in(): void
    {
        $this->get('/home')->assertRedirect(route('login'));
    }

    public function test_download_and_privacy_pages_are_public(): void
    {
        $this->get(route('app.download'))->assertOk()->assertSee('Android phones')->assertSee('Add to Home Screen');
        $this->get(route('privacy'))->assertOk()->assertSee('Privacy policy');
    }

    public function test_phone_registers_and_forgets_its_push_token(): void
    {
        $org = $this->firm();
        $staff = $this->user($org, 'staff');

        $this->actingAs($staff)
            ->postJson(route('app.devices.store'), ['token' => 'phone-token-1', 'platform' => 'android'])
            ->assertOk();

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $staff->id,
            'organization_id' => $org->id,
            'token_hash' => UserDevice::hashToken('phone-token-1'),
        ]);

        // Same phone signing in as someone else moves the token to that person.
        $other = $this->user($org, 'staff');
        $this->actingAs($other)->postJson(route('app.devices.store'), ['token' => 'phone-token-1'])->assertOk();
        $this->assertSame(1, UserDevice::count());
        $this->assertSame($other->id, UserDevice::first()->user_id);

        $this->actingAs($other)
            ->postJson(route('app.devices.forget'), ['token' => 'phone-token-1'])
            ->assertOk();
        $this->assertSame(0, UserDevice::count());
    }

    public function test_push_is_silent_until_firebase_is_configured(): void
    {
        config(['services.fcm.credentials' => null]);
        Http::fake();
        $org = $this->firm();
        $staff = $this->user($org, 'staff');
        UserDevice::create(['user_id' => $staff->id, 'token' => 't', 'token_hash' => UserDevice::hashToken('t')]);

        $sent = app(PushNotificationService::class)->sendToUser($staff, 'task_assigned', 'Hi', 'There');

        $this->assertSame(0, $sent);
        Http::assertNothingSent();
    }

    public function test_assigning_a_task_to_someone_else_sends_them_a_push(): void
    {
        $this->fakeFirebase();
        $org = $this->firm();
        $partner = $this->user($org, 'partner');
        $staff = $this->user($org, 'staff');
        UserDevice::create(['user_id' => $staff->id, 'organization_id' => $org->id, 'token' => 'staff-phone', 'token_hash' => UserDevice::hashToken('staff-phone')]);

        $this->actingAs($partner);
        Task::create([
            'title' => 'File GSTR-3B',
            'status' => Task::STATUS_PENDING,
            'priority' => 'High',
            'assigned_to' => $staff->id,
            'created_by' => $partner->id,
            'due_date' => now()->addDay(),
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'fcm.googleapis.com')
            && $request['message']['token'] === 'staff-phone'
            && str_contains($request['message']['notification']['title'], 'File GSTR-3B'));
    }

    public function test_no_push_when_you_assign_a_task_to_yourself_or_switched_it_off(): void
    {
        $this->fakeFirebase();
        $org = $this->firm();
        $staff = $this->user($org, 'staff');
        $partner = $this->user($org, 'partner', ['notification_preferences' => ['push' => []]]);
        UserDevice::create(['user_id' => $staff->id, 'organization_id' => $org->id, 'token' => 'a', 'token_hash' => UserDevice::hashToken('a')]);
        UserDevice::create(['user_id' => $partner->id, 'organization_id' => $org->id, 'token' => 'b', 'token_hash' => UserDevice::hashToken('b')]);

        $this->actingAs($staff);
        Task::create(['title' => 'Self', 'status' => Task::STATUS_PENDING, 'priority' => 'Normal', 'assigned_to' => $staff->id, 'created_by' => $staff->id]);
        Task::create(['title' => 'To partner', 'status' => Task::STATUS_PENDING, 'priority' => 'Normal', 'assigned_to' => $partner->id, 'created_by' => $staff->id]);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'fcm.googleapis.com'));
    }

    public function test_settings_save_phone_notification_choices(): void
    {
        $org = $this->firm();
        $partner = $this->user($org, 'partner', ['mobile' => '919999000000']);

        $this->actingAs($partner)->put(route('settings.update'), [
            'name' => $partner->name,
            'email' => $partner->email,
            'theme' => 'modern',
            'mobile' => '919999000000',
            'push_prefs_present' => '1',
            'push_prefs' => ['task_assigned', 'not_a_real_type'],
        ])->assertSessionHasNoErrors();

        $partner->refresh();
        $this->assertTrue($partner->wantsPush('task_assigned'));
        $this->assertFalse($partner->wantsPush('due_tomorrow'));
        $this->assertSame(['task_assigned'], $partner->notification_preferences['push']);
    }

    public function test_task_list_done_button_works_without_javascript(): void
    {
        $org = $this->firm();
        $staff = $this->user($org, 'staff', ['module_access' => ['tasks' => true]]);
        $task = Task::create(['title' => 'Phone done', 'status' => Task::STATUS_PENDING, 'priority' => 'Normal', 'assigned_to' => $staff->id, 'created_by' => $staff->id]);

        $this->actingAs($staff)
            ->from(route('tasks.index'))
            ->patch(route('tasks.update-status', $task), ['status' => Task::STATUS_COMPLETED, 'return_back' => '1'])
            ->assertRedirect(route('tasks.index'));

        $this->assertSame(Task::STATUS_COMPLETED, $task->fresh()->status);
    }

    public function test_due_tomorrow_command_lists_what_each_phone_would_get(): void
    {
        $org = $this->firm();
        $staff = $this->user($org, 'staff');
        UserDevice::create(['user_id' => $staff->id, 'organization_id' => $org->id, 'token' => 'x', 'token_hash' => UserDevice::hashToken('x')]);
        Task::create(['title' => 'Tomorrow', 'status' => Task::STATUS_PENDING, 'priority' => 'Normal', 'assigned_to' => $staff->id, 'created_by' => $staff->id, 'due_date' => now()->addDay()]);

        $this->artisan('push:due-tomorrow --dry-run')
            ->expectsOutputToContain('Due tomorrow: 1 task of yours.')
            ->assertSuccessful();
    }

    public function test_payment_alert_goes_to_partners_of_the_same_firm_only(): void
    {
        $this->fakeFirebase();
        $org = $this->firm();
        $partner = $this->user($org, 'partner');
        $staff = $this->user($org, 'staff');
        UserDevice::create(['user_id' => $partner->id, 'organization_id' => $org->id, 'token' => 'partner-phone', 'token_hash' => UserDevice::hashToken('partner-phone')]);
        UserDevice::create(['user_id' => $staff->id, 'organization_id' => $org->id, 'token' => 'staff-phone', 'token_hash' => UserDevice::hashToken('staff-phone')]);

        $otherFirm = Organization::create(['name' => 'Other', 'slug' => 'otherfirm', 'plan' => 'professional', 'seat_limit' => 5]);
        $otherPartner = $this->user($otherFirm, 'partner');
        UserDevice::create(['user_id' => $otherPartner->id, 'organization_id' => $otherFirm->id, 'token' => 'other-phone', 'token_hash' => UserDevice::hashToken('other-phone')]);

        $client = Client::factory()->create(['organization_id' => $org->id, 'name' => 'Acme Traders']);
        $invoice = Invoice::create([
            'client_id' => $client->id, 'invoice_number' => 'INV-PUSH-1', 'date' => now(), 'due_date' => now()->addDays(7),
            'status' => Invoice::STATUS_DRAFT, 'subtotal' => 1000, 'tax' => 180, 'total_amount' => 1180,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id, 'receipt_number' => 'REC-PUSH-1', 'amount' => 1180,
            'payment_date' => now(), 'payment_mode' => 'UPI', 'received_by' => $staff->id,
        ]);

        $tokens = collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'fcm.googleapis.com'))
            ->map(fn ($pair) => $pair[0]['message']['token'])
            ->values()->all();

        $this->assertSame(['partner-phone'], $tokens);
    }
}
