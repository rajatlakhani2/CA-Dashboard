<?php

namespace App\Http\Controllers;

use App\Models\Dsc;
use App\Models\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DscController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Dsc::class);

        $query = Dsc::with('client')->whereHas('client');
        $this->scopeDscsToUser($query);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Auto-mark expired
        $expiredQuery = Dsc::where('status', Dsc::STATUS_ACTIVE)
            ->where('expiry_date', '<', Carbon::today())
            ->whereHas('client');
        $this->scopeDscsToUser($expiredQuery);
        $expiredQuery->update(['status' => Dsc::STATUS_EXPIRED]);

        $dscs = $query->orderBy('expiry_date')->paginate(20);
        $expiringSoonQuery = Dsc::where('status', Dsc::STATUS_ACTIVE)
            ->where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->where('expiry_date', '>=', Carbon::today());
        $this->scopeDscsToUser($expiringSoonQuery);
        $expiringSoonCount = $expiringSoonQuery->count();

        $clients = $this->clientOptionsQuery()->orderBy('name')->get();

        return view('dscs.index', compact('dscs', 'expiringSoonCount', 'clients'));
    }

    public function create()
    {
        $this->authorize('create', Dsc::class);

        $clients = $this->clientOptionsQuery()->orderBy('name')->get();
        return view('dscs.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Dsc::class);

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'holder_name' => 'required|string|max:255',
            'class_type' => 'required|in:Class 2,Class 3',
            'provider' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'notes' => 'nullable|string',
        ]);

        $this->authorize('createForClient', [Dsc::class, Client::findOrFail($request->client_id)]);

        Dsc::create($request->all());
        return redirect()->route('dscs.index')->with('success', 'DSC added successfully.');
    }

    public function edit(Dsc $dsc)
    {
        $this->authorize('update', $dsc);

        $clients = $this->clientOptionsQuery()->orderBy('name')->get();
        return view('dscs.edit', compact('dsc', 'clients'));
    }

    public function update(Request $request, Dsc $dsc)
    {
        $this->authorize('update', $dsc);

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'holder_name' => 'required|string|max:255',
            'class_type' => 'required|in:Class 2,Class 3',
            'expiry_date' => 'required|date',
        ]);

        $this->authorize('createForClient', [Dsc::class, Client::findOrFail($request->client_id)]);

        $dsc->update($request->all());
        return redirect()->route('dscs.index')->with('success', 'DSC updated.');
    }

    public function destroy(Dsc $dsc)
    {
        $this->authorize('delete', $dsc);

        $dsc->delete();
        return redirect()->route('dscs.index')->with('success', 'DSC deleted.');
    }

    private function scopeDscsToUser($query): void
    {
        $user = auth()->user();

        if (! $user?->isManager() || ! $user->branch_id) {
            return;
        }

        $query->whereHas('client', function ($q) use ($user) {
            $q->whereNull('branch_id')
                ->orWhere('branch_id', $user->branch_id);
        });
    }

    private function clientOptionsQuery()
    {
        $query = Client::query();
        $user = auth()->user();

        if ($user?->isManager() && $user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        }

        return $query;
    }
}
