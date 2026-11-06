<?php

namespace App\Console\Commands;

use App\Models\PersonalRenewal;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Email reminders for LIC / insurance / loan dues.
 * Sent on: the policy's "remind me N days before" day, 1 day before,
 * the due date, and every 3 days while overdue (up to 30 days).
 * WhatsApp reminders for these go out in the daily WhatsApp message.
 */
class SendRenewalReminders extends Command
{
    protected $signature = 'reminders:personal-renewals';

    protected $description = 'Email reminders for LIC, insurance and loan dues';

    public function handle(): int
    {
        $today = now()->startOfDay();

        $renewals = PersonalRenewal::withoutGlobalScopes()
            ->where('status', PersonalRenewal::STATUS_PENDING)
            ->whereDate('due_date', '<=', $today->copy()->addDays(30))
            ->whereDate('due_date', '>=', $today->copy()->subDays(30))
            ->with('user')
            ->get();

        $sent = 0;

        foreach ($renewals as $renewal) {
            $days = $renewal->daysLeft();
            $lead = (int) ($renewal->remind_days_before ?: 7);

            $shouldSend = $days === $lead
                || $days === 1
                || $days === 0
                || ($days < 0 && abs($days) % 3 === 0);

            if (! $shouldSend || ($renewal->last_reminded_on && $renewal->last_reminded_on->isSameDay($today))) {
                continue;
            }

            $user = $renewal->user ?? User::find(1);
            if (! $user?->email) {
                continue;
            }

            try {
                Mail::to($user->email)->send(new \App\Mail\PersonalRenewalReminder($renewal));
                $renewal->forceFill(['last_reminded_on' => $today])->save();
                $sent++;
                $this->info("Sent reminder for {$renewal->title} to {$user->email}");
            } catch (\Throwable $e) {
                $this->warn("Could not email {$user->email}: ".$e->getMessage());
            }
        }

        $this->info("Renewal reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
