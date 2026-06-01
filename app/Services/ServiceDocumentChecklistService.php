<?php

namespace App\Services;

use App\Models\ClientService;
use App\Models\ClientServiceDocumentCheck;
use App\Models\ServiceDocumentRequirement;
use App\Models\ServiceDue;
use App\Models\User;
use Illuminate\Support\Collection;

class ServiceDocumentChecklistService
{
    public function summaryForClientService(ClientService $clientService): array
    {
        $clientService->loadMissing([
            'service.documentRequirements',
            'documentChecks',
        ]);

        $requirements = $clientService->service?->documentRequirements ?? collect();
        $checksByRequirement = $clientService->documentChecks->keyBy('service_document_requirement_id');

        $items = $requirements->map(function (ServiceDocumentRequirement $requirement) use ($checksByRequirement) {
            $check = $checksByRequirement->get($requirement->id);

            return [
                'requirement_id' => $requirement->id,
                'name' => $requirement->name,
                'is_received' => (bool) ($check?->is_received),
                'received_at' => $check?->received_at?->toDateTimeString(),
            ];
        })->values();

        $total = $items->count();
        $received = $items->where('is_received', true)->count();
        $missing = max(0, $total - $received);

        return [
            'client_service_id' => $clientService->id,
            'service_id' => $clientService->service_id,
            'service_name' => $clientService->service?->name,
            'total' => $total,
            'received' => $received,
            'missing' => $missing,
            'complete' => $total > 0 && $missing === 0,
            'items' => $items->all(),
        ];
    }

    public function summariesForClient(int $clientId): Collection
    {
        return ClientService::query()
            ->where('client_id', $clientId)
            ->where('status', ClientService::STATUS_ACTIVE)
            ->with(['service.documentRequirements', 'documentChecks'])
            ->get()
            ->map(fn (ClientService $cs) => $this->summaryForClientService($cs))
            ->filter(fn (array $summary) => $summary['total'] > 0)
            ->values();
    }

    public function attachToDues(Collection $dues): Collection
    {
        if ($dues->isEmpty()) {
            return $dues;
        }

        $clientServiceIds = $dues->pluck('client_service_id')->filter()->unique()->values();
        $summaries = ClientService::query()
            ->whereIn('id', $clientServiceIds)
            ->with(['service.documentRequirements', 'documentChecks'])
            ->get()
            ->mapWithKeys(fn (ClientService $cs) => [$cs->id => $this->summaryForClientService($cs)]);

        return $dues->each(function (ServiceDue $due) use ($summaries) {
            $due->setAttribute('doc_checklist', $summaries->get($due->client_service_id) ?? [
                'total' => 0,
                'received' => 0,
                'missing' => 0,
                'complete' => true,
                'items' => [],
            ]);
        });
    }

    public function toggleReceived(
        ClientService $clientService,
        ServiceDocumentRequirement $requirement,
        User $user,
        bool $received,
    ): ClientServiceDocumentCheck {
        if ((int) $requirement->service_id !== (int) $clientService->service_id) {
            abort(422, 'Document requirement does not belong to this service.');
        }

        $check = ClientServiceDocumentCheck::firstOrNew([
            'client_service_id' => $clientService->id,
            'service_document_requirement_id' => $requirement->id,
        ]);

        $check->is_received = $received;
        $check->received_at = $received ? now() : null;
        $check->received_by = $received ? $user->id : null;
        $check->save();

        return $check;
    }
}
