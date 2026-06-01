<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientWorksheet;
use App\Models\Dsc;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceDue;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DashboardMetricsService
{
    public function build(?User $user): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $userId = $user?->id;

        return [
            'summary' => $this->summary($today, $startOfMonth, $endOfMonth),
            'upcomingCounts' => $this->upcomingCounts($today, $userId),
            'serviceWisePending' => $this->serviceWisePending(),
            'highRiskClients' => $this->highRiskClients($today),
            'alerts' => $this->alerts($today),
            'calendarDues' => $this->calendarDues($startOfMonth, $endOfMonth),
            'myPendingTasks' => $this->myPendingTasks($userId),
            'complianceStats' => $this->complianceStats($startOfMonth, $endOfMonth),
            'recentClients' => $this->recentClients(),
            'pendingClientApprovals' => $this->pendingClientApprovals($user),
        ];
    }

    public function randomPositiveThought(): string
    {
        $thoughts = [
            "Believe you can and you're halfway there.",
            "The only way to do great work is to love what you do.",
            "Success is not final, failure is not fatal: it is the courage to continue that counts.",
            "Your limitation—it's only your imagination.",
            "Push yourself, because no one else is going to do it for you.",
            "Great things never come from comfort zones.",
            "Dream it. Wish it. Do it.",
            "Success doesn’t just find you. You have to go out and get it.",
            "The harder you work for something, the greater you’ll feel when you achieve it.",
            "Dream bigger. Do bigger.",
            "Don’t stop when you’re tired. Stop when you’re done.",
            "Wake up with determination. Go to bed with satisfaction.",
            "Do something today that your future self will thank you for.",
            "Little things make big days.",
            "It’s going to be hard, but hard does not mean impossible.",
            "Don’t wait for opportunity. Create it.",
            "Sometimes we’re tested not to show our weaknesses, but to discover our strengths.",
            "The key to success is to focus on goals, not obstacles.",
            "Dream it. Believe it. Build it.",
        ];

        return $thoughts[array_rand($thoughts)];
    }

    private function summary(Carbon $today, Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        return [
            'total_clients' => Client::count(),
            'new_clients_this_month' => Client::where('created_at', '>=', $startOfMonth)->count(),
            'services_due_month' => ServiceDue::whereBetween('due_date', [$startOfMonth, $endOfMonth])->count(),
            'services_due_today' => ServiceDue::whereDate('due_date', $today)->where('status', ServiceDue::STATUS_PENDING)->count(),
            'services_overdue' => ServiceDue::where('status', ServiceDue::STATUS_OVERDUE)->count(),
            'upcoming_renewals' => ServiceDue::where('status', ServiceDue::STATUS_PENDING)
                ->whereBetween('due_date', [$today, $today->copy()->addDays(30)])
                ->count(),
            'outstanding_fees' => '₹ ' . number_format(
                Invoice::whereIn('status', Invoice::OPEN_STATUSES)->sum('total_amount')
                    - Payment::whereHas('invoice', fn ($q) => $q->whereIn('status', Invoice::OPEN_STATUSES))->sum('amount'),
                0
            ),
            'overdue_collections' => '₹ ' . number_format(
                Invoice::where('status', Invoice::STATUS_OVERDUE)->sum('total_amount')
                    - Payment::whereHas('invoice', fn ($q) => $q->where('status', Invoice::STATUS_OVERDUE))->sum('amount'),
                0
            ),
            'collections_this_month' => '₹ ' . number_format(
                Payment::whereBetween('date', [$startOfMonth, $endOfMonth])->sum('amount'),
                0
            ),
            'unbilled_items' => $this->unbilledItemsCount(),
            'expiring_dscs' => Dsc::where('status', Dsc::STATUS_ACTIVE)
                ->where('expiry_date', '<=', $today->copy()->addDays(30))
                ->where('expiry_date', '>=', $today)
                ->count(),
        ];
    }

    private function upcomingCounts(Carbon $today, ?int $userId): array
    {
        $taskScope = fn (int $days) => Task::query()
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->when($userId, fn ($q) => $q->where('assigned_to', $userId))
            ->whereBetween('due_date', [$today, $today->copy()->addDays($days)]);

        return [
            '7_days' => ServiceDue::where('status', ServiceDue::STATUS_PENDING)
                    ->whereBetween('due_date', [$today, $today->copy()->addDays(7)])
                    ->count()
                + $taskScope(7)->count(),
            '15_days' => ServiceDue::where('status', ServiceDue::STATUS_PENDING)
                    ->whereBetween('due_date', [$today, $today->copy()->addDays(15)])
                    ->count()
                + $taskScope(15)->count(),
            '30_days' => ServiceDue::where('status', ServiceDue::STATUS_PENDING)
                    ->whereBetween('due_date', [$today, $today->copy()->addDays(30)])
                    ->count()
                + $taskScope(30)->count(),
        ];
    }

    private function serviceWisePending(): Collection
    {
        return ServiceDue::with('clientService.service')
            ->where('status', ServiceDue::STATUS_PENDING)
            ->get()
            ->groupBy(fn ($due) => $due->clientService->service->name)
            ->map
            ->count();
    }

    private function highRiskClients(Carbon $today): Collection
    {
        return ServiceDue::whereIn('status', [ServiceDue::STATUS_OVERDUE, ServiceDue::STATUS_PENDING])
            ->whereDate('due_date', '<=', $today)
            ->whereHas('clientService.client', fn ($q) => $q->where('category', 'A'))
            ->with('clientService.client')
            ->get()
            ->unique('clientService.client.id')
            ->pluck('clientService.client');
    }

    private function alerts(Carbon $today): Collection
    {
        return ServiceDue::with(['clientService.client', 'clientService.service'])
            ->where(function ($q) use ($today) {
                $q->where('status', ServiceDue::STATUS_OVERDUE)
                    ->orWhereDate('due_date', $today);
            })
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();
    }

    private function calendarDues(Carbon $startOfMonth, Carbon $endOfMonth): Collection
    {
        return ServiceDue::whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(fn ($due) => Carbon::parse($due->due_date)->format('Y-m-d'));
    }

    private function myPendingTasks(?int $userId): Collection
    {
        return Task::whereNotIn('status', Task::TERMINAL_STATUSES)
            ->where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId)
                    ->orWhere(function ($sub) use ($userId) {
                        $sub->whereNull('assigned_to')
                            ->where('created_by', $userId);
                    });
            })
            ->orderBy('due_date', 'asc')
            ->limit(100)
            ->with('client')
            ->get();
    }

    private function complianceStats(Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $base = ServiceDue::whereBetween('due_date', [$startOfMonth, $endOfMonth]);

        return [
            ServiceDue::STATUS_PENDING => (clone $base)->where('status', ServiceDue::STATUS_PENDING)->count(),
            ServiceDue::STATUS_COMPLETED => (clone $base)->where('status', ServiceDue::STATUS_COMPLETED)->count(),
            ServiceDue::STATUS_OVERDUE => (clone $base)->where('status', ServiceDue::STATUS_OVERDUE)->count(),
        ];
    }

    private function recentClients(): Collection
    {
        return Client::orderBy('updated_at', 'desc')->take(5)->get();
    }

    private function pendingClientApprovals(?User $user): int
    {
        if (! $user?->isPartner() || ! Schema::hasColumn('clients', 'approval_status')) {
            return 0;
        }

        return Client::query()
            ->where('approval_status', Client::APPROVAL_PENDING)
            ->count();
    }

    private function unbilledItemsCount(): int
    {
        $count = 0;

        if (Schema::hasTable('service_dues')
            && Schema::hasColumn('service_dues', 'billing_status')
            && Schema::hasColumn('service_dues', 'invoice_id')) {
            $count += ServiceDue::where('status', ServiceDue::STATUS_COMPLETED)
                ->where('billing_status', ServiceDue::BILLING_STATUS_UNBILLED)
                ->whereNull('invoice_id')
                ->count();
        }

        if (Schema::hasTable('client_worksheets')) {
            $count += ClientWorksheet::where('is_billed', false)->whereNull('invoice_id')->count();
        }

        return $count;
    }
}
