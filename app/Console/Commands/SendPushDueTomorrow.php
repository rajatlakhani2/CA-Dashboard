<?php

namespace App\Console\Commands;

use App\Models\ServiceDue;
use App\Models\Task;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/** Morning phone notification: your tasks due tomorrow, plus firm filings due tomorrow for partners/managers. */
class SendPushDueTomorrow extends Command
{
    protected $signature = 'push:due-tomorrow {--dry-run : List what would be sent without sending}';

    protected $description = 'Send the "due tomorrow" push notification to phones with the CA Dashboard app';

    public function handle(PushNotificationService $push): int
    {
        if (! $push->isConfigured() && ! $this->option('dry-run')) {
            $this->info('Push notifications are not set up (FIREBASE_CREDENTIALS is empty). Nothing sent.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('user_devices')) {
            return self::SUCCESS;
        }

        $tomorrow = now()->addDay()->toDateString();
        $userIds = UserDevice::distinct()->pluck('user_id');
        $sent = 0;

        foreach (User::withoutGlobalScopes()->whereIn('id', $userIds)->get() as $user) {
            $myTasks = Task::withoutGlobalScopes()
                ->where('organization_id', $user->organization_id)
                ->where('assigned_to', $user->id)
                ->whereDate('due_date', $tomorrow)
                ->whereNotIn('status', Task::TERMINAL_STATUSES)
                ->count();

            $filings = 0;
            if ($user->managesFirmModules() && $user->canAccessModule('service_dues')) {
                $filings = ServiceDue::withoutGlobalScopes()
                    ->where('organization_id', $user->organization_id)
                    ->whereDate('due_date', $tomorrow)
                    ->whereNotIn('status', ['Completed'])
                    ->count();
            }

            if ($myTasks === 0 && $filings === 0) {
                continue;
            }

            $parts = [];
            if ($myTasks > 0) {
                $parts[] = $myTasks . ' ' . ($myTasks === 1 ? 'task' : 'tasks') . ' of yours';
            }
            if ($filings > 0) {
                $parts[] = $filings . ' client ' . ($filings === 1 ? 'filing' : 'filings');
            }
            $body = 'Due tomorrow: ' . implode(' and ', $parts) . '.';
            $url = $user->prefersMyDayHome() ? route('tasks.my-day') : route('dashboard');

            if ($this->option('dry-run')) {
                $this->line("{$user->email}: {$body}");

                continue;
            }

            $sent += $push->sendToUser($user, 'due_tomorrow', 'Due tomorrow', $body, $url);
        }

        $this->info("Push notifications sent: {$sent}");

        return self::SUCCESS;
    }
}
