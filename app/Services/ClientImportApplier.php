<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientService;
use Illuminate\Support\Facades\DB;

class ClientImportApplier
{
    public function __construct(
        protected ClientImportPreviewService $previewService,
    ) {}

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function apply(string $absolutePath, ?int $branchId = null): array
    {
        $preview = $this->previewService->preview($absolutePath, $branchId);

        if (count($preview['invalid']) > 0) {
            throw new \InvalidArgumentException('Cannot import while invalid rows exist.');
        }

        $created = 0;
        $updated = 0;
        $nextId = (Client::max('id') ?? 0) + 1;

        DB::transaction(function () use ($preview, $branchId, &$created, &$updated, &$nextId) {
            foreach ($preview['create'] as $row) {
                $client = Client::create($this->attributesFromRow($row, $branchId, $nextId));
                $this->syncServices($client, $row);
                $created++;
                $nextId++;
            }

            foreach ($preview['update'] as $row) {
                $client = Client::find($row['existing_id']);
                if (! $client) {
                    continue;
                }

                $client->update($this->attributesFromRow($row, $branchId, null, false));
                $this->syncServices($client, $row);
                $updated++;
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function attributesFromRow(array $row, ?int $branchId, ?int $nextId = null, bool $forCreate = true): array
    {
        $code = $row['client_code'] ?? null;
        if ($forCreate && ! $code && $nextId) {
            $code = 'CL-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
        }

        $attrs = [
            'name' => $row['name'],
            'group_name' => $row['group_name'] ?? null,
            'entity_type' => $row['entity_type'],
            'industry' => $row['industry'],
            'pan' => $row['pan'],
            'gstin' => $row['gstin'],
            'cin' => $row['cin'],
            'tan' => $row['tan'],
            'registered_address' => $row['registered_address'],
            'status' => $row['status'] ?? Client::STATUS_ACTIVE,
            'category' => $row['category'] ?? 'C',
            'primary_contact_name' => $row['primary_contact_name'],
            'primary_contact_phone' => $row['phone'],
            'primary_contact_email' => $row['email'],
            'gst_applicable' => ! empty($row['gstin']),
            'branch_id' => $branchId,
        ];

        if ($forCreate || $code) {
            $attrs['client_code'] = $code;
        }

        return array_filter($attrs, fn ($v) => $v !== null);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function syncServices(Client $client, array $row): void
    {
        $serviceIds = $row['service_ids'] ?? [];
        if ($serviceIds === []) {
            return;
        }

        $syncData = [];
        foreach ($serviceIds as $serviceId) {
            $syncData[$serviceId] = [
                'status' => ClientService::STATUS_ACTIVE,
                'custom_due_day' => null,
            ];
        }

        $client->optedServices()->sync($syncData);
    }
}
