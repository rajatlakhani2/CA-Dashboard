<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FirmAlert;
use App\Models\Invoice;
use App\Services\Intelligence\ComplianceRiskScorer;
use App\Models\Payment;
use App\Models\ServiceDue;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PartnerDashboardController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->isPartner(), 403);

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $mtdInvoiced = Invoice::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->sum('total_amount');

        $mtdCollected = Payment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])->sum('amount');

        $outstanding = Invoice::whereIn('status', Invoice::OPEN_STATUSES)->sum('total_amount')
            - Payment::whereHas('invoice', fn ($q) => $q->whereIn('status', Invoice::OPEN_STATUSES))->sum('amount');

        $overdueCompliance = ServiceDue::where('status', ServiceDue::STATUS_OVERDUE)->count();
        $overdueInvoices = Invoice::where('status', Invoice::STATUS_OVERDUE)->count();

        $openTasks = Task::whereNotIn('status', Task::TERMINAL_STATUSES)->count();
        $overdueTasks = Task::where('due_date', '<', $today)
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->count();

        $staffLoad = User::query()
            ->whereIn('role', ['staff', 'associate', 'article', 'manager'])
            ->withCount(['tasks as open_tasks_count' => function ($q) {
                $q->whereNotIn('status', Task::TERMINAL_STATUSES);
            }])
            ->orderByDesc('open_tasks_count')
            ->limit(8)
            ->get();

        $branchRevenue = Invoice::select('branch_id', DB::raw('SUM(total_amount) as total'))
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->groupBy('branch_id')
            ->with('branch')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $unbilledQueue = ServiceDue::where('status', ServiceDue::STATUS_COMPLETED)
            ->where('billing_status', ServiceDue::BILLING_STATUS_UNBILLED)
            ->whereNull('invoice_id')
            ->count();

        $atRiskCompliance = app(ComplianceRiskScorer::class)->topAtRisk(8);

        $firmAlerts = FirmAlert::query()
            ->open()
            ->with('client')
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->latest()
            ->limit(20)
            ->get();

        return view('dashboard.partner', compact(
            'mtdInvoiced',
            'mtdCollected',
            'outstanding',
            'overdueCompliance',
            'overdueInvoices',
            'openTasks',
            'overdueTasks',
            'staffLoad',
            'branchRevenue',
            'unbilledQueue',
            'firmAlerts',
            'atRiskCompliance'
        ));
    }
}
