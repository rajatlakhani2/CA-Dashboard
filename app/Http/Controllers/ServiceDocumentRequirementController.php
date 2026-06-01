<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceDocumentRequirement;
use Illuminate\Http\Request;

class ServiceDocumentRequirementController extends Controller
{
    public function store(Request $request, Service $service)
    {
        $this->authorizeServiceMaster();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $nextOrder = (int) $service->documentRequirements()->max('sort_order') + 1;

        $service->documentRequirements()->create([
            'name' => $validated['name'],
            'sort_order' => $nextOrder,
        ]);

        return redirect()
            ->route('services.index')
            ->with('success', 'Document requirement added to ' . $service->name . '.');
    }

    public function destroy(ServiceDocumentRequirement $documentRequirement)
    {
        $this->authorizeServiceMaster();

        $name = $documentRequirement->name;
        $documentRequirement->delete();

        return redirect()
            ->route('services.index')
            ->with('success', "Removed document requirement \"{$name}\".");
    }

    private function authorizeServiceMaster(): void
    {
        if (! auth()->user()?->managesFirmModules()) {
            abort(403);
        }
    }
}
