<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceDue;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\UpcomingDuesNotification;
use Carbon\Carbon;

class SendDailyNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily email notifications for upcoming service dues.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding upcoming dues...');

        // Logic: Get dues for next 7 days, excluding Completed
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(7);

        $dues = ServiceDue::with(['clientService.client', 'clientService.service'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->where('status', '!=', ServiceDue::STATUS_COMPLETED)
            ->orderBy('due_date', 'asc')
            ->get();

        if ($dues->isEmpty()) {
            $this->info('No upcoming dues found for next 7 days.');
            return;
        }

        // Get Admin User (First user for now)
        $admin = User::first();
        if (!$admin) {
            $this->error('No user found to send email to.');
            return;
        }

        $this->info("Sending email to {$admin->email} with {$dues->count()} dues...");

        Mail::to($admin->email)->send(new UpcomingDuesNotification($dues));

        $this->info('Notification sent successfully.');
    }
}
