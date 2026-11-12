<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Dsc;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceDue;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Real data for the Action inbox, Work board and Client first dashboard layouts.
 *
 * Everything is scoped to what the signed-in user may see (Client::visibleTo),
 * plus the shared branch / staff filters from the dashboard filter bar.
 */
class DashboardLayoutDataService
{
    public const SNOOZE_HOURS = 24;

    private Carbon $today;

    public function __construct()
    {
        $this->today = Carbon::today();
    }

    /** Filters shared by every widget: branch, staff, period, scope (firm|mine). */
    public function filters(Request $request, User $user): array
    {
        $canSeeFirm = $user->isPartner() || $user->isManager();

        $scope = $canSeeFirm ? ($request->query('scope') === 'mine' ? 'mine' : 'firm') : 'mine';
        $staffId = $canSeeFirm ? (int) $request->query('staff') : 0;
        $branchId = $canSeeFirm ? (int) $request->query('branch') : 0;
        $period = in_array($request->query('period'), ['6m', 'q', 'fy'], true) ? $request->query('period') : '6m';

        if ($scope === 'mine') {
            $staffId = $user->id;
        }

        return [
            'scope' => $scope,
            'staff' => $staffId ?: null,
            'branch' => $branchId ?: null,
            'period' => $period,
            'can_see_firm' => $canSeeFirm,
        ];
    }

    /** Options for the filter dropdowns. */
    public function filterOptions(User $user): array
    {
        return [
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values()->all(),
            'staff' => $this->teamMembers($user)
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values()->all(),
        ];
    }

    /** Client query limited to visibility + branch/staff filters. */
    public function clientScope(User $user, array $f): Builder
    {
        $q = Client::query()->visibleTo($user)->where('status', '!=', Client::STATUS_CLOSED);

        if ($f['branch']) {
            $q->where('branch_id', $f['branch']);
        }
        if ($f['staff']) {
            $q->where('manager_id', $f['staff']);
        }

        return $q;
    }

    // ---------------------------------------------------------------------
    // Inbox / board items
    // ---------------------------------------------------------------------

