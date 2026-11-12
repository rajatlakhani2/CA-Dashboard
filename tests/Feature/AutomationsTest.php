<?php

namespace Tests\Feature;

use App\Models\AutomationLog;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Service;
use App\Models\ServiceDocumentRequirement;
use App\Models\ServiceDue;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Support\AutomationSettings;
use App\Support\ModuleAccess;
use App\Support\OrganizationContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AutomationsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $partner;
    private User $associate;
    private Client $client;
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 5, 0, AutomationSettings::TIMEZONE)); // a Monday, 9:05 IST

        $this->org = Organization::create(['name' => 'Auto Firm', 'slug' => 'autofirm', 'plan' => 'professional', 'seat_limit' => 10, 'is_active' => true]);
        $this->partner = $this->member('partner', 'Pat Partner', '919800000001');
        $this->associate = $this->member('associate', 'Asha Associate', '919800000002');
        Setting::set('company_name', 'Auto & Co');

        $this->client = Client::create([
            'client_code' => 'A-1', 'name' => 'Mehta Textiles', 'pan' => 'MEHTA1234A', 'status' => Client::STATUS_ACTIVE,
            'category' => 'A', 'manager_id' => $this->associate->id, 'approval_status' => Client::APPROVAL_APPROVED,
            'organization_id' => $this->org->id, 'primary_contact_phone' => '9876543210', 'primary_contact_email' => 'accounts@mehta.test',
        ]);

        $gst = Service::create(['name' => 'GSTR-3B', 'code' => 'GSTR3B', 'frequency' => 'Monthly', 'due_day' => 20]);
        ServiceDocumentRequirement::create(['service_id' => $gst->id, 'name' => 'Purchase register', 'sort_order' => 1]);
        $cs = ClientService::create(['client_id' => $this->client->id, 'service_id' => $gst->id]);
        ServiceDue::create(['client_service_id' => $cs->id, 'due_date' => now()->subDays(2), 'status' => ServiceDue::STATUS_PENDING]); // overdue
        ServiceDue::create(['client_service_id' => $cs->id, 'due_date' => now()->addDays(5), 'status' => ServiceDue::STATUS_PENDING]);   // docs due in 5 days

        Invoice::create([
            'client_id' => $this->client->id, 'invoice_number' => 'INV-77', 'date' => now()->subDays(40), 'due_date' => now()->subDays(16),
            'status' => Invoice::STATUS_OVERDUE, 'subtotal' => 48000, 'tax' => 0, 'total_amount' => 48000,
        ]);

        Task::create(['client_id' => $this->client->id, 'assigned_to' => $this->associate->id, 'created_by' => $this->partner->id,
            'title' => 'Reconcile GST', 'status' => Task::STATUS_PENDING, 'priority' => 'High', 'due_date' => now()]);

        // Capture WhatsApp instead of calling Meta.
        $this->app->instance(WhatsAppService::class, new class($this->sent) extends WhatsAppService {
            public function __construct(private array &$box) {}
            public function sendMessage($mobile, $message) { $this->box[] = [$mobile, $message]; return ['success' => true, 'message' => 'ok']; }
        });
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function member(string $role, string $name, string $mobile): User
    {
        return User::factory()->create([
            'organization_id' => $this->org->id, 'role' => $role, 'name' => $name, 'mobile' => $mobile,
            'module_access' => ModuleAccess::defaultsForRole($role),
        ]);
    }

    private function enable(array $settings): void
    {
        OrganizationContext::set($this->org->id);
        AutomationSettings::save($settings);
    }

    private function runAutomation(string $type, bool $force = true): void
    {
        $this->artisan('automation:run', array_filter(['type' => $type, '--force' => $force]))->assertSuccessful();
    }

    public function test_nothing_is_sent_while_automations_are_off(): void
    {
        $this->artisan('automation:run', ['--force' => true])->assertSuccessful();

        $this->assertSame([], $this->sent);
        $this->assertSame(0, AutomationLog::withoutGlobalScopes()->count());
    }

    public function test_morning_brief_lists_overdue_and_today_and_sends_once(): void
    {
        $this->enable(['auto_morning_brief' => '1']);

        $this->runAutomation('morning_brief');
        $this->runAutomation('morning_brief');

        $toAsha = array_values(array_filter($this->sent, fn ($s) => $s[0] === '919800000002'));
        $this->assertCount(1, $toAsha, 'sent exactly once');
        $this->assertStringContainsString('Good morning, Asha', $toAsha[0][1]);
        $this->assertStringContainsString('Overdue', $toAsha[0][1]);
        $this->assertStringContainsString('GSTR-3B', $toAsha[0][1]);
        $this->assertStringContainsString('Reconcile GST', $toAsha[0][1]);
    }

    public function test_morning_brief_respects_the_configured_hour(): void
    {
        $this->enable(['auto_morning_brief' => '1', 'auto_morning_brief_hour' => '7']);

        $this->runAutomation('morning_brief', false);   // it is 9 AM, brief set for 7 AM
        $this->assertSame([], $this->sent);
    }

    public function test_payment_reminder_goes_by_whatsapp_and_email_once_per_stage(): void
    {
        $this->enable(['auto_payment_reminders' => '1', 'auto_payment_reminder_days' => '7,15,30']);

        $this->runAutomation('payment_reminders');
        $this->runAutomation('payment_reminders');

        $wa = array_values(array_filter($this->sent, fn ($s) => $s[0] === '919876543210'));
        $this->assertCount(1, $wa);
        $this->assertStringContainsString('INV-77', $wa[0][1]);
        $this->assertStringContainsString('16 days past the due date', $wa[0][1]);
        $this->assertStringContainsString('Auto & Co', $wa[0][1]);
        $this->assertTrue(AutomationLog::withoutGlobalScopes()->where('dedupe_key', 'like', 'invoice:%:stage:15')->where('channel', 'email')->exists());
    }

    public function test_client_can_be_opted_out_of_automatic_messages(): void
    {
        $this->enable(['auto_payment_reminders' => '1', 'auto_document_requests' => '1']);

        $this->actingAs($this->partner)
            ->patch(route('clients.auto-reminders', $this->client), ['auto_reminders' => 0])
            ->assertRedirect();
        $this->assertFalse($this->client->fresh()->auto_reminders);

        $this->runAutomation('payment_reminders');
        $this->runAutomation('document_requests');
        $this->assertSame([], $this->sent);
    }

    public function test_document_request_lists_missing_documents(): void
    {
        $this->enable(['auto_document_requests' => '1', 'auto_document_request_days' => '5']);

        $this->runAutomation('document_requests');

        $wa = array_values(array_filter($this->sent, fn ($s) => $s[0] === '919876543210'));
        $this->assertCount(1, $wa);
        $this->assertStringContainsString('Purchase register', $wa[0][1]);
        $this->assertStringContainsString('GSTR-3B due on 21 Nov 2026', $wa[0][1]);
    }

    public function test_weekly_summary_emails_partners_on_monday(): void
    {
        $this->enable(['auto_weekly_summary' => '1', 'auto_weekly_summary_hour' => '9']);

        $this->runAutomation('weekly_summary', false); // Monday 9 AM matches
        $log = AutomationLog::withoutGlobalScopes()->where('type', 'weekly_summary')->first();

        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
        $this->assertSame($this->partner->email, $log->recipient);
    }

    public function test_failed_whatsapp_is_logged_with_the_reason(): void
    {
        $this->app->instance(WhatsAppService::class, new class extends WhatsAppService {
            public function __construct() {}
            public function sendMessage($mobile, $message) { return ['success' => false, 'message' => 'WhatsApp API not configured.']; }
        });
        $this->enable(['auto_morning_brief' => '1']);

        $this->runAutomation('morning_brief');

        $log = AutomationLog::withoutGlobalScopes()->where('type', 'morning_brief')->first();
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('not configured', $log->detail);
    }

    public function test_partner_can_open_and_save_the_automations_page(): void
    {
        $this->actingAs($this->partner)->get(route('settings.automations'))
            ->assertOk()
            ->assertSee('Morning WhatsApp to each staff member')
            ->assertSee('Good morning, Pat')
            ->assertSee('INV-77');

        $this->actingAs($this->partner)->put(route('settings.automations.update'), [
            'auto_morning_brief' => '1', 'auto_morning_brief_hour' => 8,
            'auto_payment_reminder_days' => '10, 20', 'auto_document_request_days' => 3,
            'auto_client_hour' => 11, 'auto_weekly_summary_hour' => 8,
        ])->assertRedirect(route('settings.automations'));

        OrganizationContext::set($this->org->id);
        $this->assertTrue(AutomationSettings::enabled('auto_morning_brief'));
        $this->assertFalse(AutomationSettings::enabled('auto_payment_reminders'));
        $this->assertSame([10, 20], AutomationSettings::reminderDays());

        $this->actingAs($this->partner)->put(route('settings.automations.update'), [
            'auto_morning_brief_hour' => 8, 'auto_payment_reminder_days' => 'weekly',
            'auto_document_request_days' => 3, 'auto_client_hour' => 11, 'auto_weekly_summary_hour' => 8,
        ])->assertSessionHasErrors('auto_payment_reminder_days');
    }

    public function test_associate_cannot_change_automations(): void
    {
        $this->actingAs($this->associate)->get(route('settings.automations'))->assertForbidden();
    }
}
