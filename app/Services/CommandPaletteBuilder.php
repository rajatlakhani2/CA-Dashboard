<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class CommandPaletteBuilder
{
    public function defaults(User $user): array
    {
        $actions = collect($this->actions($user))
            ->filter(fn (array $action) => $this->canRunAction($user, $action))
            ->take(8)
            ->map(fn (array $action) => $this->formatItem($action, 'Actions'))
            ->values();

        $navigation = collect($this->navigationPages($user))
            ->filter(fn (array $page) => $user->canAccessModule($page['module']))
            ->take(6)
            ->map(fn (array $page) => $this->formatItem($page, 'Navigation'))
            ->values();

        return [
            'actions' => $actions,
            'navigation' => $navigation,
        ];
    }

    public function search(User $user, string $query): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        $mode = 'all';
        if (str_starts_with($query, '#')) {
            $mode = 'actions';
            $query = ltrim(substr($query, 1));
        } elseif (str_starts_with($query, '>')) {
            $mode = 'clients';
            $query = ltrim(substr($query, 1));
        }

        if (strlen($query) < 2) {
            return [];
        }

        $results = [];

        if ($mode === 'all' || $mode === 'actions') {
            foreach ($this->actions($user) as $action) {
                if ($this->matches($action['title'], $query) && $this->canRunAction($user, $action)) {
                    $results[] = $this->formatItem($action, 'Actions');
                }
            }
        }

        if ($mode === 'all') {
            foreach ($this->navigationPages($user) as $page) {
                if ($this->matches($page['title'], $query) && $user->canAccessModule($page['module'])) {
                    $results[] = $this->formatItem($page, 'Navigation');
                }
            }
        }

        if (($mode === 'all' || $mode === 'clients') && $user->canAccessModule('clients') && ! $user->isArticle()) {
            $results = array_merge($results, $this->searchClients($user, $query));
        }

        if ($mode === 'all' && $user->canAccessModule('tasks')) {
            $results = array_merge($results, $this->searchTasks($user, $query));
        }

        if ($mode === 'all' && ($user->canAccessModule('invoices') || $user->canViewPortfolioInvoices())) {
            $results = array_merge($results, $this->searchInvoices($user, $query));
        }

        return $this->grouped($results);
    }

    private function formatItem(array $item, string $category): array
    {
        return [
            'category' => $category,
            'title' => $item['title'],
            'subtitle' => $item['subtitle'] ?? null,
            'url' => $item['url'],
            'icon' => $item['icon'],
        ];
    }

    private function grouped(array $results): array
    {
        return collect($results)
            ->groupBy('category')
            ->map(fn (Collection $items, string $category) => [
                'category' => $category,
                'items' => $items->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function searchClients(User $user, string $query): array
    {
        return Client::query()
            ->visibleTo($user)
            ->where(function ($clientQuery) use ($query) {
                $clientQuery->where('name', 'like', "%{$query}%")
                    ->orWhere('pan', 'like', "%{$query}%")
                    ->orWhere('client_code', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn (Client $client) => [
                'category' => 'Clients',
                'title' => $client->name,
                'subtitle' => trim('PAN: ' . ($client->pan ?: '—') . ' · ' . $client->client_code),
                'url' => route('clients.show', $client),
                'icon' => 'user',
            ])
            ->all();
    }

    private function searchTasks(User $user, string $query): array
    {
        $taskQuery = Task::query()->with('client')->where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%");
        });

        if ($user->isArticle()) {
            $taskQuery->where('assigned_to', $user->id);
        } elseif (! $user->managesFirmModules()) {
            $taskQuery->where(function ($builder) use ($user) {
                $builder->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        return $taskQuery->limit(5)->get()->map(fn (Task $task) => [
            'category' => 'Tasks',
            'title' => $task->title,
            'subtitle' => $task->client?->name ?? 'No client',
            'url' => $user->isArticle() ? route('tasks.my-day') : route('tasks.edit', $task),
            'icon' => 'clipboard-check',
        ])->all();
    }

    private function searchInvoices(User $user, string $query): array
    {
        $invoiceQuery = Invoice::query()->whereHas('client')
            ->where('invoice_number', 'like', "%{$query}%");

        if ($user->isAssociate()) {
            $invoiceQuery->whereHas('client', fn ($c) => $c->where('manager_id', $user->id));
        } elseif ($user->isManager() && $user->branch_id) {
            $invoiceQuery->where('branch_id', $user->branch_id);
        }

        return $invoiceQuery->limit(3)->get()->map(fn (Invoice $invoice) => [
            'category' => 'Invoices',
            'title' => $invoice->invoice_number,
            'subtitle' => '₹' . number_format($invoice->total_amount, 0),
            'url' => route('invoices.show', $invoice),
            'icon' => 'currency-rupee',
        ])->all();
    }

    public function navigationPages(User $user): array
    {
        $pages = [];

        if ($user->canAccessModule('dashboard')) {
            $pages[] = ['title' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'home', 'module' => 'dashboard'];
        }
        if ($user->isPartner()) {
            $pages[] = ['title' => 'Partner Overview', 'url' => route('partner.dashboard'), 'icon' => 'chart-bar', 'module' => 'dashboard'];
        }
        if ($user->canAccessModule('tasks')) {
            $pages[] = ['title' => 'My Day', 'url' => route('tasks.my-day'), 'icon' => 'sun', 'module' => 'tasks'];
            $pages[] = ['title' => 'Tasks', 'url' => route('tasks.index'), 'icon' => 'check-circle', 'module' => 'tasks'];
        }
        if ($user->canAccessModule('clients')) {
            $pages[] = ['title' => 'Clients', 'url' => route('clients.index'), 'icon' => 'users', 'module' => 'clients'];
        }
        if ($user->canAccessModule('service_dues')) {
            $pages[] = ['title' => 'Service Dues', 'url' => route('service-dues.index'), 'icon' => 'calendar', 'module' => 'service_dues'];
        }
        if ($user->canAccessModule('billing')) {
            $pages[] = ['title' => 'Billing Queue', 'url' => route('billing.index'), 'icon' => 'collection', 'module' => 'billing'];
        }
        if ($user->canAccessModule('invoices') || $user->canViewPortfolioInvoices()) {
            $pages[] = ['title' => 'Invoices', 'url' => route('invoices.index'), 'icon' => 'currency-rupee', 'module' => 'invoices'];
        }
        if ($user->canAccessModule('payments')) {
            $pages[] = ['title' => 'Payments', 'url' => route('payments.index'), 'icon' => 'cash', 'module' => 'payments'];
            if ($user->managesFirmModules()) {
                $pages[] = ['title' => 'Collections', 'url' => route('collections.index'), 'icon' => 'phone', 'module' => 'payments'];
            }
        }
        if ($user->canAccessModule('reports')) {
            $pages[] = ['title' => 'Reports', 'url' => route('reports.index'), 'icon' => 'chart-bar', 'module' => 'reports'];
        }
        if ($user->managesFirmModules() && $user->canAccessModule('tasks')) {
            $pages[] = ['title' => 'Workload Planner', 'url' => route('workload.index'), 'icon' => 'view-boards', 'module' => 'tasks'];
        }

        return $pages;
    }

    public function actions(User $user): array
    {
        $actions = [];

        if ($user->can('create', Client::class)) {
            $actions[] = [
                'title' => $user->isArticle() ? 'Submit New Client' : 'Create New Client',
                'url' => route('clients.create'),
                'icon' => 'plus',
                'gate' => 'create_client',
            ];
        }
        if ($user->can('create', Task::class)) {
            $actions[] = ['title' => 'Create New Task', 'url' => route('tasks.create'), 'icon' => 'plus', 'gate' => 'create_task'];
        }
        if ($user->can('create', Invoice::class)) {
            $actions[] = ['title' => 'Create New Invoice', 'url' => route('invoices.create'), 'icon' => 'plus', 'gate' => 'create_invoice'];
        }
        if ($user->canAccessModule('payments') && $user->managesFirmModules()) {
            $actions[] = ['title' => 'Log Payment', 'url' => route('payments.create'), 'icon' => 'cash', 'gate' => 'create_payment'];
        }
        if ($user->canAccessModule('tasks')) {
            $actions[] = ['title' => 'Open My Day', 'url' => route('tasks.my-day'), 'icon' => 'sun', 'gate' => 'my_day'];
        }
        if ($user->managesFirmModules() && $user->canAccessModule('payments')) {
            $actions[] = ['title' => 'Collections Center', 'url' => route('collections.index'), 'icon' => 'phone', 'gate' => 'collections'];
        }

        return $actions;
    }

    public function canRunAction(User $user, array $action): bool
    {
        return match ($action['gate'] ?? '') {
            'create_client' => $user->can('create', Client::class),
            'create_task' => $user->can('create', Task::class),
            'create_invoice' => $user->can('create', Invoice::class),
            'create_payment', 'collections' => $user->canAccessModule('payments') && $user->managesFirmModules(),
            'my_day' => $user->canAccessModule('tasks'),
            default => false,
        };
    }

    private function matches(string $title, string $query): bool
    {
        return stripos($title, $query) !== false;
    }
}