    /**
     * Everything that needs action soon, newest risk first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function actionItems(User $user, array $f, int $horizonDays = 7): Collection
    {
        $snoozed = $this->snoozedKeys($user);
        $clientIds = $this->clientScope($user, $f)->pluck('id');
        $horizon = $this->today->copy()->addDays($horizonDays);

        $items = collect();

        if ($user->canAccessModule('service_dues')) {
            $dues = ServiceDue::query()
                ->with(['clientService.client.manager:id,name', 'clientService.service:id,name'])
                ->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE, ServiceDue::STATUS_EXTENDED])
                ->whereDate('due_date', '<=', $horizon)
                ->whereHas('clientService', fn ($q) => $q->whereIn('client_id', $clientIds))
                ->orderBy('due_date')
                ->limit(150)
                ->get();

            foreach ($dues as $due) {
                $client = $due->clientService?->client;
                if (! $client) {
                    continue;
                }
                $items->push($this->item('due', $due->id, [
                    'kind' => 'filing',
                    'title' => ($due->clientService->service->name ?? 'Service').' · '.$due->due_date->format('M Y'),
                    'client' => $client,
                    'who' => $client->manager?->name,
                    'who_id' => $client->manager_id,
                    'date' => $due->due_date,
                    'stage' => 'todo',
                    'complete_url' => route('service-dues.complete', $due),
                    'complete_label' => 'Mark filed',
                    'remind_url' => ($user->isPartner() || $user->isManager()) ? route('service-dues.whatsapp', $due) : null,
                    'open_url' => route('clients.show', $client),
                ]));
            }
        }

        if ($user->canAccessModule('invoices') && ($user->isPartner() || $user->isManager())) {
            $canPay = $user->canAccessModule('payments');
            $invoices = Invoice::query()
                ->with('client:id,name,manager_id', 'client.manager:id,name')
                ->withSum('payments', 'amount')
                ->whereIn('status', Invoice::OPEN_STATUSES)
                ->whereIn('client_id', $clientIds)
                ->where(function ($q) {
                    $q->whereDate('due_date', '<', $this->today)
                        ->orWhere(fn ($q2) => $q2->whereNull('due_date')->whereDate('date', '<', $this->today->copy()->subDays(30)));
                })
                ->orderBy('due_date')
                ->limit(60)
                ->get();

            foreach ($invoices as $inv) {
                $balance = (float) $inv->total_amount - (float) $inv->payments_sum_amount;
                if ($balance <= 0.5 || ! $inv->client) {
                    continue;
                }
                $dueOn = $inv->due_date ?? $inv->date;
                $items->push($this->item('invoice', $inv->id, [
                    'kind' => 'payment',
                    'title' => 'Invoice '.($inv->invoice_number ?: '#'.$inv->id).' · '.$this->money($balance),
                    'client' => $inv->client,
                    'who' => $inv->client->manager?->name,
                    'who_id' => $inv->client->manager_id,
                    'date' => $dueOn,
                    'stage' => 'todo',
                    'complete_url' => null,
                    'complete_label' => 'Record payment',
                    'link_url' => $user->canAccessModule('payments') ? route('payments.create', ['invoice_id' => $inv->id]) : route('invoices.show', $inv),
                    'remind_url' => route('invoices.whatsapp', $inv),
                    'open_url' => route('invoices.show', $inv),
                    'due_text' => $dueOn ? $dueOn->diffInDays($this->today).' days overdue' : 'Unpaid',
                    'amount' => $balance,
                    'pay' => $canPay ? [
                        'invoice_id' => $inv->id,
                        'number' => $inv->invoice_number ?: '#'.$inv->id,
                        'client' => $inv->client->name,
                        'balance' => round($balance, 2),
                    ] : null,
                ]));
            }
        }

        if ($user->canAccessModule('dsc')) {
            $dscs = Dsc::query()
                ->with('client:id,name,manager_id', 'client.manager:id,name')
                ->whereIn('client_id', $clientIds)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', $this->today->copy()->addDays(30))
                ->whereDate('expiry_date', '>=', $this->today->copy()->subDays(30))
                ->orderBy('expiry_date')
                ->limit(40)
                ->get();

            foreach ($dscs as $dsc) {
                if (! $dsc->client) {
                    continue;
                }
                $items->push($this->item('dsc', $dsc->id, [
                    'kind' => 'dsc',
                    'title' => 'DSC expiry – '.($dsc->holder_name ?: 'holder'),
                    'client' => $dsc->client,
                    'who' => $dsc->client->manager?->name,
                    'who_id' => $dsc->client->manager_id,
                    'date' => $dsc->expiry_date,
                    'stage' => 'todo',
                    'complete_url' => null,
                    'complete_label' => 'Update DSC',
                    'link_url' => route('dscs.edit', $dsc),
                    'remind_url' => null,
                    'open_url' => route('dscs.edit', $dsc),
                ]));
            }
        }

        if ($user->canAccessModule('clients')) {
            foreach ($this->missingDocuments($clientIds, $horizonDays + 7) as $doc) {
                $items->push($this->item('doc', $doc['key'], [
                    'kind' => 'docs',
                    'title' => $doc['name'].' – '.$doc['service'],
                    'client' => $doc['client'],
                    'who' => $doc['client']->manager?->name,
                    'who_id' => $doc['client']->manager_id,
                    'date' => $doc['due_date'],
                    'stage' => 'waiting',
                    'complete_url' => $doc['toggle_url'],
                    'complete_method' => 'PATCH',
                    'complete_payload' => ['received' => 1],
                    'complete_label' => 'Mark received',
                    'remind_url' => null,
                    'open_url' => route('clients.show', $doc['client']),
                    'due_text' => 'Needed for '.$doc['due_date']->format('d M'),
                ]));
            }
        }

        if ($user->canAccessModule('tasks')) {
            $tasks = Task::query()
                ->with(['client:id,name,manager_id', 'assignee:id,name'])
                ->whereNotIn('status', Task::TERMINAL_STATUSES)
                ->where(function ($q) use ($user, $f, $clientIds) {
                    if ($f['staff']) {
                        $q->where('assigned_to', $f['staff']);
                    } elseif ($user->isPartner() || $user->isManager()) {
                        $q->whereIn('client_id', $clientIds)->orWhereNull('client_id');
                    } else {
                        $q->where('assigned_to', $user->id)->orWhere('created_by', $user->id);
                    }
                })
                ->where(fn ($q) => $q->whereNull('due_date')->orWhereDate('due_date', '<=', $horizon))
                ->orderByRaw('due_date is null')
                ->orderBy('due_date')
                ->limit(80)
                ->get();

            foreach ($tasks as $task) {
                $stage = match ($task->status) {
                    Task::STATUS_IN_PROGRESS => 'doing',
                    Task::STATUS_ON_HOLD => 'waiting',
                    default => 'todo',
                };
                $items->push($this->item('task', $task->id, [
                    'kind' => 'task',
                    'title' => $task->title,
                    'client' => $task->client,
                    'who' => $task->assignee?->name,
                    'who_id' => $task->assigned_to,
                    'date' => $task->due_date,
                    'stage' => $stage,
                    'complete_url' => route('tasks.update-status', $task),
                    'complete_method' => 'PATCH',
                    'complete_payload' => ['status' => Task::STATUS_COMPLETED],
                    'complete_json' => true,
                    'complete_label' => 'Mark done',
                    'start_payload' => $stage === 'todo' ? ['status' => Task::STATUS_IN_PROGRESS] : null,
                    'remind_url' => null,
                    'open_url' => route('tasks.edit', $task),
                ]));
            }
        }

        return $items
            ->reject(fn ($i) => in_array($i['key'], $snoozed, true))
            ->sortBy(fn ($i) => [$i['urgency_rank'], $i['sort_date']])
            ->values();
    }

    private function item(string $type, int|string $id, array $data): array
    {
        /** @var Carbon|null $date */
        $date = $data['date'] ?? null;
        $days = $date ? (int) $this->today->diffInDays($date, false) : null;

