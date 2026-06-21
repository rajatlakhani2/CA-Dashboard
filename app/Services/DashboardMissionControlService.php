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
use App\Support\UserTimezone;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class DashboardMissionControlService
{
    public function build(?User $user): array
    {
        $tz = UserTimezone::for($user);
        $today = Carbon::now($tz)->startOfDay();
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
        $monthlyDeadlines = app(DashboardDeadlineOverviewService::class)->monthlyServiceDeadlines($user);

        return [
            'greeting' => $this->greeting($user),
            'today_strip' => $todayStrip,
            'executive_kpis' => $this->executiveKpis($user, $today, $managesFirm),
            'monthly_deadlines' => $monthlyDeadlines,
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
            $strip[] = [
                'label' => 'Tasks next 7 days',
                'value' => Task::query()
                    ->whereNotIn('status', Task::TERMINAL_STATUSES)
                    ->when(! $managesFirm && $userId, fn ($q) => $q->where('assigned_to', $userId))
                    ->whereBetween('due_date', [$today, $today->copy()->addDays(7)])
                    ->count(),
                'url' => route('tasks.index', ['due' => 'next_7']),
                'tone' => 'blue',
            ];
            $strip[] = [
                'label' => 'Tasks next 15 days',
                'value' => Task::query()
                    ->whereNotIn('status', Task::TERMINAL_STATUSES)
                    ->when(! $managesFirm && $userId, fn ($q) => $q->where('assigned_to', $userId))
                    ->whereBetween('due_date', [$today, $today->copy()->addDays(15)])
                    ->count(),
                'url' => route('tasks.index', ['due' => 'next_15']),
                'tone' => 'blue',
            ];
        }

        if (ModuleGate::allowed($user, 'service_dues')) {
            $dueNext7 = ServiceDue::query()
                ->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
                ->whereBetween('due_date', [$today, $today->copy()->addDays(7)])
                ->count();

            $dueNext15 = ServiceDue::query()
                ->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
                ->whereBetween('due_date', [$today, $today->copy()->addDays(15)])
                ->count();

            $strip[] = ['label' => 'Due today', 'value' => $complianceDueToday, 'url' => route('service-dues.index'), 'tone' => 'violet'];
            $dueWindowUrl = fn (int $days) => $managesFirm
                ? route('reports.due-date', [
                    'start_date' => $today->format('Y-m-d'),
                    'end_date' => $today->copy()->addDays($days)->format('Y-m-d'),
                ])
                : route('service-dues.index');

            $strip[] = [
                'label' => 'Due next 7 days',
                'value' => $dueNext7,
                'url' => $dueWindowUrl(7),
                'tone' => 'amber',
            ];
            $strip[] = [
                'label' => 'Due next 15 days',
                'value' => $dueNext15,
                'url' => $dueWindowUrl(15),
                'tone' => 'amber',
            ];
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

    /** Ordered KPI grid for the executive summary right column. */
    private function executiveKpis(?User $user, Carbon $today, bool $managesFirm): array
    {
        $userId = $user?->id;
        $tomorrow = $today->copy()->addDay();
        $kpis = [];

        if (ModuleGate::allowed($user, 'tasks')) {
            $taskQuery = fn () => Task::query()
                ->whereNotIn('status', Task::TERMINAL_STATUSES)
                ->when(! $managesFirm && $userId, fn ($q) => $q->where('assigned_to', $userId));

            $kpis[] = [
                'label' => 'Tasks due today',
                'value' => $taskQuery()->whereDate('due_date', $today)->count(),
                'url' => route('tasks.index', ['due' => 'due_today']),
                'tone' => 'amber',
            ];
            $overdueCount = $taskQuery()->whereDate('due_date', '<', $today)->count();
            $kpis[] = [
                'label' => 'Tasks overdue',
                'value' => $overdueCount,
                'url' => route('dashboard', [
                    'tab' => 'calendar',
                    'show_tasks' => 1,
                    'show_dues' => 0,
                    'due_status' => 'overdue',
                ]),
                'tone' => $overdueCount > 0 ? 'rose' : 'sky',
            ];
            $kpis[] = [
                'label' => 'Tasks next 7 days',
                'value' => $taskQuery()->whereBetween('due_date', [$today, $today->copy()->addDays(7)])->count(),
                'url' => route('tasks.index', ['due' => 'next_7']),
                'tone' => 'sky',
            ];
            $kpis[] = [
                'label' => 'Tasks next 15 days',
                'value' => $taskQuery()->whereBetween('due_date', [$today, $today->copy()->addDays(15)])->count(),
                'url' => route('tasks.index', ['due' => 'next_15']),
                'tone' => 'indigo',
            ];
        }

        if (ModuleGate::allowed($user, 'service_dues')) {
            $kpis[] = [
                'label' => 'Compliance overdue',
                'value' => ServiceDue::query()->where('status', ServiceDue::STATUS_OVERDUE)->count(),
                'url' => route('service-dues.index'),
                'tone' => 'rose',
            ];
        }

        if ($managesFirm && ModuleGate::allowed($user, 'invoices')) {
            $kpis[] = [
                'label' => 'Overdue invoices',
                'value' => Invoice::where('status', Invoice::STATUS_OVERDUE)->count(),
                'url' => route('collections.index'),
                'tone' => 'emerald',
            ];
        }

        if ($managesFirm && ModuleGate::allowed($user, 'clients')) {
            $kpis[] = [
                'label' => 'Total clients',
                'value' => Client::count(),
                'url' => route('clients.index'),
                'tone' => 'blue',
            ];
        }

        if (ModuleGate::allowed($user, 'service_dues')) {
            $kpis[] = [
                'label' => 'Due tomorrow',
                'value' => ServiceDue::query()
                    ->whereDate('due_date', $tomorrow)
                    ->where('status', ServiceDue::STATUS_PENDING)
                    ->count(),
                'url' => route('service-dues.index'),
                'tone' => 'violet',
            ];
        } elseif (ModuleGate::allowed($user, 'tasks')) {
            $kpis[] = [
                'label' => 'Due tomorrow',
                'value' => Task::query()
                    ->whereNotIn('status', Task::TERMINAL_STATUSES)
                    ->when(! $managesFirm && $userId, fn ($q) => $q->where('assigned_to', $userId))
                    ->whereDate('due_date', $tomorrow)
                    ->count(),
                'url' => route('tasks.index', ['due' => 'next_7']),
                'tone' => 'violet',
            ];
        }

        return $kpis;
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
            ->filter(fn (array $row) => array_key_exists('score', $row) && $row['score'] !== null)
            ->sortBy('score')
            ->take($limit)
            ->values();
    }

    /** Masked executive finance widget — fetch on reveal via dashboard.finance-snapshot. */
    public function executiveFinanceSnapshot(?User $user): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $rev = $this->revenueMetrics($startOfMonth, $endOfMonth);
        $summary = app(DashboardMetricsService::class)->build($user)['summary'];

        return [
            'target' => $rev['target_formatted'] ?? '—',
            'achieved' => $rev['achieved_formatted'] ?? '₹ 0',
            'efficiency' => ($rev['collection_efficiency'] ?? 0) . '%',
            'outstanding' => $rev['outstanding_formatted'] ?? '₹ 0',
            'collected_mtd' => '₹ ' . number_format($rev['collected_mtd'] ?? 0, 0),
            'overdue' => $summary['overdue_collections'] ?? '₹ 0',
            'collected_today' => '₹ ' . number_format($rev['collected_today'] ?? 0, 0),
            'progress_percent' => $rev['progress_percent'],
        ];
    }
}
