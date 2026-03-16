<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ServiceDue;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // --- 1. KEY SUMMARY TILES ---
        $summary = [
            'total_clients' => Client::count(),
            'new_clients_this_month' => Client::where('created_at', '>=', $startOfMonth)->count(),
            'services_due_month' => ServiceDue::whereBetween('due_date', [$startOfMonth, $endOfMonth])->count(),
            'services_due_today' => ServiceDue::whereDate('due_date', $today)->where('status', 'Pending')->count(),
            'services_overdue' => ServiceDue::where('status', 'Overdue')->count(),
            'upcoming_renewals' => ServiceDue::where('status', 'Pending')
                ->whereBetween('due_date', [$today, $today->copy()->addDays(30)])
                ->count(),
            'outstanding_fees' => '₹ ' . number_format(
                \App\Models\Invoice::whereIn('status', ['Sent', 'Overdue', 'Partially Paid'])->sum('total_amount')
                    - \App\Models\Payment::whereHas('invoice', fn($q) => $q->whereIn('status', ['Sent', 'Overdue', 'Partially Paid']))->sum('amount'),
                0
            ),
            'overdue_collections' => '₹ ' . number_format(
                \App\Models\Invoice::where('status', 'Overdue')->sum('total_amount')
                    - \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('status', 'Overdue'))->sum('amount'),
                0
            ),
            'expiring_dscs' => \App\Models\Dsc::where('status', 'Active')
                ->where('expiry_date', '<=', $today->copy()->addDays(30))
                ->where('expiry_date', '>=', $today)
                ->count(),
        ];

        // --- 2. COMPLIANCE & SERVICE DUE SECTION ---
        // Upcoming (Next 7, 15, 30 days) - COMBINED (ServiceDue + Task)
        $upcomingCounts = [
            '7_days' => ServiceDue::where('status', 'Pending')->whereBetween('due_date', [$today, $today->copy()->addDays(7)])->count()
                + \App\Models\Task::whereNotIn('status', ['Completed', 'Done', 'Closed'])->where('assigned_to', auth()->id())->whereBetween('due_date', [$today, $today->copy()->addDays(7)])->count(),

            '15_days' => ServiceDue::where('status', 'Pending')->whereBetween('due_date', [$today, $today->copy()->addDays(15)])->count()
                + \App\Models\Task::whereNotIn('status', ['Completed', 'Done', 'Closed'])->where('assigned_to', auth()->id())->whereBetween('due_date', [$today, $today->copy()->addDays(15)])->count(),

            '30_days' => ServiceDue::where('status', 'Pending')->whereBetween('due_date', [$today, $today->copy()->addDays(30)])->count()
                + \App\Models\Task::whereNotIn('status', ['Completed', 'Done', 'Closed'])->where('assigned_to', auth()->id())->whereBetween('due_date', [$today, $today->copy()->addDays(30)])->count(),
        ];

        // Service-wise Pending
        $serviceWisePending = ServiceDue::with('clientService.service')
            ->where('status', 'Pending')
            ->get()
            ->groupBy(function ($due) {
                return $due->clientService->service->name;
            })
            ->map
            ->count();

        // High Risk Clients (Category A with Overdue/Due Today)
        $highRiskClients = ServiceDue::whereIn('status', ['Overdue', 'Pending'])
            ->whereDate('due_date', '<=', $today)
            ->whereHas('clientService.client', function ($q) {
                $q->where('category', 'A');
            })
            ->with('clientService.client')
            ->get()
            ->unique('clientService.client.id')
            ->pluck('clientService.client');

        // --- 3. ALERTS & NOTIFICATIONS ---
        $alerts = ServiceDue::with(['clientService.client', 'clientService.service'])
            ->where(function ($q) use ($today) {
                $q->where('status', 'Overdue')
                    ->orWhereDate('due_date', $today);
            })
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        // --- 4. CALENDAR DATA ---
        // Group dues by date for the current month
        $calendarDues = ServiceDue::whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->due_date)->format('Y-m-d');
            });



        // --- 5. PENDING TASKS WIDGET ---
        // FIX: Exclude both 'Completed' and potential variations like 'Done', 'Closed'
        // Logic: Show tasks assigned to me OR (created by me AND unassigned)
        $myPendingTasks = \App\Models\Task::whereNotIn('status', ['Completed', 'Done', 'Closed'])
            ->where(function ($q) {
                $q->where('assigned_to', auth()->id())
                    ->orWhere(function ($sub) {
                        $sub->whereNull('assigned_to')
                            ->where('created_by', auth()->id());
                    });
            })
            ->orderBy('due_date', 'asc')
            ->limit(100) // Increased limit to show "whole" list in welcome modal
            ->with('client')
            ->get();

        // --- 6. ANALYTICS DATA (For Charts) ---
        // --- 6. CALENDAR EVENTS (Live & Interactive) ---
        $calendarEvents = [];

        // Tasks
        $tasks = \App\Models\Task::where('assigned_to', auth()->id())
            ->whereNotIn('status', ['Completed', 'Done', 'Closed'])
            ->get();

        foreach ($tasks as $task) {
            $color = $task->due_date->isPast() ? '#ef4444' : '#3b82f6'; // Red if overdue, Blue otherwise
            $calendarEvents[] = [
                'id' => 'task_' . $task->id,
                'title' => 'Task: ' . $task->title,
                'start' => $task->due_date->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'allDay' => true,
                'extendedProps' => [
                    'type' => 'task',
                    'db_id' => $task->id,
                    'client_name' => $task->client->name ?? 'Internal Task',
                    'details' => $task->title
                ]
            ];
        }

        // Service Dues
        $dues = ServiceDue::whereBetween('due_date', [$startOfMonth->copy()->subMonth(), $endOfMonth->copy()->addMonth()]) // Window +/- 1 month
            ->where('status', 'Pending')
            ->with(['clientService.client', 'clientService.service'])
            ->get();

        foreach ($dues as $due) {
            $color = $due->due_date->isPast() ? '#b91c1c' : '#8b5cf6'; // Dark Red if overdue, Purple otherwise
            $clientName = $due->clientService->client->name ?? 'Unknown';
            $serviceName = $due->clientService->service->name ?? 'Service';

            $calendarEvents[] = [
                'id' => 'due_' . $due->id,
                'title' => "$clientName - $serviceName",
                'start' => \Carbon\Carbon::parse($due->due_date)->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'allDay' => true,
                'extendedProps' => [
                    'type' => 'due',
                    'db_id' => $due->id,
                    'client_name' => $clientName,
                    'details' => $serviceName
                ]
            ];
        }

        // 6b. Compliance Status Distribution (Current Month)
        // We want to see how we are performing this month
        $complianceStats = [
            'Pending' => ServiceDue::whereBetween('due_date', [$startOfMonth, $endOfMonth])->where('status', 'Pending')->count(),
            'Completed' => ServiceDue::whereBetween('due_date', [$startOfMonth, $endOfMonth])->where('status', 'Completed')->count(),

            'Overdue' => ServiceDue::whereBetween('due_date', [$startOfMonth, $endOfMonth])->where('status', 'Overdue')->count(),
        ];

        // --- 7. RECENT CLIENT 360 ---
        $recentClients = Client::orderBy('updated_at', 'desc')->take(5)->get();

        // --- 8. WELCOME MODAL LOGIC ---
        $showWelcomeModal = !session()->has('welcome_shown');
        if ($showWelcomeModal) {
            session(['welcome_shown' => true]);
        }

        $positiveThoughts = [
            "Believe you can and you're halfway there.",
            "The only way to do great work is to love what you do.",
            "Success is not final, failure is not fatal: it is the courage to continue that counts.",
            "Your limitation—it's only your imagination.",
            "Push yourself, because no one else is going to do it for you.",
            "Great things never come from comfort zones.",
            "Dream it. Wish it. Do it.",
            "Success doesn’t just find you. You have to go out and get it.",
            "The harder you work for something, the greater you’ll feel when you achieve it.",
            "Dream bigger. Do bigger.",
            "Don’t stop when you’re tired. Stop when you’re done.",
            "Wake up with determination. Go to bed with satisfaction.",
            "Do something today that your future self will thank you for.",
            "Little things make big days.",
            "It’s going to be hard, but hard does not mean impossible.",
            "Don’t wait for opportunity. Create it.",
            "Sometimes we’re tested not to show our weaknesses, but to discover our strengths.",
            "The key to success is to focus on goals, not obstacles.",
            "Dream it. Believe it. Build it.",
        ];
        $positiveThought = $positiveThoughts[array_rand($positiveThoughts)];

        return view('dashboard', compact(
            'summary',
            'upcomingCounts',
            'serviceWisePending',
            'highRiskClients',
            'alerts',
            'calendarDues',
            'myPendingTasks',
            'calendarEvents',
            'complianceStats',
            'recentClients',
            'showWelcomeModal',
            'positiveThought'
        ));
    }
    public function updateDate(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:task,due',
            'id' => 'required|integer',
            'new_date' => 'required|date',
        ]);

        try {
            if ($validated['type'] == 'task') {
                $item = \App\Models\Task::find($validated['id']);
                if ($item && $item->assigned_to == auth()->id()) {
                    $item->due_date = $validated['new_date'];
                    $item->save();
                    return response()->json(['success' => true]);
                }
            } elseif ($validated['type'] == 'due') {
                $item = ServiceDue::find($validated['id']);
                if ($item) {
                    $item->due_date = $validated['new_date'];
                    $item->save();
                    return response()->json(['success' => true]);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'message' => 'Item not found']);
    }
}
