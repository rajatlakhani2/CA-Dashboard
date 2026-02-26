<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceDue;
use App\Mail\UpcomingDuesNotification;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\User;

class SendUpcomingDuesNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:notify-dues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email notification for upcoming service dues to admins';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for upcoming dues...');

        $today = Carbon::today();
        $nextWeek = Carbon::today()->addDays(7);

        // Fetch upcoming pending dues
        $dues = ServiceDue::with(['clientService.client', 'clientService.service'])
            ->where('status', 'Pending')
            ->whereBetween('due_date', [$today, $nextWeek])
            ->orderBy('due_date', 'asc')
            ->get();

        if ($dues->isEmpty()) {
            $this->info('No upcoming dues found.');
            return;
        }

        $this->info("Found {$dues->count()} upcoming dues. Sending email...");

        // Get Admin Email (Assuming first user or hardcoded for now, ideally configured)
        // For this user, we'll try to get the first admin user, or fallback
        $admin = User::first();
        $email = $admin ? $admin->email : config('mail.from.address');

        if (!$email) {
            $this->error('No admin email found to send notification.');
            return;
        }

        Mail::to($email)->send(new UpcomingDuesNotification($dues));

        $this->info("Notification sent to {$email}.");
    }
}
