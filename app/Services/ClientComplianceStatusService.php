<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ServiceDue;
use Carbon\Carbon;

class ClientComplianceStatusService
{
    /**
     * @return array<int, array{key: string, label: string, status: string, detail: string}>
     */
    public function chips(Client $client): array
    {
        return [
            $this->chipFor($client, 'gst', 'GST', ['%GST%', '%GSTR%']),
            $this->chipFor($client, 'roc', 'ROC', ['%ROC%', '%MCA%', '%Annual Return%']),
            $this->chipFor($client, 'tds', 'TDS', ['%TDS%', '%TCS%']),
        ];
    }

    private function chipFor(Client $client, string $key, string $label, array $patterns): array
    {
        $query = ServiceDue::query()
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $client->id))
            ->whereHas('clientService.service', function ($q) use ($patterns) {
                $q->where(function ($inner) use ($patterns) {
                    foreach ($patterns as $pattern) {
                        $inner->orWhere('name', 'like', $pattern);
                    }
                });
            });

        $overdue = (clone $query)->where('status', ServiceDue::STATUS_OVERDUE)->count();
        if ($overdue > 0) {
            return ['key' => $key, 'label' => $label, 'status' => 'red', 'detail' => "{$overdue} overdue"];
        }

        $dueSoon = (clone $query)
            ->where('status', ServiceDue::STATUS_PENDING)
            ->whereDate('due_date', '<=', Carbon::today()->addDays(14))
            ->count();
        if ($dueSoon > 0) {
            return ['key' => $key, 'label' => $label, 'status' => 'amber', 'detail' => "{$dueSoon} due soon"];
        }

        $hasAny = (clone $query)->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])->exists();

        return [
            'key' => $key,
            'label' => $label,
            'status' => $hasAny ? 'green' : 'neutral',
            'detail' => $hasAny ? 'On track' : 'No active dues',
        ];
    }
}
