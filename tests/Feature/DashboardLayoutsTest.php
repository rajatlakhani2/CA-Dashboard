<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Dsc;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceDocumentRequirement;
use App\Models\ServiceDue;
use App\Models\Task;
use App\Models\User;
use App\Support\DashboardLayout;
use App\Support\ModuleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLayoutsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $partner;
    private User $associate;
    private Client $mine;
    private Client $other;
    private ServiceDue $overdueDue;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create(['name' => 'Layouts Firm', 'slug' => 'layouts', 'plan' => 'professional', 'seat_limit' => 10]);
        \App\Support\OrganizationContext::set($this->org->id);
        $this->partner = $this->member('partner', 'Pat Partner');
        $this->associate = $this->member('associate', 'Asha Associate');

        $this->mine = $this->client('Asha Client Pvt Ltd', $this->associate);
        $this->other = $this->client('Partner Only Traders', $this->partner);

        $gst = Service::create(['name' => 'GSTR-3B', 'code' => 'GSTR3B', 'frequency' => 'Monthly', 'due_day' => 20]);
        ServiceDocumentRequirement::create(['service_id' => $gst->id, 'name' => 'Sales register', 'sort_order' => 1]);

        $csMine = ClientService::create(['client_id' => $this->mine->id, 'service_id' => $gst->id]);
        $csOther = ClientService::create(['client_id' => $this->other->id, 'service_id' => $gst->id]);

        $this->overdueDue = ServiceDue::create(['client_service_id' => $csOther->id, 'due_date' => now()->subDays(3), 'status' => ServiceDue::STATUS_PENDING]);
        ServiceDue::create(['client_service_id' => $csMine->id, 'due_date' => now()->addDays(2), 'status' => ServiceDue::STATUS_PENDING]);

        $this->invoice = Invoice::create([
            'client_id' => $this->other->id,
            'invoice_number' => 'INV-T-1',
            'date' => now()->subDays(70),
            'due_date' => now()->subDays(55),
            'status' => Invoice::STATUS_OVERDUE,
            'subtotal' => 50000,
            'tax' => 0,
            'total_amount' => 50000,
        ]);
        Payment::create(['invoice_id' => $this->invoice->id, 'receipt_number' => 'R-1', 'amount' => 10000, 'payment_date' => now(), 'payment_mode' => 'Bank']);

        Dsc::create(['client_id' => $this->other->id, 'holder_name' => 'H. Holder', 'class_type' => 'Class 3', 'provider' => 'eMudhra', 'issue_date' => now()->subYears(2), 'expiry_date' => now()->addDays(6), 'status' => Dsc::STATUS_ACTIVE]);

        Task::create(['client_id' => $this->mine->id, 'assigned_to' => $this->associate->id, 'created_by' => $this->partner->id, 'title' => 'Prepare GST working', 'status' => Task::STATUS_PENDING, 'priority' => 'High', 'due_date' => now()->addDay()]);
    }

    private function member(string $role, string $name): User
    {
        return User::factory()->create([
            'organization_id' => $this->org->id,
            'role' => $role,
            'name' => $name,
            'module_access' => ModuleAccess::defaultsForRole($role),
            'mobile' => '9198765'.random_int(10000, 99999),
        ]);
    }

    private function client(string $name, User $manager): Client
    {
        return Client::create([
            'client_code' => 'C-'.random_int(1000, 9999),
            'name' => $name,
            'pan' => strtoupper(substr(md5($name), 0, 5)).'1234A',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'manager_id' => $manager->id,
            'approval_status' => Client::APPROVAL_APPROVED,
            'organization_id' => $this->org->id,
        ]);
    }

    private function useLayout(User $user, string $layout): void
    {
        $user->forceFill(['dashboard_layout' => $layout])->save();
    }

    public function test_all_layouts_are_now_selectable(): void
    {
        $this->assertEqualsCanonicalizing(
            [DashboardLayout::CLASSIC, DashboardLayout::INBOX, DashboardLayout::BOARD, DashboardLayout::CLIENT],
            DashboardLayout::availableKeys()
        );
    }

    public function test_partner_inbox_shows_real_items_numbers_and_charts(): void
    {
        $this->useLayout($this->partner, DashboardLayout::INBOX);

        $this->actingAs($this->partner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-layout="inbox"', false)
            ->assertSee('Today’s action inbox')
            ->assertSee('GSTR-3B')
            ->assertSee('Partner Only Traders')
            ->assertSee('INV-T-1')
            ->assertSee('₹40,000')           // balance after part payment
            ->assertSee('DSC expiry – H. Holder')
            ->assertSee('Sales register')    // awaited document
            ->assertSee('Prepare GST working')
            ->assertSee('Billed vs collected')
            ->assertSee('Receivables by age')
            ->assertSee('Compliance calendar')
            ->assertSee('Team workload')
            ->assertSee('Clients needing attention');
    }

    public function test_associate_inbox_only_shows_own_clients_and_no_money(): void
    {
        $this->useLayout($this->associate, DashboardLayout::INBOX);

        $this->actingAs($this->associate)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Asha Client Pvt Ltd')
            ->assertSee('Prepare GST working')
            ->assertDontSee('Partner Only Traders')
            ->assertDontSee('INV-T-1')
            ->assertDontSee('Billed vs collected')
            ->assertDontSee('Team workload');
    }

    public function test_board_layout_groups_into_columns(): void
    {
        $this->useLayout($this->partner, DashboardLayout::BOARD);

        $this->actingAs($this->partner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-layout="board"', false)
            ->assertSeeInOrder(['Overdue', 'Due this week', 'Waiting on client', 'In progress'])
            ->assertSee('Partner Only Traders')
            ->assertSee('Next 7 days');
    }

    public function test_client_layout_lists_clients_riskiest_first_and_shows_detail(): void
    {
        $this->useLayout($this->partner, DashboardLayout::CLIENT);

        $this->actingAs($this->partner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-layout="client"', false)
            ->assertSeeInOrder(['Partner Only Traders', 'Asha Client Pvt Ltd'])
            ->assertSee('Upcoming &amp; overdue work', false)
            ->assertSee('Health breakdown');

        $this->actingAs($this->partner)->get(route('dashboard', ['client' => $this->mine->id]))
            ->assertOk()
            ->assertSee('Sales register')
            ->assertSee('Prepare GST working');

        $this->actingAs($this->partner)->get(route('dashboard', ['q' => 'Asha']))
            ->assertOk()
            ->assertSee('Asha Client Pvt Ltd')
            ->assertDontSee('Partner Only Traders');
    }

    public function test_associate_cannot_open_another_managers_client_in_client_layout(): void
    {
        $this->useLayout($this->associate, DashboardLayout::CLIENT);

        $this->actingAs($this->associate)->get(route('dashboard', ['client' => $this->other->id]))
            ->assertOk()
            ->assertDontSee('Partner Only Traders');
    }

    public function test_article_is_still_sent_to_tasks_whatever_the_layout(): void
    {
        $article = $this->member('article', 'Art Clerk');
        $this->useLayout($article, DashboardLayout::INBOX);

        $this->actingAs($article)->get(route('dashboard'))->assertRedirect(route('tasks.index'));
    }

    public function test_staff_filter_and_my_work_scope(): void
    {
        $this->useLayout($this->partner, DashboardLayout::INBOX);

        $this->actingAs($this->partner)->get(route('dashboard', ['scope' => 'firm', 'staff' => $this->associate->id]))
            ->assertOk()
            ->assertSee('Prepare GST working')
            ->assertDontSee('INV-T-1');

        $this->actingAs($this->partner)->get(route('dashboard', ['scope' => 'mine']))
            ->assertOk()
            ->assertSee('INV-T-1')
            ->assertDontSee('Prepare GST working');
    }

    public function test_period_switch_and_calendar_month(): void
    {
        $this->useLayout($this->partner, DashboardLayout::INBOX);

        foreach (['q', 'fy'] as $p) {
            $this->actingAs($this->partner)->get(route('dashboard', ['period' => $p]))->assertOk()->assertSee('Billed vs collected');
        }
        $this->actingAs($this->partner)->get(route('dashboard', ['month' => now()->addMonth()->format('Y-m')]))
            ->assertOk()
            ->assertSee(now()->addMonth()->format('F Y'));
    }

    public function test_snooze_hides_item_and_undo_restores_it(): void
    {
        $this->useLayout($this->partner, DashboardLayout::INBOX);
        $key = 'invoice:'.$this->invoice->id;

        $this->actingAs($this->partner)->postJson(route('dashboard.inbox.snooze'), ['key' => $key])->assertOk();
        $this->actingAs($this->partner)->get(route('dashboard'))->assertDontSee('INV-T-1');

        $this->actingAs($this->partner)->postJson(route('dashboard.inbox.snooze'), ['key' => $key, 'undo' => 1])->assertOk();
        $this->actingAs($this->partner)->get(route('dashboard'))->assertSee('INV-T-1');

        $this->actingAs($this->partner)->postJson(route('dashboard.inbox.snooze'), ['key' => 'bogus'])->assertStatus(422);
    }

    public function test_mark_filed_from_inbox_removes_it(): void
    {
        $this->useLayout($this->partner, DashboardLayout::INBOX);

        $this->actingAs($this->partner)->post(route('service-dues.complete', $this->overdueDue))->assertRedirect();
        $this->assertSame(ServiceDue::STATUS_COMPLETED, $this->overdueDue->fresh()->status);

        $html = $this->actingAs($this->partner)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString(route('service-dues.complete', $this->overdueDue), $html);
    }

    public function test_classic_layout_still_works(): void
    {
        $this->useLayout($this->partner, DashboardLayout::CLASSIC);

        $this->actingAs($this->partner)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-dashboard-layout="inbox"', false);
    }

    public function test_inbox_has_payment_popup_and_payment_can_be_recorded_as_json(): void
    {
        $this->useLayout($this->partner, DashboardLayout::INBOX);

        $this->actingAs($this->partner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dash-action="pay"', false)
            ->assertSee('id="dash-pay-dialog"', false);

        $this->actingAs($this->partner)->postJson(route('payments.store'), [
            'invoice_id' => $this->invoice->id,
            'amount' => 15000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'UPI',
        ])->assertOk()->assertJson(['success' => true, 'balance' => 25000]);

        $this->actingAs($this->partner)->postJson(route('payments.store'), [
            'invoice_id' => $this->invoice->id,
            'amount' => 999999,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'UPI',
        ])->assertStatus(422);

        $this->assertSame(25000.0, round($this->invoice->fresh()->balanceDue(), 2));
    }

    public function test_associate_does_not_get_payment_popup(): void
    {
        $this->useLayout($this->associate, DashboardLayout::INBOX);

        $this->actingAs($this->associate)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="dash-pay-dialog"', false);
    }

    public function test_user_can_choose_and_order_inbox_sections(): void
    {
        $this->useLayout($this->partner, DashboardLayout::INBOX);

        $this->actingAs($this->partner)->post(route('dashboard.inbox.sections'), [
            'sections' => ['attention', 'inbox'],
        ])->assertRedirect(route('dashboard'));

        $html = $this->actingAs($this->partner)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('data-dash-section="attention"', $html);
        $this->assertStringNotContainsString('data-dash-section="calendar"', $html);
        $this->assertStringNotContainsString('data-dash-section="revenue"', $html);
        $this->assertLessThan(strpos($html, 'data-dash-section="inbox"'), strpos($html, 'data-dash-section="attention"'));

        $this->actingAs($this->partner)->post(route('dashboard.inbox.sections'), ['sections' => ['bogus']])
            ->assertSessionHasErrors();

        $this->actingAs($this->partner)->post(route('dashboard.inbox.sections'), ['reset' => 1])->assertRedirect();
        $this->actingAs($this->partner)->get(route('dashboard'))->assertSee('data-dash-section="calendar"', false);
    }

    public function test_board_cards_are_draggable_with_status_targets(): void
    {
        $this->useLayout($this->partner, DashboardLayout::BOARD);

        $this->actingAs($this->partner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-drop-col="done"', false)
            ->assertSee('data-drop-col="doing"', false)
            ->assertSee('draggable="true"', false)
            ->assertSee('data-status-url=', false);
    }
}
