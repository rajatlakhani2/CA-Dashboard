<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Organization;
use App\Models\PersonalRenewal;
use App\Models\ServiceDue;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsAppMessageLog;
use App\Services\WhatsAppService;
use App\Support\OrganizationContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * One WhatsApp message per person per day:
 *  - Partners ("me"): firm summary by priority + service dues + own LIC/insurance.
 *  - Staff: their own open tasks, High priority first, overdue flagged + own policies.
 *  - Clients (only if they agreed): their compliance dues in the next few days.
 */
class SendDailyWhatsAppReminders extends Command
{
    protected $signature = 'whatsapp:daily-reminders
        {--dry-run : Print messages instead of sending}
        {--only= : partners, staff or clients}';

    protected $description = 'Send the everyday WhatsApp reminder to partners, staff and opted-in clients';

    private int $sent = 0;

    private int $failed = 0;

    public function handle(WhatsAppService $whatsApp): int
    {
        $organizations = Organization::query()->get();

        if ($organizations->isEmpty()) {
            $this->runForOrganization(null, $whatsApp);
        } else {
            foreach ($organizations as $organization) {
                if (isset($organization->is_active) && ! $organization->is_active) {
                    continue;
                }
                OrganizationContext::set($organization->id);
                try {
                    $this->runForOrganization($organization->id, $whatsApp);
                } finally {
                    OrganizationContext::clear();
                }
            }
        }

        $this->info("Daily WhatsApp reminders: {$this->sent} sent, {$this->failed} failed.");

        return self::SUCCESS;
    }

    private function runForOrganization(?int $organizationId, WhatsAppService $whatsApp): void
    {
        if (Setting::get('whatsapp_daily_enabled', '1') !== '1') {
            return;
        }

        $daysAhead = max(1, (int) Setting::get('reminder_days_ahead', '7'));
        $clientDaysAhead = max(1, (int) Setting::get('client_reminder_days_ahead', '3'));
        $today = Carbon::today();
        $until = $today->copy()->addDays($daysAhead)->endOfDay();
        $only = $this->option('only');

        $users = User::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->get();

        $openTasks = Task::query()
            ->with('client')
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $until)
            ->orderBy('due_date')
            ->get();

        foreach ($users as $user) {
            $isPartner = $user->isPartner();
            if ($only === 'partners' && ! $isPartner) {
                continue;
            }
            if ($only === 'staff' && $isPartner) {
                continue;
            }
            if ($only === 'clients') {
                break;
            }

            $mine = $openTasks->where('assigned_to', $user->id)->values();
            $policies = $this->policiesFor($user);

            $lines = [];
            $summary = [];

            if ($isPartner) {
                $this->appendFirmSummary($lines, $summary, $openTasks, $today, $until);
            }

            if ($mine->isNotEmpty()) {
                $lines[] = $isPartner ? "\n*Your own tasks*" : '*Your tasks*';
                foreach (Task::PRIORITIES as $priority) {
                    $group = $mine->filter(fn (Task $t) => $this->priorityOf($t) === $priority);
                    if ($group->isEmpty()) {
                        continue;
                    }
                    $lines[] = $this->priorityIcon($priority)." {$priority} ({$group->count()})";
                    foreach ($group->take(8) as $task) {
                        $lines[] = '  • '.$this->taskLine($task, $today);
                    }
                    if ($group->count() > 8) {
                        $lines[] = '  …and '.($group->count() - 8).' more';
                    }
                }
                $overdue = $mine->filter(fn (Task $t) => $t->due_date->lt($today))->count();
                $summary[] = $mine->count().' task(s)'.($overdue ? " ({$overdue} overdue)" : '');
            }

            if ($policies->isNotEmpty()) {
                $lines[] = "\n*LIC & insurance*";
                foreach ($policies as $policy) {
                    $lines[] = '  • '.$policy->reminderLine();
                }
                $summary[] = $policies->count().' premium(s) due';
            }

            if ($lines === []) {
                continue;
            }

            $text = "🌅 *Good morning {$user->name}*\n".implode("\n", $lines)
                ."\n\nOpen: ".url($isPartner ? '/tasks/priority' : '/my-day');

            $this->deliver($whatsApp, $user->mobile, $user->name, $text, implode(', ', $summary).'. Open the dashboard for details.', null);
        }

