<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\AutomationService;
use App\Support\AutomationSettings;
use App\Support\DemoWorkspace;
use App\Support\OrganizationContext;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Runs every hour from the scheduler. For each firm it sends whichever automations
 * that firm has switched on and whose hour has come (India time). Each message is
 * logged with a dedupe key, so running more often never sends duplicates.
 */
class RunAutomations extends Command
{
    protected $signature = 'automation:run
        {type? : morning_brief, payment_reminders, document_requests or weekly_summary (default: all that are due now)}
        {--org= : Only this organisation id}
        {--force : Ignore the configured hour/day}
        {--dry-run : Work out what would be sent without sending}';

    protected $description = 'Send automatic staff briefs, client reminders and partner summaries that are switched on';

    public function handle(AutomationService $automation): int
    {
        $now = Carbon::now(AutomationSettings::TIMEZONE);
        $only = $this->argument('type');
        $force = (bool) $this->option('force');
        $dry = (bool) $this->option('dry-run');

        $orgs = Organization::query()
            ->where('is_active', true)
            ->when($this->option('org'), fn ($q, $id) => $q->whereKey($id))
            ->get()
            ->reject(fn ($o) => DemoWorkspace::isDemoOrganization($o));

        foreach ($orgs as $org) {
            OrganizationContext::set($org->id);
            try {
                $jobs = [
                    'morning_brief' => [
                        AutomationSettings::enabled('auto_morning_brief')
                            && ($force || $now->hour === AutomationSettings::int('auto_morning_brief_hour')),
                        fn () => $automation->sendMorningBriefs($org, $dry),
                    ],
                    'payment_reminders' => [
                        AutomationSettings::enabled('auto_payment_reminders')
                            && ($force || $now->hour === AutomationSettings::int('auto_client_hour')),
                        fn () => $automation->sendPaymentReminders($org, $dry),
                    ],
                    'document_requests' => [
                        AutomationSettings::enabled('auto_document_requests')
                            && ($force || $now->hour === AutomationSettings::int('auto_client_hour')),
                        fn () => $automation->sendDocumentRequests($org, $dry),
                    ],
                    'weekly_summary' => [
                        AutomationSettings::enabled('auto_weekly_summary')
                            && ($force || ($now->isMonday() && $now->hour === AutomationSettings::int('auto_weekly_summary_hour'))),
                        fn () => $automation->sendWeeklySummaries($org, $dry),
                    ],
                ];

                foreach ($jobs as $name => [$due, $run]) {
                    if ($only && $only !== $name) {
                        continue;
                    }
                    if (! $due) {
                        continue;
                    }
                    $stats = $run();
                    $this->line(sprintf('[%s] %s: sent %d, failed %d, skipped %d%s',
                        $org->slug, $name, $stats['sent'], $stats['failed'], $stats['skipped'], $dry ? ' (dry run)' : ''));
                }
            } catch (\Throwable $e) {
                report($e);
                $this->error("[{$org->slug}] ".$e->getMessage());
            } finally {
                OrganizationContext::clear();
            }
        }

        return self::SUCCESS;
    }
}
