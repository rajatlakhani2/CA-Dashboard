<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Service;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;
use Carbon\Carbon;

class TaskTemplateSpawner
{
    public function spawnForClient(Service $service, Client $client, ?User $creator = null): int
    {
        $templates = TaskTemplate::query()
            ->where('service_id', $service->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($templates->isEmpty()) {
            return 0;
        }

        $creator = $creator ?? auth()->user();
        $created = 0;

        foreach ($templates as $template) {
            Task::create([
                'client_id' => $client->id,
                'title' => $template->title,
                'description' => $template->description,
                'due_date' => Carbon::today()->addDays($template->due_days_offset),
                'priority' => $template->priority,
                'status' => Task::STATUS_PENDING,
                'assigned_to' => $client->manager_id,
                'created_by' => $creator?->id,
            ]);
            $created++;
        }

        return $created;
    }
}
