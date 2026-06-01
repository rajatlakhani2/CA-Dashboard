<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\Reports\ClientProfitabilityReportBuilder;
use App\Services\Reports\StaffProductivityReportBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductivityProfitabilityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_view_productivity_reports(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('reports.staff-productivity'))->assertForbidden();
        $this->actingAs($staff)->get(route('reports.client-profitability'))->assertForbidden();
    }

    public function test_partner_staff_productivity_report(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $member = User::factory()->create(['role' => 'staff', 'name' => 'Productive Pat']);
        $client = Client::factory()->create();

        $task = Task::create([
            'client_id' => $client->id,
            'assigned_to' => $member->id,
            'title' => 'Done work',
            'status' => Task::STATUS_COMPLETED,
            'due_date' => now()->subDays(2),
            'created_by' => $partner->id,
        ]);
        $task->forceFill(['updated_at' => now()])->save();

        TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'date' => now(),
            'hours' => 6,
            'is_billable' => true,
        ]);

        $this->actingAs($partner)
            ->get(route('reports.staff-productivity'))
            ->assertOk()
            ->assertSee('Productive Pat', false)
            ->assertSee('Staff Productivity', false);

        $report = app(StaffProductivityReportBuilder::class)->build(
            $partner,
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );

        $row = $report['rows']->firstWhere(fn ($r) => $r->user->id === $member->id);
        $this->assertSame(1, $row->completed_count);
        $this->assertSame(6.0, $row->total_hours);
    }

    public function test_client_profitability_flags_low_margin(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create(['name' => 'Thin Margin Co']);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-LM',
            'date' => now(),
            'due_date' => now()->addDays(15),
            'status' => Invoice::STATUS_OVERDUE,
            'subtotal' => 5000,
            'total_amount' => 5000,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_number' => 'RCP-1',
            'amount' => 1000,
            'payment_date' => now(),
            'payment_mode' => 'UPI',
        ]);

        $task = Task::create([
            'client_id' => $client->id,
            'assigned_to' => $partner->id,
            'title' => 'Heavy work',
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->addDays(5),
            'created_by' => $partner->id,
        ]);

        TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $partner->id,
            'date' => now(),
            'hours' => 12,
            'is_billable' => true,
        ]);

        $this->actingAs($partner)
            ->get(route('reports.client-profitability'))
            ->assertOk()
            ->assertSee('Thin Margin Co', false)
            ->assertSee('Review', false);

        $report = app(ClientProfitabilityReportBuilder::class)->build(
            $partner,
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );

        $row = $report['rows']->first();
        $this->assertTrue($row->low_margin);
        $this->assertLessThan(50, $row->realization_rate);
    }
}