        $urgency = match (true) {
            $days === null => 'ok',
            $days < 0 => 'over',
            $days <= 3 => 'soon',
            default => 'ok',
        };

        $dueText = $data['due_text'] ?? match (true) {
            $days === null => 'No date',
            $days < 0 => 'Overdue · '.$date->format('d M'),
            $days === 0 => 'Today',
            $days === 1 => 'Tomorrow',
            default => 'In '.$days.' days · '.$date->format('d M'),
        };

        $client = $data['client'] ?? null;

        return array_merge($data, [
            'key' => $type.':'.$id,
            'type' => $type,
            'client_name' => $client?->name ?? '—',
            'client_id' => $client?->id,
            'urgency' => $urgency,
            'urgency_rank' => ['over' => 0, 'soon' => 1, 'ok' => 2][$urgency],
            'sort_date' => $date?->format('Y-m-d') ?? '9999-12-31',
            'due_text' => $dueText,
            'complete_method' => $data['complete_method'] ?? 'POST',
            'complete_payload' => $data['complete_payload'] ?? [],
            'complete_json' => $data['complete_json'] ?? false,
            'link_url' => $data['link_url'] ?? null,
            'start_payload' => $data['start_payload'] ?? null,
            'pay' => $data['pay'] ?? null,
            'client' => null,
            'date' => $date?->format('Y-m-d'),
        ]);
    }

    /** Required documents not yet received for services with a due date coming up. */
    private function missingDocuments(Collection $clientIds, int $days): Collection
    {
        $services = ClientService::query()
            ->with([
                'client:id,name,manager_id',
                'client.manager:id,name',
                'service:id,name',
                'service.documentRequirements:id,service_id,name,sort_order',
                'documentChecks',
                'dues' => fn ($q) => $q->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
                    ->whereDate('due_date', '<=', $this->today->copy()->addDays($days))
                    ->orderBy('due_date'),
            ])
            ->whereIn('client_id', $clientIds)
            ->whereHas('dues', fn ($q) => $q->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
                ->whereDate('due_date', '<=', $this->today->copy()->addDays($days)))
            ->whereHas('service.documentRequirements')
            ->limit(80)
            ->get();

        $out = collect();
        foreach ($services as $cs) {
            $received = $cs->documentChecks->where('is_received', true)->pluck('service_document_requirement_id')->all();
            $due = $cs->dues->first();
            if (! $due || ! $cs->client) {
                continue;
            }
            foreach ($cs->service->documentRequirements as $req) {
                if (in_array($req->id, $received, true)) {
                    continue;
                }
                $out->push([
                    'key' => $cs->id.'-'.$req->id,
                    'name' => $req->name,
                    'service' => $cs->service->name,
                    'client' => $cs->client,
                    'due_date' => $due->due_date,
                    'toggle_url' => route('clients.service-documents.toggle', [$cs->client_id, $cs->id, $req->id]),
                ]);
            }
        }

        return $out;
    }

    // ---------------------------------------------------------------------
    // Snooze (per user, 24h, cache-based)
    // ---------------------------------------------------------------------

    public function snooze(User $user, string $key): void
    {
        $map = $this->snoozeMap($user);
        $map[$key] = now()->addHours(self::SNOOZE_HOURS)->timestamp;
        Cache::put($this->snoozeCacheKey($user), $map, now()->addDays(2));
    }

    public function unsnooze(User $user, string $key): void
    {
        $map = $this->snoozeMap($user);
        unset($map[$key]);
        Cache::put($this->snoozeCacheKey($user), $map, now()->addDays(2));
    }

    private function snoozedKeys(User $user): array
    {
        return array_keys($this->snoozeMap($user));
    }

    private function snoozeMap(User $user): array
    {
        $map = Cache::get($this->snoozeCacheKey($user), []);

        return array_filter(is_array($map) ? $map : [], fn ($until) => $until > now()->timestamp);
    }

    private function snoozeCacheKey(User $user): string
    {
        return 'dashboard_snooze:'.$user->organization_id.':'.$user->id;
    }

    // ---------------------------------------------------------------------
    // Headline numbers, charts, calendar
    // ---------------------------------------------------------------------

    public function kpis(User $user, array $f, Collection $items): array
    {
        $clientIds = $this->clientScope($user, $f)->pluck('id');
        $weekEnd = $this->today->copy()->addDays(7);

        $dueWeek = ServiceDue::query()
            ->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_EXTENDED])
            ->whereBetween('due_date', [$this->today->toDateString(), $weekEnd->toDateString()])
            ->whereHas('clientService', fn ($q) => $q->whereIn('client_id', $clientIds))
            ->count();

        $overdue = $items->where('urgency', 'over');
        $kpis = [
            ['label' => 'Due in next 7 days', 'value' => (string) $dueWeek, 'sub' => 'filings across your clients', 'tone' => ''],
            ['label' => 'Overdue', 'value' => (string) $overdue->count(), 'sub' => $this->countSummary($overdue), 'tone' => $overdue->count() ? 'red' : ''],
        ];

        if ($this->canSeeMoney($user)) {
            $open = $this->openInvoices($clientIds);
            $receivable = $open->sum('balance');
            $over60 = $open->filter(fn ($i) => $i['age'] > 60)->sum('balance');
            $monthStart = $this->today->copy()->startOfMonth();
            $collected = (float) Payment::query()
                ->whereHas('invoice', fn ($q) => $q->whereIn('client_id', $clientIds))
                ->whereDate('payment_date', '>=', $monthStart)->sum('amount');
            $billed = (float) Invoice::query()
                ->whereIn('client_id', $clientIds)
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->whereDate('date', '>=', $monthStart)->sum('total_amount');

            $kpis[] = ['label' => 'Receivables', 'value' => $this->money($receivable), 'sub' => $this->money($over60).' over 60 days', 'tone' => ''];
            $kpis[] = ['label' => 'Collected this month', 'value' => $this->money($collected), 'sub' => 'of '.$this->money($billed).' billed', 'tone' => ''];
        } else {
            $myTasks = $items->where('type', 'task')->count();
            $docs = $items->where('kind', 'docs')->count();
            $kpis[] = ['label' => 'Open tasks', 'value' => (string) $myTasks, 'sub' => 'due in the next 7 days', 'tone' => ''];
            $kpis[] = ['label' => 'Documents awaited', 'value' => (string) $docs, 'sub' => 'for upcoming filings', 'tone' => ''];
        }

        return $kpis;
    }

    private function countSummary(Collection $items): string
    {
        if ($items->isEmpty()) {
            return 'Nothing overdue';
        }
        $labels = ['filing' => 'filings', 'payment' => 'invoices', 'dsc' => 'DSCs', 'docs' => 'documents', 'task' => 'tasks'];

        return $items->groupBy('kind')->map(fn ($g, $k) => $g->count().' '.($labels[$k] ?? $k))->implode(' · ');
    }

    public function canSeeMoney(User $user): bool
    {
        return ($user->isPartner() || $user->isManager()) && $user->canAccessModule('invoices');
    }

    /** @return Collection<int, array{id:int, client_id:int, client:string, balance:float, age:int}> */
    private function openInvoices(Collection $clientIds): Collection
    {
        return Invoice::query()
            ->with('client:id,name')
            ->withSum('payments', 'amount')
            ->whereIn('status', Invoice::OPEN_STATUSES)
            ->whereIn('client_id', $clientIds)
            ->get(['id', 'client_id', 'total_amount', 'date', 'due_date'])
            ->map(function ($inv) {
                $from = $inv->due_date ?? $inv->date ?? $this->today;

                return [
                    'id' => $inv->id,
                    'client_id' => $inv->client_id,
                    'client' => $inv->client->name ?? '—',
                    'balance' => max(0, (float) $inv->total_amount - (float) $inv->payments_sum_amount),
                    'age' => max(0, (int) $from->diffInDays($this->today, false)),
                ];
            })
            ->filter(fn ($i) => $i['balance'] > 0.5)
            ->values();
    }

    /** Billed vs collected, by month / quarter / financial year. */
    public function revenueSeries(User $user, array $f): array
    {
        if (! $this->canSeeMoney($user)) {
            return [];
        }

        $clientIds = $this->clientScope($user, $f)->pluck('id');
        $fyStart = fn (Carbon $d) => $d->month >= 4 ? Carbon::create($d->year, 4, 1) : Carbon::create($d->year - 1, 4, 1);

        $buckets = [];
        if ($f['period'] === 'fy') {
            $start = $fyStart($this->today)->subYears(4);
            for ($i = 0; $i < 5; $i++) {
                $s = $start->copy()->addYears($i);
                $buckets[] = ['label' => 'FY '.$s->format('y').'-'.$s->copy()->addYear()->format('y'), 'from' => $s, 'to' => $s->copy()->addYear()->subDay()];
            }
        } elseif ($f['period'] === 'q') {
            $fy = $fyStart($this->today);
            $qIndex = intdiv($this->today->month >= 4 ? $this->today->month - 4 : $this->today->month + 8, 3);
            $current = $fy->copy()->addMonths($qIndex * 3);
            for ($i = 5; $i >= 0; $i--) {
                $s = $current->copy()->subMonths($i * 3);
                $sFy = $fyStart($s);
                $qn = intdiv($s->month >= 4 ? $s->month - 4 : $s->month + 8, 3) + 1;
                $buckets[] = ['label' => 'Q'.$qn.' '.$sFy->format('y').'-'.$sFy->copy()->addYear()->format('y'), 'from' => $s, 'to' => $s->copy()->addMonths(3)->subDay()];
            }
        } else {
            for ($i = 5; $i >= 0; $i--) {
                $s = $this->today->copy()->startOfMonth()->subMonths($i);
                $buckets[] = ['label' => $s->format('M y'), 'from' => $s, 'to' => $s->copy()->endOfMonth()];
            }
        }

        $from = $buckets[0]['from']->toDateString();
        $invoices = Invoice::query()->whereIn('client_id', $clientIds)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->whereDate('date', '>=', $from)->toBase()->get(['date', 'total_amount']);
        $payments = Payment::query()->whereHas('invoice', fn ($q) => $q->whereIn('client_id', $clientIds))
            ->whereDate('payment_date', '>=', $from)->toBase()->get(['payment_date', 'amount']);

        return array_map(function ($b) use ($invoices, $payments) {
            $in = fn ($d) => $d && $d >= $b['from']->toDateString() && $d <= $b['to']->toDateString();
            $billed = $invoices->filter(fn ($r) => $in(substr((string) $r->date, 0, 10)))->sum('total_amount');
            $coll = $payments->filter(fn ($r) => $in(substr((string) $r->payment_date, 0, 10)))->sum('amount');

            return [
                'label' => $b['label'],
                'billed' => round((float) $billed, 2),
                'collected' => round((float) $coll, 2),
                'from' => $b['from']->toDateString(),
                'to' => $b['to']->toDateString(),
                'url' => route('invoices.index', ['from' => $b['from']->toDateString(), 'to' => $b['to']->toDateString()]),
            ];
        }, $buckets);
    }

    /** Outstanding money grouped by days overdue, with the biggest clients per group. */
    public function ageing(User $user, array $f): array
    {
        if (! $this->canSeeMoney($user)) {
            return [];
        }

        $open = $this->openInvoices($this->clientScope($user, $f)->pluck('id'));
        $defs = [['0–30 days', 0, 30], ['31–60 days', 31, 60], ['61–90 days', 61, 90], ['90+ days', 91, PHP_INT_MAX]];

        return array_map(function ($d) use ($open) {
            $rows = $open->filter(fn ($i) => $i['age'] >= $d[1] && $i['age'] <= $d[2]);
            $clients = $rows->groupBy('client_id')->map(fn ($g) => [
                'name' => $g->first()['client'],
                'amount' => $g->sum('balance'),
                'url' => route('clients.show', $g->first()['client_id']),
            ])->sortByDesc('amount')->take(5)->values()->all();

            return ['label' => $d[0], 'amount' => round($rows->sum('balance'), 2), 'clients' => $clients];
        }, $defs);
    }

    /** Filings due per day for a month, grouped by service. */
    public function calendar(User $user, array $f, ?string $month = null): array
    {
        $start = $month && preg_match('/^\d{4}-\d{2}$/', $month)
            ? Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay()
            : $this->today->copy()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $clientIds = $this->clientScope($user, $f)->pluck('id');

        $dues = ServiceDue::query()
            ->with('clientService.service:id,name')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('clientService', fn ($q) => $q->whereIn('client_id', $clientIds))
            ->get(['id', 'client_service_id', 'due_date', 'status']);

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $onDay = $dues->filter(fn ($x) => $x->due_date->isSameDay($d));
            $days[] = [
                'day' => $d->day,
                'date' => $d->toDateString(),
                'count' => $onDay->count(),
                'open' => $onDay->where('status', '!=', ServiceDue::STATUS_COMPLETED)->count(),
                'is_today' => $d->isSameDay($this->today),
                'items' => $onDay->groupBy(fn ($x) => $x->clientService->service->name ?? 'Other')
                    ->map(fn ($g, $name) => [
                        'name' => $name,
                        'total' => $g->count(),
                        'done' => $g->where('status', ServiceDue::STATUS_COMPLETED)->count(),
                    ])->values()->all(),
            ];
        }

        return [
            'label' => $start->format('F Y'),
            'month' => $start->format('Y-m'),
            'prev' => $start->copy()->subMonth()->format('Y-m'),
            'next' => $start->copy()->addMonth()->format('Y-m'),
            'lead_blanks' => $start->dayOfWeek, // 0 = Sunday
            'days' => $days,
            'url' => route('service-dues.index'),
        ];
    }

    /** Filed vs total this month, per service (top 6). */
    public function filingStatus(User $user, array $f): array
    {
        $clientIds = $this->clientScope($user, $f)->pluck('id');
        $start = $this->today->copy()->startOfMonth();

        return ServiceDue::query()
            ->with('clientService.service:id,name')
            ->whereBetween('due_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->whereHas('clientService', fn ($q) => $q->whereIn('client_id', $clientIds))
            ->get(['id', 'client_service_id', 'status'])
            ->groupBy(fn ($d) => $d->clientService->service->name ?? 'Other')
            ->map(fn ($g, $name) => [
                'name' => $name,
                'total' => $g->count(),
                'filed' => $g->where('status', ServiceDue::STATUS_COMPLETED)->count(),
            ])
            ->sortByDesc('total')->take(6)->values()->all();
    }

    // ---------------------------------------------------------------------
    // Team
    // ---------------------------------------------------------------------

    private function teamMembers(User $user): Collection
    {
        return User::query()
            ->inOrganization($user->organization_id)
            ->when($user->isManager() && $user->branch_id, fn ($q) => $q->where(fn ($q2) => $q2->whereNull('branch_id')->orWhere('branch_id', $user->branch_id)))
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'branch_id']);
    }

    /** Open tasks + open filings per person; busiest flagged relative to the team average. */
    public function workload(User $user, array $f): array
    {
        if (! ($user->isPartner() || $user->isManager())) {
            return [];
        }

        $members = $this->teamMembers($user);
        $ids = $members->pluck('id');
        $weekEnd = $this->today->copy()->addDays(7)->toDateString();

        $tasks = Task::query()->whereIn('assigned_to', $ids)
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->selectRaw('assigned_to, count(*) as c')->groupBy('assigned_to')->pluck('c', 'assigned_to');

        $dues = ServiceDue::query()
            ->join('client_services', 'client_services.id', '=', 'service_dues.client_service_id')
            ->join('clients', 'clients.id', '=', 'client_services.client_id')
            ->whereIn('clients.manager_id', $ids)
            ->whereIn('service_dues.status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE, ServiceDue::STATUS_EXTENDED])
            ->whereDate('service_dues.due_date', '<=', $weekEnd)
            ->selectRaw('clients.manager_id as m, count(*) as c')->groupBy('clients.manager_id')->pluck('c', 'm');

        $rows = $members->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'role' => ucfirst((string) $m->role),
            'tasks' => (int) ($tasks[$m->id] ?? 0),
            'dues' => (int) ($dues[$m->id] ?? 0),
        ])->map(fn ($r) => $r + ['total' => $r['tasks'] + $r['dues']]);

        $active = $rows->where('total', '>', 0);
        $avg = $active->count() ? $active->avg('total') : 0;
        $max = max(1, $rows->max('total') ?? 1);

        return $rows->map(fn ($r) => $r + [
            'pct' => (int) round($r['total'] / $max * 100),
            'flag' => $avg > 0 && $r['total'] > $avg * 1.5 ? 'Heavy' : ($r['total'] === 0 ? 'Free' : 'OK'),
        ])->sortByDesc('total')->values()->all();
    }

    /** Next 7 days: number of open filings + tasks due each day. */
    public function weekLoad(Collection $items): array
    {
        $out = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $this->today->copy()->addDays($i);
            $out[] = [
                'label' => $d->format('D j'),
                'count' => $items->where('date', $d->toDateString())->count(),
            ];
        }

        return $out;
    }

    // ---------------------------------------------------------------------
    // Clients
    // ---------------------------------------------------------------------

    /**
     * Quick attention score for many clients at once (bulk queries).
     * Lower = needs more attention.
     */
    public function clientList(User $user, array $f, string $sort = 'risk', ?string $search = null, int $limit = 60): Collection
    {
        $q = $this->clientScope($user, $f);
        if ($search) {
            $term = '%'.trim($search).'%';
            $q->where(fn ($w) => $w->where('name', 'like', $term)
                ->orWhere('pan', 'like', $term)
                ->orWhere('gstin', 'like', $term)
                ->orWhere('client_code', 'like', $term));
        }
        $clients = $q->get(['id', 'name', 'client_code', 'manager_id']);
        if ($clients->isEmpty()) {
            return collect();
        }
        $ids = $clients->pluck('id');

        $overdueDues = ServiceDue::query()
            ->join('client_services', 'client_services.id', '=', 'service_dues.client_service_id')
            ->whereIn('client_services.client_id', $ids)
            ->where('service_dues.status', '!=', ServiceDue::STATUS_COMPLETED)
            ->whereDate('service_dues.due_date', '<', $this->today)
            ->selectRaw('client_services.client_id as cid, count(*) as c')->groupBy('client_services.client_id')
            ->pluck('c', 'cid');

        $owed = collect();
        $overdueMoney = collect();
        if ($this->canSeeMoney($user)) {
            $open = $this->openInvoices($ids);
            $owed = $open->groupBy('client_id')->map->sum('balance');
            $overdueMoney = $open->filter(fn ($i) => $i['age'] > 0)->groupBy('client_id')->map->sum('balance');
        }

        $dscSoon = Dsc::query()->whereIn('client_id', $ids)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $this->today->copy()->addDays(30))
            ->pluck('client_id')->countBy();

        $rows = $clients->map(function ($c) use ($overdueDues, $owed, $overdueMoney, $dscSoon) {
            $od = (int) ($overdueDues[$c->id] ?? 0);
            $money = (float) ($overdueMoney[$c->id] ?? 0);
            $dsc = (int) ($dscSoon[$c->id] ?? 0);

            $score = 100 - min(45, $od * 15) - ($money > 0 ? min(35, 15 + (int) ($money / 25000) * 5) : 0) - ($dsc ? 10 : 0);
            $score = max(0, min(100, $score));

            $flags = [];
            if ($od) {
                $flags[] = $od.' overdue filing'.($od > 1 ? 's' : '');
            }
            if ($money > 0) {
                $flags[] = $this->money($money).' overdue';
            }
            if ($dsc) {
                $flags[] = 'DSC expiring';
            }

            return [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->client_code,
                'score' => $score,
                'flag' => $flags ? implode(' · ', $flags) : 'On track',
                'owed' => (float) ($owed[$c->id] ?? 0),
            ];
        });

        $rows = $sort === 'az' ? $rows->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) : $rows->sortBy([['score', 'asc'], ['name', 'asc']]);

        return $rows->take($limit)->values();
    }

    /** Everything about one client for the Client first layout. */
    public function clientDetail(User $user, Client $client): array
    {
        $client->loadMissing(['manager:id,name', 'branch:id,name', 'services.service:id,name']);

        $openDues = ServiceDue::query()
            ->with('clientService.service:id,name')
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $client->id))
            ->where(function ($q) {
                $q->where('status', '!=', ServiceDue::STATUS_COMPLETED)
                    ->orWhereDate('completed_at', '>=', $this->today->copy()->subDays(14));
            })
            ->whereDate('due_date', '<=', $this->today->copy()->addDays(45))
            ->orderBy('due_date')->limit(15)->get();

        $work = $openDues->map(fn ($d) => [
            'label' => ($d->clientService->service->name ?? 'Service').' · '.$d->due_date->format('M Y'),
            'due' => $d->status === ServiceDue::STATUS_COMPLETED
                ? 'Filed '.optional($d->completed_at)->format('d M')
                : ($d->due_date->lt($this->today) ? 'Overdue · '.$d->due_date->format('d M') : 'Due '.$d->due_date->format('d M')),
            'state' => $d->status === ServiceDue::STATUS_COMPLETED ? 'done' : ($d->due_date->lt($this->today) ? 'over' : ($d->due_date->lte($this->today->copy()->addDays(3)) ? 'soon' : 'ok')),
            'complete_url' => $d->status !== ServiceDue::STATUS_COMPLETED && $user->canAccessModule('service_dues') ? route('service-dues.complete', $d) : null,
            'remind_url' => $d->status !== ServiceDue::STATUS_COMPLETED && ($user->isPartner() || $user->isManager()) ? route('service-dues.whatsapp', $d) : null,
        ]);

        $tasks = $client->tasks()->with('assignee:id,name')
            ->whereNotIn('status', Task::TERMINAL_STATUSES)->orderBy('due_date')->limit(10)->get();
        foreach ($tasks as $t) {
            $work->push([
                'label' => $t->title.($t->assignee ? ' – '.$t->assignee->name : ''),
                'due' => $t->due_date ? 'Task due '.$t->due_date->format('d M') : 'Task',
                'state' => $t->due_date && $t->due_date->lt($this->today) ? 'over' : 'ok',
                'complete_url' => null,
                'task_url' => route('tasks.update-status', $t),
                'remind_url' => null,
            ]);
        }

        $money = null;
        $invoices = collect();
        if ($this->canSeeMoney($user)) {
            $open = $this->openInvoices(collect([$client->id]));
            $money = [
                'owed' => $open->sum('balance'),
                'count' => $open->count(),
                'oldest' => $open->max('age') ?? 0,
            ];
            $invoices = $client->invoices()->withSum('payments', 'amount')->latest('date')->limit(5)->get()
                ->map(fn ($i) => [
                    'number' => $i->invoice_number ?: '#'.$i->id,
                    'date' => optional($i->date)->format('d M Y'),
                    'total' => $this->money((float) $i->total_amount),
                    'balance' => $this->money(max(0, (float) $i->total_amount - (float) $i->payments_sum_amount)),
                    'status' => $i->status,
                    'url' => route('invoices.show', $i),
                    'remind_url' => in_array($i->status, Invoice::OPEN_STATUSES, true) ? route('invoices.whatsapp', $i) : null,
                ]);
        }

        $docsPending = $this->missingDocuments(collect([$client->id]), 45);

        $dsc = Dsc::query()->where('client_id', $client->id)->orderBy('expiry_date')->first();
        $nextDue = $openDues->first(fn ($d) => $d->status !== ServiceDue::STATUS_COMPLETED);

        $health = app(ClientHealthScoreService::class)->forClient($client);

        $activity = collect();
        ServiceDue::query()->with('clientService.service:id,name')
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $client->id))
            ->where('status', ServiceDue::STATUS_COMPLETED)->whereNotNull('completed_at')
            ->latest('completed_at')->limit(5)->get()
            ->each(fn ($d) => $activity->push(['text' => ($d->clientService->service->name ?? 'Service').' ('.$d->due_date->format('M Y').') filed', 'at' => $d->completed_at]));
        if ($this->canSeeMoney($user)) {
            Payment::query()->whereHas('invoice', fn ($q) => $q->where('client_id', $client->id))
                ->latest('payment_date')->limit(5)->get()
                ->each(fn ($p) => $activity->push(['text' => 'Payment '.$this->money((float) $p->amount).' received', 'at' => $p->payment_date]));
            $client->invoices()->latest('date')->limit(3)->get()
                ->each(fn ($i) => $activity->push(['text' => 'Invoice '.($i->invoice_number ?: '#'.$i->id).' raised · '.$this->money((float) $i->total_amount), 'at' => $i->date]));
        }

        return [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'code' => $client->client_code,
                'pan' => $client->pan,
                'gstin' => $client->gstin ?? null,
                'mobile' => $client->mobile_number ?? null,
                'manager' => $client->manager?->name,
                'branch' => $client->branch?->name,
                'services' => $client->services->map(fn ($s) => $s->service->name ?? null)->filter()->unique()->values()->all(),
                'url' => route('clients.show', $client),
                'auto_reminders' => (bool) ($client->auto_reminders ?? true),
            ],
            'health' => $health,
            'tiles' => array_values(array_filter([
                $money !== null ? [
                    'label' => 'Unpaid',
                    'value' => $this->money($money['owed']),
                    'sub' => $money['count'] ? $money['count'].' invoice(s), oldest '.$money['oldest'].' days' : 'Nothing outstanding',
                    'tone' => $money['oldest'] > 60 ? 'red' : ($money['owed'] > 0 ? '' : 'green'),
                ] : null,
                [
                    'label' => 'Next due',
                    'value' => $nextDue ? ($nextDue->clientService->service->name ?? 'Filing') : 'None',
                    'sub' => $nextDue ? ($nextDue->due_date->lt($this->today) ? 'Overdue since '.$nextDue->due_date->format('d M') : $nextDue->due_date->format('d M Y')) : 'Nothing in the next 45 days',
                    'tone' => $nextDue && $nextDue->due_date->lt($this->today) ? 'red' : '',
                ],
                [
                    'label' => 'Documents awaited',
                    'value' => (string) $docsPending->count(),
                    'sub' => $docsPending->isEmpty() ? 'All received' : $docsPending->pluck('name')->take(2)->implode(', '),
                    'tone' => $docsPending->isEmpty() ? 'green' : '',
                ],
                [
                    'label' => 'DSC',
                    'value' => $dsc ? ($dsc->expiry_date && $dsc->expiry_date->lt($this->today) ? 'Expired' : ($dsc->expiry_date && $dsc->expiry_date->lte($this->today->copy()->addDays(30)) ? $this->today->diffInDays($dsc->expiry_date).' days left' : 'Valid')) : 'None',
                    'sub' => $dsc ? (($dsc->holder_name ?: 'Holder').' · '.optional($dsc->expiry_date)->format('d M Y')) : 'Not on file',
                    'tone' => $dsc && $dsc->expiry_date && $dsc->expiry_date->lte($this->today->copy()->addDays(30)) ? 'red' : ($dsc ? 'green' : ''),
                ],
            ])),
            'work' => $work->values()->all(),
            'documents' => $docsPending->map(fn ($d) => ['name' => $d['name'], 'service' => $d['service'], 'toggle_url' => $d['toggle_url']])->values()->all(),
            'invoices' => $invoices->values()->all(),
            'activity' => $activity->filter(fn ($a) => $a['at'])->sortByDesc(fn ($a) => $a['at'])->take(8)
                ->map(fn ($a) => ['text' => $a['text'], 'at' => Carbon::parse($a['at'])->format('d M Y')])->values()->all(),
        ];
    }

    public function money(float $amount): string
    {
        if ($amount >= 10000000) {
            return '₹'.number_format($amount / 10000000, 2).' Cr';
        }
        if ($amount >= 100000) {
            return '₹'.number_format($amount / 100000, 2).' L';
        }

        return '₹'.$this->indianFormat($amount);
    }

    private function indianFormat(float $amount): string
    {
        $n = (string) (int) round($amount);
        if (strlen($n) <= 3) {
            return $n;
        }
        $last3 = substr($n, -3);
        $rest = substr($n, 0, -3);

        return preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).','.$last3;
    }
}
