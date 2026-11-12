<?php

namespace App\Services;

use App\Models\AutomationLog;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\ServiceDue;
use App\Models\User;
use App\Support\AutomationSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Builds and sends the Phase 3 automatic messages. Every send is recorded in
 * automation_logs with a dedupe key, so the same message never goes out twice.
 *
 * The caller must set OrganizationContext to the organisation being processed.
 */
class AutomationService
{
    public function __construct(
        private WhatsAppService $whatsApp,
        private DashboardLayoutDataService $data,
    ) {
    }

    private function today(): Carbon
    {
        return Carbon::now(AutomationSettings::TIMEZONE)->startOfDay();
    }

    // ------------------------------------------------------------------
    // 3.1 Morning brief to staff
    // ------------------------------------------------------------------

    /** @return array{text: string, count: int} */
    public function morningBriefFor(User $user): array
    {
        $filters = ['scope' => 'mine', 'staff' => $user->id, 'branch' => null, 'period' => '6m', 'can_see_firm' => false];
        $today = $this->today()->toDateString();

        $items = $this->data->actionItems($user, $filters, 0)
            ->filter(fn ($i) => $i['urgency'] === 'over' || $i['date'] === $today);

        $overdue = $items->where('urgency', 'over')->values();
        $dueToday = $items->where('urgency', '!=', 'over')->values();

        $lines = ['Good morning, '.Str::before($user->name, ' ').'. Your day at '.AutomationSettings::firmName().':'];
        if ($items->isEmpty()) {
            $lines[] = '';
            $lines[] = 'Nothing overdue and nothing due today. Have a good day!';
        }
        foreach ([['Overdue', $overdue], ['Due today', $dueToday]] as [$label, $group]) {
            if ($group->isEmpty()) {
                continue;
            }
            $lines[] = '';
            $lines[] = "*{$label} ({$group->count()})*";
            foreach ($group->take(8) as $i) {
                $lines[] = '• '.$i['title'].($i['client_name'] !== '—' ? ' – '.$i['client_name'] : '');
            }
            if ($group->count() > 8) {
                $lines[] = '…and '.($group->count() - 8).' more';
            }
        }
        $lines[] = '';
        $lines[] = 'Open your dashboard: '.route('dashboard');

        return ['text' => implode("\n", $lines), 'count' => $items->count()];
    }

    public function sendMorningBriefs(Organization $org, bool $dryRun = false): array
    {
        $stats = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        $date = $this->today()->toDateString();

        $users = User::query()->inOrganization($org->id)
            ->whereNotNull('mobile')->where('mobile', '!=', '')
            ->get();

        foreach ($users as $user) {
            $brief = $this->morningBriefFor($user);
            if ($brief['count'] === 0) {
                $stats['skipped']++;
                continue; // don't wake people up with an empty message
            }
            $result = $this->deliver($org, 'morning_brief', "brief:{$user->id}:{$date}", 'whatsapp', $user->mobile, $user->name, $brief['text'], $dryRun);
            $stats[$result]++;
        }

        return $stats;
    }

    // ------------------------------------------------------------------
    // 3.2 Payment reminders to clients
    // ------------------------------------------------------------------

    /** Invoices that reach a reminder stage today: [invoice, stage days, balance]. */
    public function paymentRemindersDue(): Collection
    {
        $stages = AutomationSettings::reminderDays();
        $today = $this->today();

        return Invoice::query()
            ->with('client')
            ->withSum('payments', 'amount')
            ->whereIn('status', Invoice::OPEN_STATUSES)
            ->whereHas('client', fn ($q) => $q->where('auto_reminders', true)->where('status', '!=', Client::STATUS_CLOSED))
            ->get()
            ->map(function (Invoice $inv) use ($stages, $today) {
                $dueOn = $inv->due_date ?? $inv->date;
                if (! $dueOn) {
                    return null;
                }
                $days = (int) Carbon::parse($dueOn)->startOfDay()->diffInDays($today, false);
                $balance = (float) $inv->total_amount - (float) $inv->payments_sum_amount;
                if ($balance <= 0.5 || $days < $stages[0]) {
                    return null;
                }
                // Highest stage reached; the dedupe key stops repeats within a stage.
                $stage = collect($stages)->filter(fn ($s) => $days >= $s)->last();

                return ['invoice' => $inv, 'stage' => $stage, 'days' => $days, 'balance' => $balance];
            })
            ->filter()
            ->values();
    }

