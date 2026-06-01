<?php

namespace App\Http\Controllers;

use App\Models\OnboardingChecklist;
use App\Models\Client;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function show(Client $client)
    {
        $this->authorize('view', $client);

        $checklist = OnboardingChecklist::where('client_id', $client->id)->get();

        // If no checklist exists, create default items
        if ($checklist->isEmpty()) {
            foreach (OnboardingChecklist::defaultItems() as $item) {
                OnboardingChecklist::create([
                    'client_id' => $client->id,
                    'item' => $item,
                ]);
            }
            $checklist = OnboardingChecklist::where('client_id', $client->id)->get();
        }

        $progress = $checklist->count() > 0
            ? round(($checklist->where('is_completed', true)->count() / $checklist->count()) * 100)
            : 0;

        return view('onboarding.show', compact('client', 'checklist', 'progress'));
    }

    public function toggle(Request $request, OnboardingChecklist $item)
    {
        $item->update([
            'is_completed' => !$item->is_completed,
            'completed_at' => !$item->is_completed ? now() : null,
            'completed_by' => !$item->is_completed ? auth()->id() : null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'is_completed' => $item->is_completed]);
        }
        return back()->with('success', 'Checklist updated.');
    }

    public function addItem(Request $request, Client $client)
    {
        $request->validate(['item' => 'required|string|max:255']);

        OnboardingChecklist::create([
            'client_id' => $client->id,
            'item' => $request->item,
        ]);

        return back()->with('success', 'Item added to checklist.');
    }
}
