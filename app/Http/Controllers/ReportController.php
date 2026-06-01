<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ServiceDue;
use App\Models\Client;
use App\Models\Task;
use App\Services\Reports\ClientProfitabilityReportBuilder;
use App\Services\Reports\ReportScopeHelper;
use App\Services\Reports\StaffProductivityReportBuilder;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $this->authorize('viewReports');

        return view('reports.index');
    }

    public function financial(Request $request)
    {
        $this->authorize('viewReports');

        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));

        // Monthly Revenue Graph Data
        $monthlyRevenueQuery = Invoice::select(
            DB::raw($this->monthExpression('date') . ' as month'),
            DB::raw('SUM(total_amount) as total')
        )
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->where('status', '!=', Invoice::STATUS_CANCELLED);
        $this->scopeInvoicesToUser($monthlyRevenueQuery);
        $monthlyRevenue = $monthlyRevenueQuery
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Summary Stats
        $totalInvoicedQuery = Invoice::whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', Invoice::STATUS_DRAFT);
        $this->scopeInvoicesToUser($totalInvoicedQuery);
        $totalInvoiced = $totalInvoicedQuery->sum('total_amount');

        $totalCollectedQuery = Invoice::whereBetween('date', [$startDate, $endDate])
            ->where('status', Invoice::STATUS_PAID);
        $this->scopeInvoicesToUser($totalCollectedQuery);
        $totalCollected = $totalCollectedQuery->sum('total_amount');

        $totalOutstandingQuery = Invoice::whereBetween('date', [$startDate, $endDate])
            ->where('status', Invoice::STATUS_OVERDUE);
        $this->scopeInvoicesToUser($totalOutstandingQuery);
        $totalOutstanding = $totalOutstandingQuery->sum('total_amount');

        // Client-wise Revenue
        $clientRevenueQuery = Invoice::select('client_id', DB::raw('SUM(total_amount) as total'))
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->whereHas('client');
        $this->scopeInvoicesToUser($clientRevenueQuery);
        $clientRevenue = $clientRevenueQuery
            ->groupBy('client_id')
            ->with('client')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return view('reports.financial', compact(
            'startDate',
            'endDate',
            'monthlyRevenue',
            'totalInvoiced',
            'totalCollected',
            'totalOutstanding',
            'clientRevenue'
        ));
    }

    public function compliance(Request $request)
    {
        $this->authorize('viewReports');

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Status Distribution
        $statusDistributionQuery = ServiceDue::select('status', DB::raw('count(*) as count'))
            ->whereBetween('due_date', [$startDate, $endDate]);
        $this->scopeServiceDuesToUser($statusDistributionQuery);
        $statusDistribution = $statusDistributionQuery
            ->groupBy('status')
            ->get();

        // On-Time Completion Rate (Simplified logic: Completed on or before due date)
        // Note: currently 'completed_at' might not be fully populated in all seeders, assuming logic exists
        $totalCompletedQuery = ServiceDue::whereBetween('due_date', [$startDate, $endDate])
            ->where('status', ServiceDue::STATUS_COMPLETED);
        $this->scopeServiceDuesToUser($totalCompletedQuery);
        $totalCompleted = $totalCompletedQuery->count();

        // Assuming we track 'completed_at', we could check if completed_at <= due_date
        // For now, let's just show Total vs Completed vs Overdue

        $totalDuesQuery = ServiceDue::whereBetween('due_date', [$startDate, $endDate]);
        $this->scopeServiceDuesToUser($totalDuesQuery);
        $totalDues = $totalDuesQuery->count();
        $completionRate = $totalDues > 0 ? ($totalCompleted / $totalDues) * 100 : 0;

        // Service-wise breakdown
        // Service-wise breakdown
        $serviceBreakdown = ServiceDue::join('client_services', 'service_dues.client_service_id', '=', 'client_services.id')
            ->join('clients', 'client_services.client_id', '=', 'clients.id')
            ->join('services', 'client_services.service_id', '=', 'services.id')
            ->select('services.id as service_id', 'services.name', DB::raw('count(service_dues.id) as total'))
            ->whereBetween('service_dues.due_date', [$startDate, $endDate])
            ->when($this->currentManagerBranchId(), function ($query, $branchId) {
                $query->where(function ($q) use ($branchId) {
                    $q->whereNull('clients.branch_id')
                        ->orWhere('clients.branch_id', $branchId);
                });
            })
            ->groupBy('services.id', 'services.name')
            ->get();

        return view('reports.compliance', compact(
            'startDate',
            'endDate',
            'statusDistribution',
            'totalDues',
            'completionRate',
            'serviceBreakdown'
        ));
    }

    public function exportFinancial(Request $request)
    {
        $this->authorize('exportReports');

        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));

        $invoiceQuery = Invoice::with('client')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', Invoice::STATUS_DRAFT);
        $this->scopeInvoicesToUser($invoiceQuery);
        $invoices = $invoiceQuery
            ->get();

        $filename = "financial-report-{$startDate}-to-{$endDate}.csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Invoice Number', 'Date', 'Client', 'Status', 'Total Amount']);

            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->invoice_number,
                    $invoice->date->format('Y-m-d'),
                    $invoice->client->name,
                    $invoice->status,
                    $invoice->total_amount
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportCompliance(Request $request)
    {
        $this->authorize('exportReports');

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $dueQuery = ServiceDue::with(['clientService.client', 'clientService.service'])
            ->whereBetween('due_date', [$startDate, $endDate]);
        $this->scopeServiceDuesToUser($dueQuery);
        $dues = $dueQuery
            ->get();

        $filename = "compliance-report-{$startDate}-to-{$endDate}.csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($dues) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Client', 'Service', 'Due Date', 'Status', 'Completed At', 'Remarks']);

            foreach ($dues as $due) {
                fputcsv($file, [
                    $due->clientService->client->name,
                    $due->clientService->service->name,
                    $due->due_date->format('Y-m-d'),
                    $due->status,
                    $due->completed_at ? $due->completed_at->format('Y-m-d') : '',
                    $due->remarks
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function service(Request $request)
    {
        $this->authorize('viewReports');

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Service Performance - Count of service dues by service
        $servicePerformance = ServiceDue::join('client_services', 'service_dues.client_service_id', '=', 'client_services.id')
            ->join('clients', 'client_services.client_id', '=', 'clients.id')
            ->join('services', 'client_services.service_id', '=', 'services.id')
            ->select('services.name', DB::raw('count(service_dues.id) as total_count'))
            ->whereBetween('service_dues.due_date', [$startDate, $endDate])
            ->when($this->currentManagerBranchId(), function ($query, $branchId) {
                $query->where(function ($q) use ($branchId) {
                    $q->whereNull('clients.branch_id')
                        ->orWhere('clients.branch_id', $branchId);
                });
            })
            ->groupBy('services.name')
            ->orderByDesc('total_count')
            ->get();

        // Service Revenue - Revenue by service type from invoices
        $serviceRevenue = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('clients', 'invoices.client_id', '=', 'clients.id')
            ->select('invoice_items.description as service_name', DB::raw('SUM(invoice_items.amount) as total_revenue'))
            ->whereBetween('invoices.date', [$startDate, $endDate])
            ->where('invoices.status', '!=', Invoice::STATUS_DRAFT)
            ->when($this->currentManagerBranchId(), function ($query, $branchId) {
                $query->where(function ($q) use ($branchId) {
                    $q->where('invoices.branch_id', $branchId)
                        ->orWhere(function ($q) use ($branchId) {
                            $q->whereNull('invoices.branch_id')
                                ->where(function ($clientQuery) use ($branchId) {
                                    $clientQuery->whereNull('clients.branch_id')
                                        ->orWhere('clients.branch_id', $branchId);
                                });
                        });
                });
            })
            ->groupBy('invoice_items.description')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        return view('reports.service', compact('servicePerformance', 'serviceRevenue', 'startDate', 'endDate'));
    }

    public function client(Request $request)
    {
        $this->authorize('viewReports');

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Client Activity - Clients with recent activity
        $activeClientsQuery = Client::where(function ($query) use ($startDate, $endDate) {
            $query->whereHas('tasks', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
            })->orWhereHas('invoices', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            });
        });
        $this->scopeClientsToUser($activeClientsQuery);
        $activeClients = $activeClientsQuery->count();

        $totalClientsQuery = Client::query();
        $this->scopeClientsToUser($totalClientsQuery);
        $totalClients = $totalClientsQuery->count();

        $newClientsQuery = Client::whereBetween('created_at', [$startDate, $endDate]);
        $this->scopeClientsToUser($newClientsQuery);
        $newClients = $newClientsQuery->count();

        // Top Clients by Revenue
        $topClientsQuery = Invoice::select('client_id', DB::raw('SUM(total_amount) as total_revenue'))
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', Invoice::STATUS_DRAFT);
        $this->scopeInvoicesToUser($topClientsQuery);
        $topClients = $topClientsQuery
            ->groupBy('client_id')
            ->with('client')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // Client Services Distribution
        $clientServices = DB::table('client_services')
            ->join('clients', 'client_services.client_id', '=', 'clients.id')
            ->select(DB::raw('COUNT(DISTINCT client_services.client_id) as client_count'), DB::raw('COUNT(client_services.id) as service_count'))
            ->when($this->currentManagerBranchId(), function ($query, $branchId) {
                $query->where(function ($q) use ($branchId) {
                    $q->whereNull('clients.branch_id')
                        ->orWhere('clients.branch_id', $branchId);
                });
            })
            ->first();

        return view('reports.client', compact('activeClients', 'totalClients', 'newClients', 'topClients', 'clientServices', 'startDate', 'endDate'));
    }

    public function task(Request $request)
    {
        $this->authorize('viewReports');

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Task Status Distribution
        $tasksByStatusQuery = Task::select('status', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate]);
        $this->scopeTasksToUser($tasksByStatusQuery);
        $tasksByStatus = $tasksByStatusQuery
            ->groupBy('status')
            ->get();

        // Task Assignment Distribution
        $tasksByAssigneeQuery = Task::select('assigned_to', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate]);
        $this->scopeTasksToUser($tasksByAssigneeQuery);
        $tasksByAssignee = $tasksByAssigneeQuery
            ->with('assignee')
            ->groupBy('assigned_to')
            ->get();

        // Completion Rate
        $totalTasksQuery = Task::whereBetween('created_at', [$startDate, $endDate]);
        $this->scopeTasksToUser($totalTasksQuery);
        $totalTasks = $totalTasksQuery->count();
        $completedTasksQuery = Task::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Task::STATUS_COMPLETED);
        $this->scopeTasksToUser($completedTasksQuery);
        $completedTasks = $completedTasksQuery->count();
        $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        // Overdue Tasks
        $overdueTasksQuery = Task::where('due_date', '<', Carbon::now())
            ->where('status', '!=', Task::STATUS_COMPLETED);
        $this->scopeTasksToUser($overdueTasksQuery);
        $overdueTasks = $overdueTasksQuery->count();

        return view('reports.task', compact('tasksByStatus', 'tasksByAssignee', 'totalTasks', 'completedTasks', 'completionRate', 'overdueTasks', 'startDate', 'endDate'));
    }

    public function dueDate(Request $request)
    {
        $this->authorize('viewReports');

        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->addDays(30)->format('Y-m-d'));

        // Upcoming Service Dues
        $upcomingServiceDueQuery = ServiceDue::with(['clientService.client', 'clientService.service'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->where('status', '!=', ServiceDue::STATUS_COMPLETED);
        $this->scopeServiceDuesToUser($upcomingServiceDueQuery);
        $upcomingServiceDues = $upcomingServiceDueQuery
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        // Upcoming Tasks
        $upcomingTaskQuery = Task::with('client')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->where('status', '!=', Task::STATUS_COMPLETED);
        $this->scopeTasksToUser($upcomingTaskQuery);
        $upcomingTasks = $upcomingTaskQuery
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        // Overdue Items
        $overdueServiceDueQuery = ServiceDue::where('due_date', '<', Carbon::now())
            ->where('status', '!=', ServiceDue::STATUS_COMPLETED);
        $this->scopeServiceDuesToUser($overdueServiceDueQuery);
        $overdueServiceDues = $overdueServiceDueQuery->count();

        $overdueTaskQuery = Task::where('due_date', '<', Carbon::now())
            ->where('status', '!=', Task::STATUS_COMPLETED);
        $this->scopeTasksToUser($overdueTaskQuery);
        $overdueTasks = $overdueTaskQuery->count();

        // Breakdown by time period
        $next7DaysQuery = ServiceDue::whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays(7)])
            ->where('status', '!=', ServiceDue::STATUS_COMPLETED);
        $this->scopeServiceDuesToUser($next7DaysQuery);
        $next7Days = $next7DaysQuery->count();

        $next15DaysQuery = ServiceDue::whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays(15)])
            ->where('status', '!=', ServiceDue::STATUS_COMPLETED);
        $this->scopeServiceDuesToUser($next15DaysQuery);
        $next15Days = $next15DaysQuery->count();

        $next30DaysQuery = ServiceDue::whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays(30)])
            ->where('status', '!=', ServiceDue::STATUS_COMPLETED);
        $this->scopeServiceDuesToUser($next30DaysQuery);
        $next30Days = $next30DaysQuery->count();

        return view('reports.due-date', compact('upcomingServiceDues', 'upcomingTasks', 'overdueServiceDues', 'overdueTasks', 'next7Days', 'next15Days', 'next30Days', 'startDate', 'endDate'));
    }

    public function staffProductivity(Request $request, StaffProductivityReportBuilder $builder)
    {
        $this->authorize('viewReports');

        [$start, $end] = ReportScopeHelper::datesFromRequest($request);
        $report = $builder->build($request->user(), $start, $end);

        return view('reports.staff-productivity', [
            'rows' => $report['rows'],
            'totals' => $report['totals'],
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
        ]);
    }

    public function clientProfitability(Request $request, ClientProfitabilityReportBuilder $builder)
    {
        $this->authorize('viewReports');

        [$start, $end] = ReportScopeHelper::datesFromRequest($request);
        $clientId = $request->filled('client_id') ? (int) $request->client_id : null;
        $report = $builder->build($request->user(), $start, $end, $clientId);

        return view('reports.client-profitability', [
            'rows' => $report['rows'],
            'totals' => $report['totals'],
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
        ]);
    }

    private function scopeInvoicesToUser($query): void
    {
        $branchId = $this->currentManagerBranchId();

        if (! $branchId) {
            return;
        }

        $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->orWhere(function ($q) use ($branchId) {
                    $q->whereNull('branch_id')
                        ->whereHas('client', function ($clientQuery) use ($branchId) {
                            $clientQuery->whereNull('branch_id')
                                ->orWhere('branch_id', $branchId);
                        });
                });
        });
    }

    private function scopeServiceDuesToUser($query): void
    {
        $branchId = $this->currentManagerBranchId();

        if (! $branchId) {
            return;
        }

        $query->whereHas('clientService.client', function ($q) use ($branchId) {
            $q->whereNull('branch_id')
                ->orWhere('branch_id', $branchId);
        });
    }

    private function scopeClientsToUser($query): void
    {
        $branchId = $this->currentManagerBranchId();

        if (! $branchId) {
            return;
        }

        $query->where(function ($q) use ($branchId) {
            $q->whereNull('branch_id')
                ->orWhere('branch_id', $branchId);
        });
    }

    private function scopeTasksToUser($query): void
    {
        $branchId = $this->currentManagerBranchId();

        if (! $branchId) {
            return;
        }

        $query->where(function ($q) use ($branchId) {
            $q->whereNull('client_id')
                ->orWhereHas('client', function ($clientQuery) use ($branchId) {
                    $clientQuery->whereNull('branch_id')
                        ->orWhere('branch_id', $branchId);
                });
        });
    }

    private function currentManagerBranchId(): ?int
    {
        $user = auth()->user();

        return $user?->isManager() && $user->branch_id
            ? (int) $user->branch_id
            : null;
    }

    private function monthExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }
}