    public function paymentReminderText(Invoice $inv, float $balance, int $days): string
    {
        $firm = AutomationSettings::firmName();
        $lines = [
            "Dear {$inv->client->name},",
            '',
            "This is a reminder from {$firm} that invoice ".($inv->invoice_number ?: '#'.$inv->id)
                .' dated '.optional($inv->date)->format('d M Y').' has '.$this->data->money($balance)
                ." outstanding, {$days} days past the due date.",
        ];
        if ($inv->payment_url) {
            $lines[] = '';
            $lines[] = 'You can pay here: '.$inv->payment_url;
        }
        $lines[] = '';
        $lines[] = 'If you have already paid, please ignore this message. Thank you.';
        $lines[] = '– '.$firm;

        return implode("\n", $lines);
    }

    public function sendPaymentReminders(Organization $org, bool $dryRun = false): array
    {
        $stats = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($this->paymentRemindersDue() as $r) {
            $inv = $r['invoice'];
            $text = $this->paymentReminderText($inv, $r['balance'], $r['days']);
            $key = "invoice:{$inv->id}:stage:{$r['stage']}";
            $label = 'Invoice '.($inv->invoice_number ?: '#'.$inv->id).' · '.$inv->client->name;
            $sentAny = false;

            if ($mobile = $this->clientMobile($inv->client)) {
                $stats[$this->deliver($org, 'payment_reminder', $key, 'whatsapp', $mobile, $label, $text, $dryRun)]++;
                $sentAny = true;
            }
            if ($email = $this->clientEmail($inv->client, true)) {
                $stats[$this->deliver($org, 'payment_reminder', $key, 'email', $email, $label, $text, $dryRun, 'Payment reminder – '.($inv->invoice_number ?: 'invoice'))]++;
                $sentAny = true;
            }
            if (! $sentAny) {
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    // ------------------------------------------------------------------
    // 3.3 Document requests to clients
    // ------------------------------------------------------------------

    /** Per client: filings due in exactly N days with documents still missing. */
    public function documentRequestsDue(): Collection
    {
        $target = $this->today()->addDays(AutomationSettings::int('auto_document_request_days'))->toDateString();

        $services = ClientService::query()
            ->with([
                'client', 'service:id,name', 'service.documentRequirements:id,service_id,name,sort_order', 'documentChecks',
                'dues' => fn ($q) => $q->where('status', '!=', ServiceDue::STATUS_COMPLETED)->whereDate('due_date', $target),
            ])
            ->whereHas('client', fn ($q) => $q->where('auto_reminders', true)->where('status', '!=', Client::STATUS_CLOSED))
            ->whereHas('dues', fn ($q) => $q->where('status', '!=', ServiceDue::STATUS_COMPLETED)->whereDate('due_date', $target))
            ->whereHas('service.documentRequirements')
            ->get();

        return $services->map(function (ClientService $cs) {
            $received = $cs->documentChecks->where('is_received', true)->pluck('service_document_requirement_id')->all();
            $missing = $cs->service->documentRequirements->reject(fn ($r) => in_array($r->id, $received, true))->pluck('name');
            $due = $cs->dues->first();

            return $missing->isEmpty() || ! $due ? null : [
                'client' => $cs->client, 'service' => $cs->service->name, 'due' => $due, 'missing' => $missing->values()->all(),
            ];
        })->filter()->values();
    }

    public function documentRequestText(Client $client, string $service, Carbon $dueDate, array $missing): string
    {
        $firm = AutomationSettings::firmName();
        $lines = [
            "Dear {$client->name},",
            '',
            "For your {$service} due on ".$dueDate->format('d M Y').', please share the following with us:',
        ];
        foreach ($missing as $m) {
            $lines[] = '• '.$m;
        }
        $lines[] = '';
        $lines[] = 'Sending them early helps us file on time. Thank you.';
        $lines[] = '– '.$firm;

        return implode("\n", $lines);
    }

    public function sendDocumentRequests(Organization $org, bool $dryRun = false): array
    {
        $stats = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($this->documentRequestsDue() as $r) {
            $client = $r['client'];
            $text = $this->documentRequestText($client, $r['service'], $r['due']->due_date, $r['missing']);
            $key = "docs:due:{$r['due']->id}";
            $label = $r['service'].' · '.$client->name;
            $sentAny = false;

            if ($mobile = $this->clientMobile($client)) {
                $stats[$this->deliver($org, 'document_request', $key, 'whatsapp', $mobile, $label, $text, $dryRun)]++;
                $sentAny = true;
            }
            if ($email = $this->clientEmail($client)) {
                $stats[$this->deliver($org, 'document_request', $key, 'email', $email, $label, $text, $dryRun, 'Documents needed for '.$r['service'])]++;
                $sentAny = true;
            }
            if (! $sentAny) {
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    // ------------------------------------------------------------------
    // 3.4 Weekly summary to partners
    // ------------------------------------------------------------------

    public function weeklySummary(Organization $org): array
    {
        $end = $this->today();
        $start = $end->copy()->subDays(7);

        $filed = ServiceDue::query()->where('status', ServiceDue::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$start->toDateString(), $end->toDateString()])->count();
        $filedLate = ServiceDue::query()->where('status', ServiceDue::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$start->toDateString(), $end->toDateString()])
            ->whereColumn('completed_at', '>', 'due_date')->count();
        $overdueFilings = ServiceDue::query()->where('status', '!=', ServiceDue::STATUS_COMPLETED)
            ->whereDate('due_date', '<', $end)->count();
        $dueNextWeek = ServiceDue::query()->where('status', '!=', ServiceDue::STATUS_COMPLETED)
            ->whereBetween('due_date', [$end->toDateString(), $end->copy()->addDays(7)->toDateString()])->count();
        $collected = (float) Payment::query()->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])->sum('amount');
        $billed = (float) Invoice::query()->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])->sum('total_amount');

        $open = Invoice::query()->with('client:id,name')->withSum('payments', 'amount')
            ->whereIn('status', Invoice::OPEN_STATUSES)->get()
            ->map(fn ($i) => ['client' => $i->client->name ?? '—', 'balance' => max(0, (float) $i->total_amount - (float) $i->payments_sum_amount),
                'overdue' => ($i->due_date ?? $i->date) && Carbon::parse($i->due_date ?? $i->date)->lt($end)]);
        $receivable = $open->sum('balance');
        $topOverdue = $open->where('overdue', true)->groupBy('client')->map->sum('balance')->sortDesc()->take(5);

        $partner = User::query()->inOrganization($org->id)->where('role', 'partner')->first();
        $team = $partner ? collect($this->data->workload($partner, ['scope' => 'firm', 'staff' => null, 'branch' => null, 'period' => '6m', 'can_see_firm' => true])) : collect();

        $lines = [
            AutomationSettings::firmName().' – week to '.$end->format('d M Y'),
            '',
            '*Filings*',
            "• Filed this week: {$filed}".($filedLate ? " ({$filedLate} after the due date)" : ''),
            "• Overdue now: {$overdueFilings}",
            "• Due in the next 7 days: {$dueNextWeek}",
            '',
            '*Money*',
            '• Billed this week: '.$this->data->money($billed),
            '• Collected this week: '.$this->data->money($collected),
            '• Outstanding: '.$this->data->money($receivable),
        ];
        if ($topOverdue->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '*Largest overdue balances*';
            foreach ($topOverdue as $client => $amt) {
                $lines[] = "• {$client}: ".$this->data->money($amt);
            }
        }
        $heavy = $team->where('flag', 'Heavy');
        if ($heavy->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '*Team*';
            foreach ($heavy as $m) {
                $lines[] = "• {$m['name']} is carrying a heavy load ({$m['tasks']} tasks, {$m['dues']} filings this week)";
            }
        }
        $lines[] = '';
        $lines[] = 'Dashboard: '.route('dashboard');

        return ['text' => implode("\n", $lines), 'subject' => AutomationSettings::firmName().' – weekly summary, '.$end->format('d M Y')];
    }

