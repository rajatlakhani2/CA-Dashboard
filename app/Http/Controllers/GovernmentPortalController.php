<?php

namespace App\Http\Controllers;

use App\Models\ClientCredential;
use App\Services\GovernmentPortalLauncher;
use App\Support\GovernmentPortals;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GovernmentPortalController extends Controller
{
    public function __construct(
        private GovernmentPortalLauncher $launcher,
    ) {}

    public function clients(Request $request, string $portal): JsonResponse
    {
        $this->authorize('viewAny', ClientCredential::class);
        GovernmentPortals::find($portal);

        $credentials = $this->launcher
            ->credentialsQuery($portal, $request->user())
            ->get()
            ->map(fn (ClientCredential $credential) => [
                'credential_id' => $credential->id,
                'client_id' => $credential->client_id,
                'client_name' => $credential->client?->name ?? 'Unknown client',
                'client_code' => $credential->client?->client_code,
                'group_name' => $credential->client?->group_name,
                'portal_name' => $credential->portal_name,
                'username' => $credential->username,
                'launch_url' => route('gov-portals.launch', ['portal' => $portal, 'credential' => $credential]),
            ])
            ->values();

        return response()->json([
            'portal' => $portal,
            'label' => GovernmentPortals::find($portal)['label'],
            'clients' => $credentials,
        ]);
    }

    public function launch(Request $request, string $portal, ClientCredential $credential): View
    {
        $this->authorize('view', $credential);
        GovernmentPortals::find($portal);

        $credential->recordVaultAccess();

        return view('gov-portals.launch', $this->launcher->buildLaunchPayload($portal, $credential));
    }
}
