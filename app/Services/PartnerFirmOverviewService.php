<?php

namespace App\Services;

use App\Models\FirmAlert;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceDue;
use App\Models\Task;
use App\Models\User;
use App\Services\Intelligence\ComplianceRiskScorer;
use App\Support\ModuleGate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PartnerFirmOverviewService
{
    public function build(?User $user = null): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $hasFinance = ModuleGate::hasFinanceModule($user);

        $mtdInvoiced = $hasFinance
            ? Invoice::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', '!=', Invoice::STATUS_DRAFT)
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->sum('total_amount')
            : 0;

        $mtdCollected = $hasFinance && ModuleGate::allowed($user, 'payments')
            ? Payment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])->sum('amount')
            : 0;

        $outstanding = 0;
        if ($hasFinance && ModuleGate::allowed($user, 'invoices')) {
            $outstanding = Invoice::whereIn('status', Invoice::OPEN_STATUSES)->sum('total_amount')
                - Payment::whereHas('invoice', fn ($q) => $q->whereIn('status', Invoice::OPEN_STATUSES))->sum('amount');
        }

        return [
            'hasFinance' => $hasFinance,
            'mtdInvoiced' => $mtdInvoiced,
            'mtdCollected' => $mtdCollected,
            'outstanding' => $outstanding,
            'overdueCompliance' => ModuleGate::allowed($user, 'service_dues')
                ? ServiceDue::where('status', ServiceDue::STATUS_OVERDUE)->count()
                : 0,
            'overdueInvoices' => ModuleGate::allowed($user, 'invoices')
                ? Invoice::where('status', Invoice::STATUS_OVERDUE)->count()
                : 0,
            'openTasks' => ModuleGate::allowed($user, 'tasks')
                ? Task::whereNotIn('status', Task::TERMINAL_STATUSES)->count()
                : 0,
            'overdueTasks' => ModuleGate::allowed($user, 'tasks')
                ? Task::where('due_date', '<', $today)
                    ->whereNotIn('status', Task::TERMINAL_STATUSES)
                    ->count()
                : 0,
            'staffLoad' => ModuleGate::allowed($user, 'staff')
                ? User::query()
                    ->whereIn('role', ['staff', 'associate', 'article', 'manager'])
                    ->withCount(['tasks as open_tasks_count' => function ($q) {
                        $q->whereNotIn('status', Task::TERMINAL_STATUSES);
                    }])
                    ->orderByDesc('open_tasks_count')
                    ->limit(8)
                    ->get()
                : collect(),
            'branchRevenue' => $hasFinance && ModuleGate::allowed($user, 'invoices')
                ? Invoice::select('branch_id', DB::raw('SUM(total_amount) as total'))
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->where('status', '!=', Invoice::STATUS_DRAFT)
                    ->groupBy('branch_id')
                    ->with('branch')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->get()
                : collect(),
            'unbilledQueue' => ModuleGate::allowed($user, 'billing')
                ? ServiceDue::where('status', ServiceDue::STATUS_COMPLETED)
                    ->where('billing_status', ServiceDue::BILLING_STATUS_UNBILLED)
                    ->whereNull('invoice_id')
                    ->count()
                : 0,
            'atRiskCompliance' => ModuleGate::allowed($user, 'service_dues')
                ? app(ComplianceRiskScorer::class)->topAtRisk(8)
                : collect(),
            'firmAlerts' => FirmAlert::query()
                ->open()
                ->with('client')
                ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
                ->latest()
                ->limit(20)
                ->get(),
        ];
    }
}
