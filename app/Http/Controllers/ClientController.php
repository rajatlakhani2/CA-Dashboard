<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClientsExport;
use App\Imports\ClientsImport;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Client::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
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

        $clients = $query->latest()->paginate(10);
        $managers = \App\Models\User::all(); // Fetch all users as managers for now

        return view('clients.index', compact('clients', 'managers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $services = \App\Models\Service::all();
        return view('clients.create', compact('services'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'nullable|string',
            'industry' => 'nullable|string',
            'pan' => 'required|string|unique:clients,pan',
            'gstin' => 'nullable|string|unique:clients,gstin',
            'cin' => 'nullable|string',
            'tan' => 'nullable|string',
            'primary_contact_name' => 'nullable|string',
            'primary_contact_phone' => 'nullable|string',
            'primary_contact_email' => 'nullable|email',
            'category' => 'required|in:A,B,C',
            'status' => 'required|in:Active,On-Hold,Closed',
            'tags' => 'nullable|string',
            'billing_cycle' => 'nullable|string',
            'registered_address' => 'nullable|string',
            'services' => 'array',
            'custom_due_days' => 'array',
        ]);

        if ($request->has('tags') && $request->tags) {
            $validated['tags'] = array_map('trim', explode(',', $request->tags));
        } else {
            $validated['tags'] = []; // Ensure empty array if no tags
        }

        // Auto-generate Client Code (e.g., CL-0001)
        $lastClient = Client::latest('id')->first();
        $nextId = $lastClient ? $lastClient->id + 1 : 1;
        $clientCode = 'CL-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $client = new Client($validated);
        $client->client_code = $clientCode;
        $client->save();

        // Sync Services with Custom Due Days
        if ($request->has('services')) {
            $services = $request->input('services', []);
            $customDueDays = $request->input('custom_due_days', []);
            $syncData = [];

            foreach ($services as $serviceId) {
                $syncData[$serviceId] = [
                    'custom_due_day' => $customDueDays[$serviceId] ?? null
                ];
            }
            $client->optedServices()->sync($syncData);
        }

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        $client->load(['manager', 'optedServices', 'invoices' => function ($q) {
            $q->latest()->take(5);
        }, 'tasks' => function ($q) {
            $q->latest()->take(5);
        }]);

        // Financial Stats
        $totalBilled = $client->invoices()->sum('total_amount');
        $totalCollected = $client->invoices()->where('status', 'Paid')->sum('total_amount');
        $totalOutstanding = $client->invoices()->where('status', 'Overdue')->sum('total_amount');

        // Compliance Stats
        $serviceDues = \App\Models\ServiceDue::whereHas('clientService', function ($q) use ($client) {
            $q->where('client_id', $client->id);
        })->whereIn('status', ['Pending', 'Overdue'])->orderBy('due_date')->get();

        $activeTasks = $client->tasks()->whereIn('status', ['Pending', 'In Progress'])->get();

        return view('clients.show', compact('client', 'totalBilled', 'totalCollected', 'totalOutstanding', 'serviceDues', 'activeTasks'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        $services = \App\Models\Service::all();
        $optedServices = $client->optedServices()->get()->keyBy('id');
        return view('clients.edit', compact('client', 'services', 'optedServices'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'pan' => 'required|string|unique:clients,pan,' . $client->id,
            'gstin' => 'nullable|string|unique:clients,gstin,' . $client->id,
            'category' => 'required|in:A,B,C',
            'status' => 'required|in:Active,On-Hold,Closed',
            'tags' => 'nullable|string',
            'entity_type' => 'nullable|string',
            'industry' => 'nullable|string',
            'billing_cycle' => 'nullable|string',
            'primary_contact_name' => 'nullable|string',
            'primary_contact_phone' => 'nullable|string',
            'primary_contact_email' => 'nullable|email',
            'registered_address' => 'nullable|string',
            'services' => 'array',
            'custom_due_days' => 'array',
        ]);

        if ($request->has('tags')) {
            $validated['tags'] = $request->tags ? array_map('trim', explode(',', $request->tags)) : [];
        }

        $client->update($validated);

        // Sync Services with Custom Due Days
        $services = $request->input('services', []);
        $customDueDays = $request->input('custom_due_days', []);

        $syncData = [];
        foreach ($services as $serviceId) {
            $syncData[$serviceId] = [
                'custom_due_day' => $customDueDays[$serviceId] ?? null
            ];
        }

        $client->optedServices()->sync($syncData);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    public function export()
    {
        return Excel::download(new ClientsExport, 'clients.xlsx');
    }

    public function downloadTemplate()
    {
        return Excel::download(new \App\Exports\ClientTemplateExport, 'client_import_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $import = new ClientsImport;
        Excel::import($import, $request->file('file'));

        $failures = $import->failures();

        if ($failures->count() > 0) {
            return redirect()->route('clients.index')->with('warning', 'Some clients were imported, but ' . $failures->count() . ' rows failed validation. Check logs for details.');
        }

        return redirect()->route('clients.index')->with('success', 'Clients imported successfully.');
    }
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'selected_clients' => 'required|array',
            'selected_clients.*' => 'exists:clients,id',
        ]);

        Client::whereIn('id', $request->selected_clients)->delete();

        return redirect()->route('clients.index')->with('success', 'Selected clients deleted successfully.');
    }
}
