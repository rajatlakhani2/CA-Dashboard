<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ServiceDocumentRequirement;
use App\Services\ServiceDocumentChecklistService;
use Illuminate\Http\Request;

class ClientServiceDocumentController extends Controller
{
    public function toggle(
        Request $request,
        Client $client,
        ClientService $clientService,
        ServiceDocumentRequirement $requirement,
        ServiceDocumentChecklistService $checklists,
    ) {
        $this->authorize('view', $client);

        if ((int) $clientService->client_id !== (int) $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'received' => 'required|boolean',
        ]);

        $checklists->toggleReceived(
            $clientService,
            $requirement,
            $request->user(),
            (bool) $validated['received'],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'summary' => $checklists->summaryForClientService($clientService->fresh([
                    'service.documentRequirements',
                    'documentChecks',
                ])),
            ]);
        }

        return back()->with('success', 'Document checklist updated.');
    }
}