        if ($only === null || $only === 'clients') {
            $this->sendClientReminders($whatsApp, $today, $clientDaysAhead);
        }
    }

    /** Firm-wide part of the partner message. */
    private function appendFirmSummary(array &$lines, array &$summary, Collection $openTasks, Carbon $today, Carbon $until): void
    {
        $overdue = $openTasks->filter(fn (Task $t) => $t->due_date->lt($today));
        $dueToday = $openTasks->filter(fn (Task $t) => $t->due_date->isSameDay($today));

        $lines[] = '*Firm summary*';
        foreach (Task::PRIORITIES as $priority) {
            $all = $openTasks->filter(fn (Task $t) => $this->priorityOf($t) === $priority);
            if ($all->isEmpty()) {
                continue;
            }
            $late = $overdue->filter(fn (Task $t) => $this->priorityOf($t) === $priority)->count();
            $lines[] = $this->priorityIcon($priority)." {$priority}: {$all->count()} open".($late ? ", {$late} overdue" : '');
        }

        $highLate = $overdue->filter(fn (Task $t) => $this->priorityOf($t) === Task::PRIORITY_HIGH)->take(5);
        if ($highLate->isNotEmpty()) {
            $lines[] = 'Overdue high priority:';
            foreach ($highLate as $task) {
                $lines[] = '  • '.$this->taskLine($task, $today);
            }
        }

        $dues = ServiceDue::query()
            ->with(['clientService.client', 'clientService.service'])
            ->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
            ->whereDate('due_date', '<=', $until)
            ->orderBy('due_date')
            ->get();

        if ($dues->isNotEmpty()) {
            $lines[] = "\n*Compliance dues ({$dues->count()})*";
            foreach ($dues->take(10) as $due) {
                $client = $due->clientService?->client?->name ?? 'Client';
                $service = $due->clientService?->service?->name ?? 'Service';
                $lines[] = "  • {$client}: {$service} – ".$due->due_date->format('d M');
            }
            if ($dues->count() > 10) {
                $lines[] = '  …and '.($dues->count() - 10).' more';
            }
        }

        $summary[] = "Firm: {$openTasks->count()} open task(s), {$overdue->count()} overdue, {$dueToday->count()} due today, {$dues->count()} compliance due(s)";
    }

    /** User's own LIC/insurance/loan items inside their reminder window. */
    private function policiesFor(User $user): Collection
    {
        return PersonalRenewal::query()
            ->where('user_id', $user->id)
            ->where('status', PersonalRenewal::STATUS_PENDING)
            ->where(fn ($q) => $q->where('whatsapp_reminder', true)->orWhereNull('whatsapp_reminder'))
            ->whereDate('due_date', '<=', Carbon::today()->addDays(30))
            ->orderBy('due_date')
            ->get()
            ->filter(fn (PersonalRenewal $p) => $p->daysLeft() <= (int) ($p->remind_days_before ?: 7))
            ->values();
    }

    private function sendClientReminders(WhatsAppService $whatsApp, Carbon $today, int $daysAhead): void
    {
        if (Setting::get('whatsapp_client_daily_enabled', '1') !== '1') {
            return;
        }

        $dues = ServiceDue::query()
            ->with(['clientService.client', 'clientService.service'])
            ->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
            ->whereDate('due_date', '<=', $today->copy()->addDays($daysAhead))
            ->orderBy('due_date')
            ->get()
            ->filter(fn (ServiceDue $d) => $d->clientService?->client?->whatsapp_opt_in)
            ->groupBy(fn (ServiceDue $d) => $d->clientService->client_id);

        $firm = config('whatsapp.firm_reply_name', config('app.name'));

        foreach ($dues as $clientDues) {
            /** @var Client $client */
            $client = $clientDues->first()->clientService->client;
            if (! $client->mobile_number) {
                continue;
            }

            $lines = [];
            $short = [];
            foreach ($clientDues as $due) {
                $service = $due->clientService?->service?->name ?? 'Compliance';
                $when = $due->due_date->lt($today) ? 'overdue since '.$due->due_date->format('d M') : 'due '.$due->due_date->format('d M');
                $lines[] = "  • {$service} – {$when}";
                $short[] = "{$service} {$when}";
            }

            $text = "Dear {$client->name},\nGentle reminder from {$firm}:\n".implode("\n", $lines)
                ."\n\nPlease share the documents / confirm so we can file on time. Reply STOP to stop these reminders.";

            $this->deliver(
                $whatsApp,
                $client->mobile_number,
                $client->name,
                $text,
                'reminder from '.$firm.': '.implode('; ', $short).'. Please share documents so we can file on time.',
                $client->id
            );
        }
    }

    private function deliver(WhatsAppService $whatsApp, string $mobile, string $name, string $text, string $oneLine, ?int $clientId): void
    {
        $number = WhatsAppService::normalizeMobile($mobile);
        if (! $number) {
            return;
        }

        // Never send the same person two daily reminders on one day.
        $already = WhatsAppMessageLog::query()
            ->where('phone', $number)
            ->where('intent', 'daily_reminder')
            ->whereDate('created_at', Carbon::today())
            ->exists();
        if ($already) {
            return;
        }

        if ($this->option('dry-run')) {
            $this->line("---- {$name} ({$number})\n{$text}\n");

            return;
        }

        $result = $whatsApp->sendDailyReminder($number, $name, $text, $oneLine);

        WhatsAppMessageLog::create([
            'client_id' => $clientId,
            'phone' => $number,
            'direction' => WhatsAppMessageLog::DIRECTION_OUT,
            'body' => $text,
            'intent' => 'daily_reminder',
            'meta' => ['success' => (bool) ($result['success'] ?? false), 'message' => $result['message'] ?? null],
        ]);

        ($result['success'] ?? false) ? $this->sent++ : $this->failed++;
        if (! ($result['success'] ?? false)) {
            $this->warn("Failed for {$name}: ".($result['message'] ?? 'unknown error'));
        }
    }

    private function taskLine(Task $task, Carbon $today): string
    {
        $client = $task->client?->name ?? 'Internal';
        $days = (int) $today->diffInDays($task->due_date->copy()->startOfDay(), false);
        $when = match (true) {
            $days < 0 => '⚠️ '.abs($days).'d overdue',
            $days === 0 => 'today',
            $days === 1 => 'tomorrow',
            default => $task->due_date->format('d M'),
        };

        return "{$task->title} – {$client} ({$when})";
    }

    private function priorityOf(Task $task): string
    {
        return in_array($task->priority, Task::PRIORITIES, true) ? $task->priority : Task::PRIORITY_MEDIUM;
    }

    private function priorityIcon(string $priority): string
    {
        return match ($priority) {
            Task::PRIORITY_HIGH => '🔴',
            Task::PRIORITY_MEDIUM => '🟠',
            default => '⚪',
        };
    }
}
