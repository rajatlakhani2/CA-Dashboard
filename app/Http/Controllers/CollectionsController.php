<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CollectionFollowUp;
use App\Services\Intelligence\CollectionsCallListBuilder;
use Illuminate\Http\Request;

class CollectionsController extends Controller
{
    public function index(Request $request, CollectionsCallListBuilder $builder)
    {
        abort_unless(auth()->user()?->managesFirmModules() && auth()->user()?->canAccessModule('payments'), 403);

        $bucket = $request->input('bucket');
        $callList = $builder->build($bucket ?: null);
        $bucketCounts = $builder->bucketCounts();
        $highlightClientId = $request->integer('client_id') ?: null;
        $selectedClient = $highlightClientId
            ? Client::find($highlightClientId)
            : $callList->first()?->client;

        return view('collections.index', compact('callList', 'bucketCounts', 'bucket', 'highlightClientId', 'selectedClient'));
    }

    public function storeFollowUp(Request $request, Client $client)
    {
        abort_unless(auth()->user()?->managesFirmModules() && auth()->user()?->canAccessModule('payments'), 403);
        $this->authorize('view', $client);

        $validated = $request->validate([
            'channel' => 'required|in:phone,whatsapp,email,in_person',
            'notes' => 'nullable|string|max:2000',
            'promise_date' => 'nullable|date',
            'next_action' => 'nullable|string|max:255',
            'contacted_at' => 'nullable|date',
        ]);

        CollectionFollowUp::create([
            'client_id' => $client->id,
            'user_id' => auth()->id(),
            'channel' => $validated['channel'],
            'notes' => $validated['notes'] ?? null,
            'promise_date' => $validated['promise_date'] ?? null,
            'next_action' => $validated['next_action'] ?? null,
            'contacted_at' => isset($validated['contacted_at'])
                ? \Carbon\Carbon::parse($validated['contacted_at'])
                : now(),
        ]);

        return redirect()
            ->route('collections.index', ['client_id' => $client->id])
            ->with('success', 'Follow-up logged for ' . $client->name);
    }
}
