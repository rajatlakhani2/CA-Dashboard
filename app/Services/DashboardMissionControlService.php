<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Dsc;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceDue;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Support\ModuleGate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class DashboardMissionControlService
{
    public function build(?User $user): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $managesFirm = $user?->managesFirmModules() ?? false;

        $hasFinance = ModuleGate::hasFinanceModule($user);
        $revenue = $hasFinance ? $this->revenueMetrics($startOfMonth, $endOfMonth) : [];
        $teamWorkload = $managesFirm && ModuleGate::allowed($user, 'staff') ? $this->teamWorkload() : collect();
        $todayStrip = $this->todayStrip($user, $today, $managesFirm);
        $riskAlerts = $this->riskAlerts($user, $today, $managesFirm);
        $aiInsights = $this->aiInsights($user, $today, $managesFirm, $revenue, $teamWorkload);
        $firmPulse = $this->firmPulse($today);
        $clientsNeedingAttention = $managesFirm
            ? $this->clientsNeedingAttention($user, 6)
            : collect();

        return [
            'greeting' => $this->greeting($user),
            'today_strip' => $todayStrip,
            'risk_alerts' => $riskAlerts,
            'ai_insights' => $aiInsights,
            'firm_pulse' => $firmPulse,
            'revenue' => $revenue,
            'team_workload' => $teamWorkload,
            'clients_needing_attention' => $clientsNeedingAttention,
        ];
    }

    private function greeting(?User $user): string
    {
        $hour = (int) now()->format('H');
        $time = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $name = $user?->name ? explode(' ', trim($user->name))[0] : 'there';

        return "{$time}, {$name}";
    }

    private function todayStrip(?User $user, Carbon $today, bool $managesFirm): array
    {
        $userId = $user?->id;
        $tasksDueToday = Task::query()
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->when(! $managesFirm && $userId, fn ($q) => $q->where('assigned_to', $userId))
            ->whereDate('due_date', $today)
            ->count();

        $tasksOverdue = Task::query()
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->when(! $managesFirm && $userId, fn ($q) => $q->where('assigned_to', $userId))
            ->whereDate('due_date', '<', $today)
            ->count();

        $complianceDueToday = ServiceDue::query()
            ->whereDate('due_date', $today)
            ->where('status', ServiceDue::STATUS_PENDING)
            ->count();

        $complianceOverdue = ServiceDue::query()
            ->where('status', ServiceDue::STATUS_OVERDUE)
            ->count();

        $collectionsPending = $managesFirm
            ? Invoice::where('status', Invoice::STATUS_OVERDUE)->count()
            : 0;

        $strip = [];

        if (ModuleGate::allowed($user, 'tasks')) {
            $strip[] = [
                'label' => 'Tasks due today',
                'value' => $tasksDueToday,
                'url' => route('tasks.index', ['due' => 'due_today']),
                'tone' => 'amber',
            ];
            $strip[] = [
                'label' => 'Tasks overdue',
                'value' => $tasksOverdue,
                'url' => route('dashboard', [
                    'tab' => 'calendar',
                    'show_tasks' => 1,
                    'show_dues' => 0,
                    'due_status' => 'overdue',
                ]),
                'tone' => 'rose',
            ];
        }

        if (ModuleGate::allowed($user, 'service_dues')) {
            $strip[] = ['label' => 'Due today', 'value' => $complianceDueToday, 'url' => route('service-dues.index'), 'tone' => 'violet'];
            $strip[] = ['label' => 'Compliance overdue', 'value' => $complianceOverdue, 'url' => route('service-dues.index'), 'tone' => 'rose'];
        }

        if ($managesFirm && ModuleGate::allowed($user, 'invoices')) {
            $strip[] = ['label' => 'Overdue invoices', 'value' => $collectionsPending, 'url' => route('collections.index'), 'tone' => 'emerald'];
        }

        if ($managesFirm && ModuleGate::allowed($user, 'clients')) {
            $strip[] = ['label' => 'Total clients', 'value' => Client::count(), 'url' => route('clients.index'), 'tone' => 'blue'];
        }

        if ($managesFirm && ModuleGate::allowed($user, 'dsc')) {
            $strip[] = ['label' => 'DSC expiring (30d)', 'value' => Dsc::where('status', Dsc::STATUS_ACTIVE)
                ->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])
                ->count(), 'url' => route('dscs.index'), 'tone' => 'amber'];
        }

        return $strip;
    }

    private function riskAlerts(?User $user, Carbon $today, bool $managesFirm): array
    {
        if (! $managesFirm) {
            return [];
        }

        $alerts = [];

        if (! ModuleGate::allowed($user, 'service_dues')) {
            return [];
        }

        $gstOverdue = ServiceDue::query()
            ->where('status', ServiceDue::STATUS_OVERDUE)
            ->whereHas('clientService.service', fn ($q) => $q->where('name', 'like', '%GST%'))
            ->count();
        if ($gstOverdue > 0) {
            $alerts[] = ['label' => 'GST filings overdue', 'count' => $gstOverdue, 'url' => route('service-dues.index'), 'severity' => 'high'];
        }

        $itrOverdue = ServiceDue::query()
            ->where('status', ServiceDue::STATUS_OVERDUE)
            ->whereHas('clientService.service', fn ($q) => $q->where(function ($s) {
                $s->where('name', 'like', '%IT%')->orWhere('name', 'like', '%Income%');
            }))
            ->count();
        if ($itrOverdue > 0) {
            $alerts[] = ['label' => 'ITR / income-tax overdue', 'count' => $itrOverdue, 'url' => route('service-dues.index'), 'severity' => 'high'];
        }

        if (ModuleGate::allowed($user, 'dsc')) {
            $dscExpiring = Dsc::where('status', Dsc::STATUS_ACTIVE)
                ->where('expiry_date', '<=', $today->copy()->addDays(30))
                ->where('expiry_date', '>=', $today)
                ->count();
            if ($dscExpiring > 0) {
                $alerts[] = ['label' => 'DSC expiring soon', 'count' => $dscExpiring, 'url' => route('dscs.index'), 'severity' => 'medium'];
            }
        }

        if (ModuleGate::allowed($user, 'billing')) {
            $unbilledCount = 0;
            if (\Illuminate\Support\Facades\Schema::hasColumn('service_dues', 'billing_status')) {
                $unbilledCount = ServiceDue::where('status', ServiceDue::STATUS_COMPLETED)
                    ->where('billing_status', ServiceDue::BILLING_STATUS_UNBILLED)
                    ->whereNull('invoice_id')
                    ->count();
            }
            if ($unbilledCount > 0) {
                $alerts[] = ['label' => 'Completed work not billed', 'count' => $unbilledCount, 'url' => route('billing.index'), 'severity' => 'medium'];
            }
        }

        return array_slice($alerts, 0, 5);
    }

    private function aiInsights(?User $user, Carbon $today, bool $managesFirm, array $revenue, Collection $teamWorkload): array
    {
        if (! $managesFirm) {
            return [];
        }

        $insights = [];
        $gstClients = ServiceDue::query()
            ->where('status', ServiceDue::STATUS_OVERDUE)
            ->whereHas('clientService.service', fn ($q) => $q->where('name', 'like', '%GST%'))
            ->distinct('client_service_id')
            ->count('client_service_id');
        if ($gstClients > 0) {
            $insights[] = "{$gstClients} client service(s) have overdue GST compliance.";
        }

        if (ModuleGate::hasFinanceModule($user) && ($revenue['outstanding_amount'] ?? 0) > 0) {
            $insights[] = '₹' . number_format($revenue['outstanding_amount'], 0) . ' outstanding across open invoices.';
        }

        $overloaded = $teamWorkload->sortByDesc('open_tasks')->first();
        $avgTasks = (int) round($teamWorkload->avg('open_tasks') ?: 0);
        if ($overloaded && $avgTasks > 0 && $overloaded['open_tasks'] > $avgTasks * 1.4) {
            $insights[] = "{$overloaded['name']} has {$overloaded['open_tasks']} open tasks (above team average).";
        }

        if (ModuleGate::allowed($user, 'invoices')) {
            $threeMonthsAgo = $today->copy()->subMonths(3);
            $unbilledClients = Client::query()
                ->whereDoesntHave('invoices', fn ($q) => $q->where('date', '>=', $threeMonthsAgo))
                ->where('status', Client::STATUS_ACTIVE)
                ->count();
            if ($unbilledClients > 0) {
                $insights[] = "{$unbilledClients} active client(s) with no invoice in the last 3 months.";
            }
        }

        return array_slice($insights, 0, 5);
    }

    private function firmPulse(Carbon $today): array
    {
        $start = $today->copy()->startOfDay();

        $tasksCompleted = Task::query()
            ->whereIn('status', Task::TERMINAL_STATUSES)
            ->where('updated_at', '>=', $start)
            ->count();

        $clientsAdded = Client::where('created_at', '>=', $start)->count();

        $collected = (float) Payment::where('payment_date', '>=', $start->toDateString())->sum('amount');

        $filingsCompleted = ServiceDue::query()
            ->where('status', ServiceDue::STATUS_COMPLETED)
            ->where('updated_at', '>=', $start)
            ->count();

        $feed = Activity::query()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Activity $a) => [
                'time' => $a->created_at->format('H:i'),
                'text' => $a->description ?: (class_basename($a->subject_type ?? 'Record') . ' ' . ($a->event ?? 'updated')),
            ]);

        return [
            'tasks_completed' => $tasksCompleted,
            'clients_added' => $clientsAdded,
            'collected' => $collected,
            'filings_completed' => $filingsCompleted,
            'feed' => $feed,
        ];
    }

    private function revenueMetrics(Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $target = (float) Setting::get('monthly_revenue_target', 0);
        $invoicedMtd = (float) Invoice::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', [Invoice::STATUS_CANCELLED])
            ->sum('total_amount');
        $collectedMtd = (float) Payment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])->sum('amount');
        $outstanding = (float) Invoice::whereIn('status', Invoice::OPEN_STATUSES)->sum('total_amount')
            - (float) Payment::whereHas('invoice', fn ($q) => $q->whereIn('status', Invoice::OPEN_STATUSES))->sum('amount');
        $outstanding = max(0, $outstanding);

        $invoicedTotal = (float) Invoice::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', [Invoice::STATUS_CANCELLED])
            ->sum('total_amount');
        $efficiency = $invoicedTotal > 0 ? min(100, round(($collectedMtd / $invoicedTotal) * 100)) : ($collectedMtd > 0 ? 100 : 0);
        $progress = $target > 0 ? min(100, round(($invoicedMtd / $target) * 100)) : null;

        return [
            'target' => $target,
            'achieved' => $invoicedMtd,
            'collected_mtd' => $collectedMtd,
            'progress_percent' => $progress,
            'collection_efficiency' => $efficiency,
            'outstanding_amount' => $outstanding,
            'outstanding_formatted' => '₹ ' . number_format($outstanding, 0),
            'achieved_formatted' => '₹ ' . number_format($invoicedMtd, 0),
            'target_formatted' => $target > 0 ? '₹ ' . number_format($target, 0) : 'Set target in Settings',
            'collected_today' => (float) Payment::whereDate('payment_date', Carbon::today())->sum('amount'),
        ];
    }

    private function teamWorkload(): Collection
    {
        return User::query()
            ->whereIn('role', ['partner', 'manager', 'associate', 'staff', 'article'])
            ->get()
            ->map(function (User $user) {
                $open = Task::query()
                    ->where('assigned_to', $user->id)
                    ->whereNotIn('status', Task::TERMINAL_STATUSES)
                    ->count();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'open_tasks' => $open,
                    'status' => $open >= 15 ? 'overloaded' : ($open <= 2 ? 'idle' : 'normal'),
                ];
            })
            ->sortByDesc('open_tasks')
            ->values();
    }

    private function clientsNeedingAttention(?User $user, int $limit): Collection
    {
        $health = app(ClientHealthScoreService::class);

        return Client::query()
            ->visibleTo($user)
            ->where('status', Client::STATUS_ACTIVE)
            ->when($user?->isPartner(), fn ($q) => $q->where('approval_status', Client::APPROVAL_APPROVED))
            ->latest('updated_at')
            ->limit(40)
            ->get()
            ->map(fn (Client $c) => array_merge(['client' => $c], $health->forClient($c)))
            ->sortBy('score')
            ->take($limit)
            ->values();
    }
}
