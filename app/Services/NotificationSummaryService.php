<?php

namespace App\Services;

use App\Models\Dsc;
use App\Models\Invoice;
use App\Models\ServiceDue;
use App\Models\Task;
use Carbon\Carbon;

class NotificationSummaryService
{
    public function groups(): array
    {
        $today = Carbon::today();

        return array_values(array_filter([
            [
                'key' => 'compliance',
                'label' => 'Compliance',
                'count' => ServiceDue::where('status', ServiceDue::STATUS_OVERDUE)->count(),
                'url' => route('service-dues.index'),
            ],
            [
                'key' => 'billing',
                'label' => 'Billing',
                'count' => Invoice::whereIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_OVERDUE, Invoice::STATUS_PARTIALLY_PAID])->count(),
                'url' => route('invoices.index'),
            ],
            [
                'key' => 'dsc',
                'label' => 'DSC',
                'count' => Dsc::where('status', Dsc::STATUS_ACTIVE)
                    ->where('expiry_date', '<=', $today->copy()->addDays(30))
                    ->where('expiry_date', '>=', $today)
                    ->count(),
                'url' => route('dscs.index'),
            ],
            [
                'key' => 'tasks',
                'label' => 'Tasks',
                'count' => Task::query()
                    ->whereNotIn('status', Task::TERMINAL_STATUSES)
                    ->whereDate('due_date', '<', $today)
                    ->count(),
                'url' => route('tasks.index'),
            ],
        ], fn ($g) => $g['count'] > 0));
    }
}
