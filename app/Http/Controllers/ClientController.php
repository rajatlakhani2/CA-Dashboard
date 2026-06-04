<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClientsExport;
use App\Models\ServiceDue;
use App\Models\Task;
use App\Models\User;
use App\Mail\ClientPendingApprovalMail;
use Illuminate\Support\Facades\Mail;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query()
            ->visibleTo(auth()->user())
            ->with('manager')
            ->withCount(['tasks as open_tasks_count' => function ($q) {
                $q->whereNotIn('status', Task::TERMINAL_STATUSES);
            }]);

        if (auth()->user()?->isPartner()) {
            $query->where('approval_status', Client::APPROVAL_APPROVED);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('group_name', 'like', "%{$search}%")
                    ->orWhere('pan', 'like', "%{$search}%")
                    ->orWhere('client_code', 'like', "%{$search}%")
                    ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->input('manager_id'));
        }

        if ($request->filled('tag')) {
            // Using JSON_CONTAINS for JSON column (MySQL specific, but common in Laravel)
            $query->whereJsonContains('tags', $request->input('tag'));
        }

        $clients = $query->latest()->paginate(12);
        $managers = \App\Models\User::all();
        $healthService = app(\App\Services\ClientHealthScoreService::class);
        $clientHealthMap = $clients->getCollection()->mapWithKeys(fn (Client $c) => [
            $c->id => $healthService->forClient($c),
        ]);

        $panLookupHint = null;
        if ($request->filled('search') && $clients->total() === 0) {
            $panLookupHint = $this->panLookupHint((string) $request->input('search'));
        }

        $pendingClients = collect();
        if (auth()->user()?->isPartner()) {
            $pendingClients = Client::query()
                ->where('approval_status', Client::APPROVAL_PENDING)
                ->with('createdBy')
                ->latest()
                ->get();
        }

        return view('clients.index', compact('clients', 'managers', 'pendingClients', 'panLookupHint', 'clientHealthMap'));
    }

    /**
     * @return array{type: string, name: string, code: ?string, action_url: ?string, action_label: ?string}|null
     */
    protected function panLookupHint(string $search): ?array
    {
        $term = strtoupper(trim($search));
        if (! preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $term)) {
            return null;
        }

        $trashed = Client::onlyTrashed()->whereRaw('UPPER(TRIM(pan)) = ?', [$term])->first();
        if ($trashed) {
            return [
                'type' => 'trashed',
                'name' => $trashed->name,
                'code' => $trashed->client_code,
                'action_url' => route('recycle-bin.index'),
                'action_label' => 'Open Recycle Bin',
            ];
        }

        $pending = Client::query()
            ->whereRaw('UPPER(TRIM(pan)) = ?', [$term])
            ->where('approval_status', Client::APPROVAL_PENDING)
            ->first();

        if ($pending) {
            return [
                'type' => 'pending',
                'name' => $pending->name,
                'code' => $pending->client_code,
                'action_url' => null,
                'action_label' => null,
            ];
        }

        $hidden = Client::findByPan($term, false);
        $user = auth()->user();
        if ($hidden && $user) {
            $visible = Client::query()
                ->visibleTo($user)
                ->when($user->isPartner(), fn ($q) => $q->where('approval_status', Client::APPROVAL_APPROVED))
                ->where('id', $hidden->id)
                ->exists();

            if (! $visible) {
                return [
                    'type' => 'hidden',
                    'name' => $hidden->name,
                    'code' => $hidden->client_code,
                    'action_url' => $user->can('update', $hidden) ? route('clients.edit', $hidden) : null,
                    'action_label' => 'Open client',
                ];
            }
        }

        return null;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Client::class);

        $services = \App\Models\Service::all();
        return view('clients.create', compact('services'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\StoreClientRequest $request)
    {
        $this->authorize('create', Client::class);

        $validated = $request->validated();
        $validated['pan'] = strtoupper(trim($validated['pan']));

        $trashed = Client::onlyTrashed()->whereRaw('UPPER(TRIM(pan)) = ?', [$validated['pan']])->first();
        if ($trashed) {
            $trashed->restore();
            $trashed->fill(collect($validated)->except(['services', 'custom_due_days'])->all());
            $trashed->approval_status = Client::APPROVAL_APPROVED;
            $trashed->approved_at = now();
            $trashed->approved_by_user_id = auth()->id();
            $trashed->save();

            if ($request->has('services')) {
                $this->syncClientServices($trashed, $request->input('services', []), $request->input('custom_due_days', []));
            }

            return redirect()
                ->route('clients.edit', $trashed)
                ->with('success', "Client \"{$trashed->name}\" was in the Recycle Bin and has been restored.");
        }

        if ($request->has('tags') && $request->tags) {
            $validated['tags'] = array_map('trim', explode(',', $request->tags));
        } else {
            $validated['tags'] = []; // Ensure empty array if no tags
        }

        // Auto-generate Client Code
        $lastClient = Client::latest('id')->first();
        $nextId = $lastClient ? $lastClient->id + 1 : 1;
        
        $prefix = 'CL';
        if (!empty($validated['group_name'])) {
            $words = explode(' ', trim($validated['group_name']));
            $prefix = strtoupper(substr($words[0], 0, 6));
        }
        
        $clientCode = $prefix . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $user = auth()->user();
        $client = new Client($validated);
        $client->client_code = $clientCode;
        $client->created_by_user_id = $user->id;

        if ($user->isArticle()) {
            $client->approval_status = Client::APPROVAL_PENDING;
        } else {
            $client->approval_status = Client::APPROVAL_APPROVED;
            $client->approved_at = now();
            $client->approved_by_user_id = $user->isPartner() ? $user->id : null;
        }

        if ($user->isAssociate()) {
            $client->manager_id = $user->id;
        }

        if ($user->isManager() && $user->branch_id) {
            $client->branch_id = $user->branch_id;
        }

        $client->save();

        if ($request->has('services')) {
            $this->syncClientServices($client, $request->input('services', []), $request->input('custom_due_days', []));
        }

        if ($user->isArticle()) {
            $this->notifyPartnersOfPendingClient($client);

            return redirect()
                ->route('tasks.index')
                ->with('success', 'Client submitted successfully. Rajat will review and approve it before it appears for everyone.');
        }

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function approve(Client $client)
    {
        $this->authorize('approve', $client);

        $client->update([
            'approval_status' => Client::APPROVAL_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('clients.index')
            ->with('success', $client->name . ' is now approved and visible across the firm.');
    }

    private function notifyPartnersOfPendingClient(Client $client): void
    {
        $client->loadMissing('createdBy');

        User::query()
            ->where('role', 'partner')
            ->whereNotNull('email')
            ->pluck('email')
            ->each(function (string $email) use ($client) {
                try {
                    Mail::to($email)->send(new ClientPendingApprovalMail($client));
                } catch (\Throwable $e) {
                    report($e);
                }
            });
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        $this->authorize('view', $client);

        $client->load([
            'manager',
            'optedServices.taskTemplates' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            'invoices' => fn ($q) => $q->latest()->take(5),
            'tasks' => fn ($q) => $q->latest()->take(5),
        ]);

        // Financial Stats
        $totalBilled = $client->invoices()->sum('total_amount');
        $totalCollected = (float) \App\Models\Payment::whereHas('invoice', function ($q) use ($client) {
            $q->where('client_id', $client->id);
        })->sum('amount');
        $totalOutstanding = max(0, (float) $totalBilled - $totalCollected);

        // Compliance Stats
        $serviceDues = \App\Models\ServiceDue::whereHas('clientService', function ($q) use ($client) {
            $q->where('client_id', $client->id);
        })->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])->orderBy('due_date')->get();

        $activeTasks = $client->tasks()->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS])->get();

        $unbilledWorksheets = $client->worksheets()->where('is_billed', false)->latest()->get();

        $nextDue = $serviceDues->first();
        $lastInvoice = $client->invoices->first();

        $documentChecklists = app(\App\Services\ServiceDocumentChecklistService::class)
            ->summariesForClient($client->id);

        $timeline = app(\App\Services\Intelligence\ClientTimelineBuilder::class)->build($client);
        $complianceRisks = \App\Models\ComplianceRiskScore::query()
            ->where('client_id', $client->id)
            ->whereIn('level', [\App\Models\ComplianceRiskScore::LEVEL_HIGH, \App\Models\ComplianceRiskScore::LEVEL_MEDIUM])
            ->with('service')
            ->orderByDesc('score')
            ->limit(5)
            ->get();

        $clientHealth = app(\App\Services\ClientHealthScoreService::class)->forClient($client);

        $activePortalToken = \App\Models\ClientPortalToken::query()
            ->where('client_id', $client->id)
            ->where('expires_at', '>', now())
            ->orderByDesc('expires_at')
            ->first();

        return view('clients.show', compact(
            'client',
            'totalBilled',
            'totalCollected',
            'totalOutstanding',
            'serviceDues',
            'activeTasks',
            'unbilledWorksheets',
            'nextDue',
            'lastInvoice',
            'timeline',
            'complianceRisks',
            'documentChecklists',
            'clientHealth',
            'activePortalToken',
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        $this->authorize('update', $client);

        $services = \App\Models\Service::all();
        $optedServices = $client->optedServices()->get()->keyBy('id');
        return view('clients.edit', compact('client', 'services', 'optedServices'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\UpdateClientRequest $request, Client $client)
    {
        $this->authorize('update', $client);

        $validated = $request->validated();

        if ($request->has('tags')) {
            $validated['tags'] = $request->tags ? array_map('trim', explode(',', $request->tags)) : [];
        }

        $client->update($validated);

        if ($request->has('services')) {
            $this->syncClientServices($client, $request->input('services', []), $request->input('custom_due_days', []));
        }

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    /**
     * @param  list<int|string>  $services
     * @param  array<int|string, mixed>  $customDueDays
     */
    protected function syncClientServices(Client $client, array $services, array $customDueDays): void
    {
        $syncData = [];
        foreach ($services as $serviceId) {
            $syncData[$serviceId] = [
                'custom_due_day' => $customDueDays[$serviceId] ?? null,
            ];
        }

        $client->optedServices()->sync($syncData);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client, \App\Services\SensitiveActionLogger $audit)
    {
        $this->authorize('delete', $client);

        $audit->clientDeleted($client);
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    public function export()
    {
        $this->authorize('export', Client::class);

        return Excel::download(new ClientsExport(auth()->user()), 'clients.xlsx');
    }

    public function downloadTemplate()
    {
        $this->authorize('import', Client::class);

        return Excel::download(new \App\Exports\ClientTemplateExport, 'client_import_template.xlsx');
    }

    public function import(Request $request)
    {
        return redirect()
            ->route('clients.index')
            ->with('warning', 'Use Preview import on the clients list — review rows before confirming.');
    }
    public function bulkDestroy(\App\Http\Requests\BulkDeleteClientsRequest $request, \App\Services\SensitiveActionLogger $audit)
    {
        $this->authorize('bulkDelete', Client::class);

        $ids = $request->validated('selected_clients');
        $clients = Client::whereIn('id', $ids)->get();

        foreach ($clients as $client) {
            $this->authorize('delete', $client);
            $client->delete();
        }

        $audit->clientsBulkDeleted($ids);

        return redirect()->route('clients.index')->with('success', 'Selected clients deleted successfully.');
    }

    public function purgeByGroup(Request $request, \App\Services\SensitiveActionLogger $audit)
    {
        $this->authorize('bulkDelete', Client::class);

        if (! auth()->user()?->isPartner()) {
            abort(403);
        }

        $validated = $request->validate([
            'group_name' => 'required|string|max:255',
            'confirm' => 'required|in:DELETE',
        ]);

        $groupName = $validated['group_name'];
        $clients = Client::query()
            ->where('group_name', $groupName)
            ->get();

        if ($clients->isEmpty()) {
            return redirect()
                ->route('clients.index')
                ->with('warning', "No clients found with reference \"{$groupName}\".");
        }

        $ids = [];
        foreach ($clients as $client) {
            $client->delete();
            $ids[] = $client->id;
        }

        $audit->clientsBulkDeleted($ids);

        return redirect()
            ->route('clients.index')
            ->with('success', count($ids).' client(s) with reference "'.$groupName.'" were deleted. You can import fresh data now.');
    }
}