    public function sendWeeklySummaries(Organization $org, bool $dryRun = false): array
    {
        $stats = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        $summary = $this->weeklySummary($org);
        $week = $this->today()->format('o-\WW');

        $partners = User::query()->inOrganization($org->id)->where('role', 'partner')->get();
        foreach ($partners as $p) {
            if ($p->email) {
                $stats[$this->deliver($org, 'weekly_summary', "weekly:{$week}:{$p->id}", 'email', $p->email, $p->name, $summary['text'], $dryRun, $summary['subject'])]++;
            } else {
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    // ------------------------------------------------------------------
    // Delivery
    // ------------------------------------------------------------------

    /** @return 'sent'|'failed'|'skipped' */
    public function deliver(Organization $org, string $type, string $key, string $channel, string $to, ?string $label, string $text, bool $dryRun = false, ?string $subject = null): string
    {
        $already = AutomationLog::query()
            ->where('organization_id', $org->id)->where('dedupe_key', $key)->where('channel', $channel)
            ->where('status', AutomationLog::STATUS_SENT)->exists();
        if ($already) {
            return 'skipped';
        }
        if ($dryRun) {
            return 'skipped';
        }

        $ok = false;
        $detail = null;
        try {
            if ($channel === 'whatsapp') {
                $res = $this->whatsApp->sendMessage($to, $text);
                if (! empty($res['skipped'])) {
                    return 'skipped'; // limited to the firm's "WhatsApp only to" numbers; nothing is logged
                }
                $ok = (bool) ($res['success'] ?? false);
                $detail = $res['message'] ?? null;
            } else {
                Mail::raw($text, fn ($m) => $m->to($to)->subject($subject ?? AutomationSettings::firmName()));
                $ok = true;
            }
        } catch (\Throwable $e) {
            $detail = Str::limit($e->getMessage(), 500);
        }

        AutomationLog::query()->updateOrCreate(
            ['organization_id' => $org->id, 'dedupe_key' => $key, 'channel' => $channel],
            [
                'type' => $type,
                'recipient' => $to,
                'subject_label' => $label ? Str::limit($label, 185) : null,
                'status' => $ok ? AutomationLog::STATUS_SENT : AutomationLog::STATUS_FAILED,
                'detail' => $ok ? null : $detail,
            ]
        );

        return $ok ? 'sent' : 'failed';
    }

    private function clientMobile(Client $client): ?string
    {
        $raw = preg_replace('/\D+/', '', (string) ($client->primary_contact_phone ?: ($client->mobile_number ?? '')));
        if ($raw === '') {
            return null;
        }
        if (strlen($raw) === 10) {
            $raw = '91'.$raw;
        }

        return strlen($raw) >= 11 ? $raw : null;
    }

    private function clientEmail(Client $client, bool $billing = false): ?string
    {
        $candidates = $billing
            ? [$client->invoice_email ?? null, $client->primary_contact_email ?? null, $client->email ?? null]
            : [$client->primary_contact_email ?? null, $client->email ?? null, $client->invoice_email ?? null];

        foreach ($candidates as $e) {
            if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                return $e;
            }
        }

        return null;
    }
}
