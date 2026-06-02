<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientService;
use Illuminate\Support\Facades\DB;

class ClientImportApplier
{
    /** Bump when deploy verification is needed (grep on server). */
    public const VERSION = 4;

    public function __construct(
        protected ClientImportPreviewService $previewService,
    ) {}

    /**
     * @param  array{create: array, update: array, invalid: array}|null  $preview  Optional preview from session (confirm UI).
     * @return array{created: int, updated: int, skipped: int}
     */
    public function apply(string $absolutePath, ?int $branchId = null, ?array $preview = null): array
    {
        $preview ??= $this->previewService->preview($absolutePath, $branchId);

        if (count($preview['invalid']) > 0) {
            throw new \InvalidArgumentException('Cannot import while invalid rows exist.');
        }

        $created = 0;
        $updated = 0;
        $usedCodes = Client::withTrashed()
            ->whereNotNull('client_code')
            ->pluck('client_code')
            ->all();

        DB::transaction(function () use ($preview, $branchId, &$created, &$updated, &$usedCodes) {
            foreach ($preview['create'] as $row) {
                $existing = $this->findClientByPan((string) $row['pan']);
                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->update($this->attributesFromRow($row, $branchId, null, false));
                    $this->syncServices($existing, $row);
                    $updated++;

                    continue;
                }

                $row['client_code'] = $this->resolveClientCodeForCreate($row, $usedCodes);
                $client = Client::create($this->attributesFromRow($row, $branchId, null, true));
                $this->syncServices($client, $row);
                $created++;
            }

            foreach ($preview['update'] as $row) {
                $client = Client::withTrashed()->find($row['existing_id']);
                if (! $client) {
                    continue;
                }

                if ($client->trashed()) {
                    $client->restore();
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

    protected function findClientByPan(string $pan): ?Client
    {
        $pan = strtoupper(trim($pan));
        if ($pan === '') {
            return null;
        }

        return Client::withTrashed()
            ->whereRaw('UPPER(TRIM(pan)) = ?', [$pan])
            ->first();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function attributesFromRow(array $row, ?int $branchId, ?int $nextId = null, bool $forCreate = true): array
    {
        $code = $row['client_code'] ?? null;

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

        // On update, keep the existing client_code (Excel codes are often re-export labels).
        if ($forCreate && $code) {
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

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $usedCodes
     */
    protected function resolveClientCodeForCreate(array $row, array &$usedCodes): string
    {
        $requested = trim((string) ($row['client_code'] ?? ''));

        if ($requested !== ''
            && ! in_array($requested, $usedCodes, true)
            && ! Client::withTrashed()->where('client_code', $requested)->exists()) {
            $usedCodes[] = $requested;

            return $requested;
        }

        return $this->allocateClientCode($usedCodes);
    }

    /**
     * @param  list<string>  $usedCodes
     */
    protected function allocateClientCode(array &$usedCodes): string
    {
        $max = 0;
        foreach ($usedCodes as $code) {
            if (preg_match('/^CL-(\d+)$/i', $code, $match)) {
                $max = max($max, (int) $match[1]);
            }
        }

        do {
            $max++;
            $candidate = 'CL-'.str_pad((string) $max, 4, '0', STR_PAD_LEFT);
        } while (
            in_array($candidate, $usedCodes, true)
            || Client::withTrashed()->where('client_code', $candidate)->exists()
        );

        $usedCodes[] = $candidate;

        return $candidate;
    }
}
