<?php

namespace App\Http\Controllers;

use App\Models\ClientCredential;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientCredentialController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ClientCredential::class);

        $query = ClientCredential::with(['client', 'lastAccessedBy']);
        $this->scopeCredentialsToUser($query);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('portal_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('group_name', 'like', "%{$search}%");
                    });
            });
        }

        $credentials = $query->latest()->paginate(15);

        return view('credentials.index', [
            'credentials' => $credentials,
            'categories' => \App\Models\ClientCredential::CATEGORIES,
        ]);
    }

    public function store(\App\Http\Requests\StoreCredentialRequest $request)
    {
        $this->authorize('create', ClientCredential::class);

        $validated = $request->validated();

        $this->authorize('createForClient', [ClientCredential::class, Client::findOrFail($validated['client_id'])]);

        $validated['category'] = $validated['category'] ?? ClientCredential::CATEGORY_OTHER;

        $credential = ClientCredential::create($validated);

        return back()->with('success', 'Credential added successfully.');
    }

    public function audit(\App\Http\Requests\CredentialAuditRequest $request, ClientCredential $credential)
    {
        $this->authorize('view', $credential);

        $validated = $request->validated();

        $credential->logVaultAction($validated['action']);

        return response()->json(['success' => true]);
    }

    public function destroy(ClientCredential $credential)
    {
        $this->authorize('delete', $credential);

        $credential->delete();
        return back()->with('success', 'Credential deleted successfully.');
    }

    private function scopeCredentialsToUser($query): void
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
}
