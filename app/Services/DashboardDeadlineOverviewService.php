<?php

namespace App\Services;

use App\Models\ServiceDue;
use App\Models\User;
use App\Support\ModuleGate;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardDeadlineOverviewService
{
    /**
     * Service-level deadline buckets for the next calendar month (ITR-style rollups).
     *
     * @return array<int, array<string, mixed>>
     */
    public function monthlyServiceDeadlines(?User $user): array
    {
        if (! ModuleGate::allowed($user, 'service_dues')) {
            return [];
        }

        $today = Carbon::today();
        $end = $today->copy()->addMonth()->endOfMonth();

        $dues = ServiceDue::query()
            ->with(['clientService.client', 'clientService.service'])
            ->whereBetween('due_date', [$today, $end])
            ->whereHas('clientService.service')
            ->whereHas('clientService.client')
            ->get();

        return $dues
            ->groupBy(fn (ServiceDue $due) => $due->clientService->service_id.'|'.$due->due_date->format('Y-m-d'))
            ->map(function (Collection $items, string $groupKey) {
                /** @var ServiceDue $first */
                $first = $items->first();
                $service = $first->clientService->service;
                $completed = $items->where('status', ServiceDue::STATUS_COMPLETED)->count();
                $total = $items->count();
                $pending = $items->whereIn('status', [
                    ServiceDue::STATUS_PENDING,
                    ServiceDue::STATUS_OVERDUE,
                ])->count();

                $clients = $items
                    ->map(fn (ServiceDue $due) => [
                        'id' => (int) $due->clientService->client_id,
                        'name' => $due->clientService->client?->name ?? 'Client',
                        'status' => $due->status,
                        'due_date' => $due->due_date->format('d M'),
                        'url' => route('clients.show', $due->clientService->client_id),
                    ])
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();

                return [
                    'key' => $groupKey,
                    'service_id' => (int) $service->id,
                    'service_name' => $service->name,
                    'due_date' => $first->due_date->format('Y-m-d'),
                    'due_label' => $first->due_date->format('j M Y'),
                    'total' => $total,
                    'completed' => $completed,
                    'pending' => $pending,
                    'progress_pct' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
                    'url' => route('service-dues.index'),
                    'clients' => $clients,
                ];
            })
            ->sortBy('due_date')
            ->values()
            ->take(8)
            ->all();
    }
}
