<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function globalSearch(Request $request)
    {
        $query = $request->input('query');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = [];

        // 1. Navigation Pages (Static)
        $pages = [
            ['title' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'home'],
            ['title' => 'Clients', 'url' => route('clients.index'), 'icon' => 'users'],
            ['title' => 'Tasks', 'url' => route('tasks.index'), 'icon' => 'check-circle'],
            ['title' => 'Service Dues', 'url' => route('service-dues.index'), 'icon' => 'calendar'],
            ['title' => 'Invoices', 'url' => route('invoices.index'), 'icon' => 'currency-rupee'],
            ['title' => 'Billing Queue', 'url' => route('billing.index'), 'icon' => 'collection'],
            ['title' => 'Reports', 'url' => route('reports.index'), 'icon' => 'chart-bar'],
            ['title' => 'Settings', 'url' => route('settings.index'), 'icon' => 'cog'],
        ];

        foreach ($pages as $page) {
            if (stripos($page['title'], $query) !== false) {
                $results[] = [
                    'category' => 'Navigation',
                    'title' => $page['title'],
                    'url' => $page['url'],
                    'icon' => $page['icon']
                ];
            }
        }

        // 2. Actions (Static)
        $actions = [
            ['title' => 'Create New Client', 'url' => route('clients.create'), 'icon' => 'plus'],
            ['title' => 'Create New Task', 'url' => route('tasks.create'), 'icon' => 'plus'],
            ['title' => 'Create New Invoice', 'url' => route('invoices.create'), 'icon' => 'plus'],
        ];

        foreach ($actions as $action) {
            if (stripos($action['title'], $query) !== false) {
                $results[] = [
                    'category' => 'Actions',
                    'title' => $action['title'],
                    'url' => $action['url'],
                    'icon' => $action['icon']
                ];
            }
        }

        // 3. Application Data (Dynamic)

        // Clients
        $clients = Client::where('name', 'like', "%{$query}%")
            ->orWhere('pan', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        foreach ($clients as $client) {
            $results[] = [
                'category' => 'Clients',
                'title' => $client->name,
                'subtitle' => 'PAN: ' . $client->pan,
                'url' => route('clients.show', $client),
                'icon' => 'user'
            ];
        }

        // Tasks
        $tasks = Task::where('title', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        foreach ($tasks as $task) {
            $results[] = [
                'category' => 'Tasks',
                'title' => $task->title,
                'subtitle' => $task->client ? $task->client->name : 'No Client',
                'url' => route('tasks.edit', $task),
                'icon' => 'clipboard-check'
            ];
        }

        return response()->json($results);
    }
}
