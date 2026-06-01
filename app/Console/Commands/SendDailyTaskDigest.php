<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailyTaskDigest extends Command
{
    protected $signature = 'tasks:send-daily-digest';

    protected $description = 'Send end-of-day WhatsApp task summary to each staff member';

    public function handle(WhatsAppService $whatsAppService): int
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        $users = User::query()
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->whereIn('role', ['staff', 'associate', 'article', 'manager', 'partner'])
            ->get();

        $sent = 0;

        foreach ($users as $user) {
            if (! $user->canAccessModule('tasks')) {
                continue;
            }

            $overdue = Task::query()
                ->where('assigned_to', $user->id)
                ->whereNotIn('status', Task::TERMINAL_STATUSES)
                ->whereDate('due_date', '<', $today)
                ->count();

            $dueToday = Task::query()
                ->where('assigned_to', $user->id)
                ->whereNotIn('status', Task::TERMINAL_STATUSES)
                ->whereDate('due_date', $today)
                ->count();

            $dueTomorrow = Task::query()
                ->where('assigned_to', $user->id)
                ->whereNotIn('status', Task::TERMINAL_STATUSES)
                ->whereDate('due_date', $tomorrow)
                ->count();

            if ($overdue === 0 && $dueToday === 0 && $dueTomorrow === 0) {
                continue;
            }

            $message = "📋 *Daily Task Digest*\n\n"
                ."Hi {$user->name},\n"
                ."• Overdue: {$overdue}\n"
                ."• Due today: {$dueToday}\n"
                ."• Due tomorrow: {$dueTomorrow}\n\n"
                .'Open My Day: '.url('/my-day');

            $result = $whatsAppService->sendMessage($user->mobile, $message);
            if ($result['success'] ?? false) {
                $sent++;
            }
        }

        $this->info("Daily digest sent to {$sent} user(s).");

        return self::SUCCESS;
    }
}
