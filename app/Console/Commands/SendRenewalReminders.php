<?php

namespace App\Console\Commands;

use App\Models\PersonalRenewal;
use Illuminate\Console\Command;

class SendRenewalReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:personal-renewals';
    protected $description = 'Send email reminders for personal renewals due in 3 days';

    public function handle()
    {
        $targetDate = now()->addDays(3)->toDateString();

        $renewals = PersonalRenewal::whereDate('due_date', $targetDate)
            ->where('status', PersonalRenewal::STATUS_PENDING)
            ->with('user')
            ->get();

        $this->info("Found " . $renewals->count() . " renewals due on {$targetDate}.");

        foreach ($renewals as $renewal) {
            // Assuming user has email. If renewal->user_id is hardcoded to 1, fetch that user.
            $user = $renewal->user ?? \App\Models\User::find(1);

            if ($user && $user->email) {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\PersonalRenewalReminder($renewal));
                $this->info("Sent reminder for {$renewal->title} to {$user->email}");
            }
        }
    }
}
