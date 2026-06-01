<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Service;
use App\Models\TaskTemplate;
use App\Services\TaskTemplateSpawner;
use Illuminate\Http\Request;

class TaskTemplateController extends Controller
{
    public function store(Request $request, Service $service)
    {
        abort_unless(auth()->user()?->managesFirmModules(), 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_days_offset' => 'required|integer|min:0|max:365',
            'priority' => 'required|in:High,Medium,Normal,Low',
        ]);

        $service->taskTemplates()->create(array_merge($data, [
            'sort_order' => (int) $service->taskTemplates()->max('sort_order') + 1,
        ]));

        return back()->with('success', 'Task template added.');
    }

    public function destroy(TaskTemplate $taskTemplate)
    {
        abort_unless(auth()->user()?->managesFirmModules(), 403);
        $taskTemplate->delete();

        return back()->with('success', 'Task template removed.');
    }

    public function spawn(Service $service, Client $client, TaskTemplateSpawner $spawner)
    {
        abort_unless(auth()->user()?->managesFirmModules(), 403);

        $count = $spawner->spawnForClient($service, $client);

        if ($count === 0) {
            return back()->with('warning', 'No active templates for this service.');
        }

        return redirect()
            ->route('tasks.index', ['client_id' => $client->id])
            ->with('success', "{$count} task(s) created from template.");
    }
}
