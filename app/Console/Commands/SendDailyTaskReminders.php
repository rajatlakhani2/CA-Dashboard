<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Task;
use App\Models\ServiceDue;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendDailyTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily pending task reminders via WhatsApp to staff and admin';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $whatsAppService)
    {
        $this->info('Starting Daily WhatsApp Task & Dues Reminders...');

        $daysAhead = (int) \App\Models\Setting::get('reminder_days_ahead', '7');
        $cutoffDate = \Carbon\Carbon::now()->addDays($daysAhead)->endOfDay();

        // 1. Get all staff users with a mobile number and pending tasks
        $staffUsers = User::whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->with(['tasks' => function ($query) use ($cutoffDate) {
                $query->whereNotIn('status', Task::TERMINAL_STATUSES)
                      ->where(function($q) use ($cutoffDate) {
                          $q->whereNull('due_date')
                            ->orWhere('due_date', '<=', $cutoffDate);
                      })
                      ->with('client');
            }])
            ->get();

        $adminSummaryText = "*Daily Firm Summary (Next {$daysAhead} Days)*\n\n";
        $totalPendingFirm = 0;

        foreach ($staffUsers as $staff) {
            if ($staff->tasks->isEmpty()) {
                continue;
            }

            $totalPendingFirm += $staff->tasks->count();

            // Build Staff Message
            $staffMessage = "*Daily Reminder: Your Upcoming Tasks*\nHello {$staff->name},\nHere are your pending tasks due within the next {$daysAhead} days:\n\n";
            
            $adminSummaryText .= "*{$staff->name}* ({$staff->tasks->count()} tasks):\n";

            foreach ($staff->tasks as $index => $task) {
                $clientName = $task->client ? $task->client->name : 'Internal';
                $dueDate = $task->due_date ? $task->due_date->format('d M Y') : 'No Date';
                
                $taskLine = ($index + 1) . ". {$task->title} - {$clientName} (Due: {$dueDate})\n";
                $staffMessage .= $taskLine;
                $adminSummaryText .= " - {$task->title} ({$clientName})\n";
            }

            $staffMessage .= "\nPlease prioritize these tasks. Have a productive day!";

            // Send to Staff
            $this->info("Sending reminder to {$staff->name} ({$staff->mobile})");
            $response = $whatsAppService->sendMessage($staff->mobile, $staffMessage);
            if (!$response['success']) {
                $this->error("Failed to send to {$staff->name}: " . $response['message']);
            }
        }

        // 2. Fetch upcoming Service Dues
        $upcomingDues = ServiceDue::where('status', ServiceDue::STATUS_PENDING)
            ->where('due_date', '<=', $cutoffDate)
            ->with(['clientService.client', 'clientService.service'])
            ->orderBy('due_date', 'asc')
            ->get();

        if ($upcomingDues->isNotEmpty()) {
            $adminSummaryText .= "\n*Upcoming Service Dues ({$upcomingDues->count()}):*\n";
            foreach($upcomingDues as $due) {
                $clientName = $due->clientService->client->name ?? 'Unknown';
                $serviceName = $due->clientService->service->name ?? 'Service';
                $dueDateStr = $due->due_date->format('d M Y');
                $adminSummaryText .= " - {$clientName}: {$serviceName} (Due {$dueDateStr})\n";
            }
        }

        // 3. Send Master Summary to Admin/Partner
        $partners = User::where('role', 'partner')
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->get();

        if ($totalPendingFirm > 0 || $upcomingDues->isNotEmpty()) {
            foreach ($partners as $partner) {
                $this->info("Sending Master Summary to Partner: {$partner->name} ({$partner->mobile})");
                $whatsAppService->sendMessage($partner->mobile, $adminSummaryText);
            }
        } else {
            $this->info('No pending tasks or dues to send reminders for.');
        }

        $this->info('Finished sending reminders.');
    }
}
