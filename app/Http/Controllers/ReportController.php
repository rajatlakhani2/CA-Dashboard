<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ServiceDue;
use App\Models\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function financial(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));

        // Monthly Revenue Graph Data
        $monthlyRevenue = Invoice::select(
            DB::raw('DATE_FORMAT(date, "%Y-%m") as month'),
            DB::raw('SUM(total_amount) as total')
        )
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', 'Draft')
            ->where('status', '!=', 'Cancelled')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Summary Stats
        $totalInvoiced = Invoice::whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', 'Draft')->sum('total_amount');

        $totalCollected = Invoice::whereBetween('date', [$startDate, $endDate])
            ->where('status', 'Paid')->sum('total_amount');

        $totalOutstanding = Invoice::whereBetween('date', [$startDate, $endDate])
            ->where('status', 'Overdue')->sum('total_amount');

        // Client-wise Revenue
        $clientRevenue = Invoice::select('client_id', DB::raw('SUM(total_amount) as total'))
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', 'Draft')
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
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Status Distribution
        $statusDistribution = ServiceDue::select('status', DB::raw('count(*) as count'))
            ->whereBetween('due_date', [$startDate, $endDate])
            ->groupBy('status')
            ->get();

        // On-Time Completion Rate (Simplified logic: Completed on or before due date)
        // Note: currently 'completed_at' might not be fully populated in all seeders, assuming logic exists
        $totalCompleted = ServiceDue::whereBetween('due_date', [$startDate, $endDate])
            ->where('status', 'Completed')
            ->count();

        // Assuming we track 'completed_at', we could check if completed_at <= due_date
        // For now, let's just show Total vs Completed vs Overdue

        $totalDues = ServiceDue::whereBetween('due_date', [$startDate, $endDate])->count();
        $completionRate = $totalDues > 0 ? ($totalCompleted / $totalDues) * 100 : 0;

        // Service-wise breakdown
        // Service-wise breakdown
        $serviceBreakdown = ServiceDue::join('client_services', 'service_dues.client_service_id', '=', 'client_services.id')
            ->join('services', 'client_services.service_id', '=', 'services.id')
            ->select('services.id as service_id', 'services.name', DB::raw('count(service_dues.id) as total'))
            ->whereBetween('service_dues.due_date', [$startDate, $endDate])
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
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));

        $invoices = Invoice::with('client')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', 'Draft')
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
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $dues = ServiceDue::with(['clientService.client', 'clientService.service'])
            ->whereBetween('due_date', [$startDate, $endDate])
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
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Service Performance - Count of service dues by service
        $servicePerformance = ServiceDue::join('client_services', 'service_dues.client_service_id', '=', 'client_services.id')
            ->join('services', 'client_services.service_id', '=', 'services.id')
            ->select('services.name', DB::raw('count(service_dues.id) as total_count'))
            ->whereBetween('service_dues.due_date', [$startDate, $endDate])
            ->groupBy('services.name')
            ->orderByDesc('total_count')
            ->get();

        // Service Revenue - Revenue by service type from invoices
        $serviceRevenue = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->select('invoice_items.description as service_name', DB::raw('SUM(invoice_items.amount) as total_revenue'))
            ->whereBetween('invoices.date', [$startDate, $endDate])
            ->where('invoices.status', '!=', 'Draft')
            ->groupBy('invoice_items.description')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        return view('reports.service', compact('servicePerformance', 'serviceRevenue', 'startDate', 'endDate'));
    }

    public function client(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Client Activity - Clients with recent activity
        $activeClients = Client::whereHas('tasks', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        })->orWhereHas('invoices', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        })->count();

        $totalClients = Client::count();
        $newClients = Client::whereBetween('created_at', [$startDate, $endDate])->count();

        // Top Clients by Revenue
        $topClients = Invoice::select('client_id', DB::raw('SUM(total_amount) as total_revenue'))
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', 'Draft')
            ->groupBy('client_id')
            ->with('client')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // Client Services Distribution
        $clientServices = DB::table('client_services')
            ->join('clients', 'client_services.client_id', '=', 'clients.id')
            ->select(DB::raw('COUNT(DISTINCT client_services.client_id) as client_count'), DB::raw('COUNT(client_services.id) as service_count'))
            ->first();

        return view('reports.client', compact('activeClients', 'totalClients', 'newClients', 'topClients', 'clientServices', 'startDate', 'endDate'));
    }

    public function task(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Task Status Distribution
        $tasksByStatus = \App\Models\Task::select('status', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();

        // Task Assignment Distribution
        $tasksByAssignee = \App\Models\Task::select('assigned_to', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('assignee')
            ->groupBy('assigned_to')
            ->get();

        // Completion Rate
        $totalTasks = \App\Models\Task::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedTasks = \App\Models\Task::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'Completed')->count();
        $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        // Overdue Tasks
        $overdueTasks = \App\Models\Task::where('due_date', '<', Carbon::now())
            ->where('status', '!=', 'Completed')
            ->count();

        return view('reports.task', compact('tasksByStatus', 'tasksByAssignee', 'totalTasks', 'completedTasks', 'completionRate', 'overdueTasks', 'startDate', 'endDate'));
    }

    public function dueDate(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->addDays(30)->format('Y-m-d'));

        // Upcoming Service Dues
        $upcomingServiceDues = ServiceDue::with(['clientService.client', 'clientService.service'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->where('status', '!=', 'Completed')
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        // Upcoming Tasks
        $upcomingTasks = \App\Models\Task::with('client')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->where('status', '!=', 'Completed')
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        // Overdue Items
        $overdueServiceDues = ServiceDue::where('due_date', '<', Carbon::now())
            ->where('status', '!=', 'Completed')
            ->count();

        $overdueTasks = \App\Models\Task::where('due_date', '<', Carbon::now())
            ->where('status', '!=', 'Completed')
            ->count();

        // Breakdown by time period
        $next7Days = ServiceDue::whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays(7)])
            ->where('status', '!=', 'Completed')->count();
        $next15Days = ServiceDue::whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays(15)])
            ->where('status', '!=', 'Completed')->count();
        $next30Days = ServiceDue::whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays(30)])
            ->where('status', '!=', 'Completed')->count();

        return view('reports.due-date', compact('upcomingServiceDues', 'upcomingTasks', 'overdueServiceDues', 'overdueTasks', 'next7Days', 'next15Days', 'next30Days', 'startDate', 'endDate'));
    }
}
