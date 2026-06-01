<?php

namespace App\Services\Intelligence;

use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\FirmAlert;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceDue;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class AnomalyScanner
{
    public const OUTSTANDING_THRESHOLD = 100000;

    public const CREDENTIAL_IDLE_DAYS = 90;

    public const COMPLIANCE_STACK_MIN = 5;

    /**
     * @return array{created: int, resolved: int}
     */
    public function scan(): array
    {
        $fingerprints = [];

        foreach ($this->detectDuplicatePans() as $alert) {
            $fingerprints[] = $this->upsert($alert);
        }

        foreach ($this->detectHighOutstanding() as $alert) {
            $fingerprints[] = $this->upsert($alert);
        }

        foreach ($this->detectIdleCredentials() as $alert) {
            $fingerprints[] = $this->upsert($alert);
        }

        foreach ($this->detectComplianceStacks() as $alert) {
            $fingerprints[] = $this->upsert($alert);
        }

        $resolved = FirmAlert::query()
            ->open()
            ->whereNotIn('fingerprint', $fingerprints)
            ->update([
                'dismissed_at' => now(),
            ]);

        return [
            'created' => count(array_unique($fingerprints)),
            'resolved' => $resolved,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function detectDuplicatePans(): array
    {
        $duplicates = Client::query()
            ->select('pan', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('pan')
            ->where('pan', '!=', '')
            ->groupBy('pan')
            ->having('cnt', '>', 1)
            ->get();

        $alerts = [];

        foreach ($duplicates as $row) {
            $clients = Client::where('pan', $row->pan)->get(['id', 'name', 'client_code']);
            $alerts[] = [
                'type' => FirmAlert::TYPE_DUPLICATE_PAN,
                'severity' => FirmAlert::SEVERITY_CRITICAL,
                'title' => 'Duplicate PAN: ' . $row->pan,
                'message' => 'Multiple clients share PAN ' . $row->pan . ': '
                    . $clients->pluck('name')->join(', '),
                'client_id' => $clients->first()?->id,
                'related_type' => null,
                'related_id' => null,
                'fingerprint' => 'duplicate_pan:' . strtoupper($row->pan),
                'metadata' => [
                    'pan' => $row->pan,
                    'client_ids' => $clients->pluck('id')->all(),
                ],
            ];
        }

        return $alerts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function detectHighOutstanding(): array
    {
        $alerts = [];

        Client::query()
            ->where('status', Client::STATUS_ACTIVE)
            ->chunkById(100, function ($clients) use (&$alerts) {
                foreach ($clients as $client) {
                    $billed = (float) Invoice::where('client_id', $client->id)
                        ->where('status', '!=', Invoice::STATUS_DRAFT)
                        ->where('status', '!=', Invoice::STATUS_CANCELLED)
                        ->sum('total_amount');

                    $collected = (float) Payment::whereHas('invoice', fn ($q) => $q->where('client_id', $client->id))
                        ->sum('amount');

                    $outstanding = max(0, $billed - $collected);

                    if ($outstanding < self::OUTSTANDING_THRESHOLD) {
                        continue;
                    }

                    $alerts[] = [
                        'type' => FirmAlert::TYPE_HIGH_OUTSTANDING,
                        'severity' => FirmAlert::SEVERITY_WARNING,
                        'title' => 'High outstanding: ' . $client->name,
                        'message' => sprintf(
                            '%s has ₹%s ledger outstanding.',
                            $client->name,
                            number_format($outstanding, 2)
                        ),
                        'client_id' => $client->id,
                        'related_type' => null,
                        'related_id' => null,
                        'fingerprint' => 'high_outstanding:' . $client->id,
                        'metadata' => [
                            'outstanding' => $outstanding,
                        ],
                    ];
                }
            });

        return $alerts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function detectIdleCredentials(): array
    {
        $alerts = [];
        $cutoff = now()->subDays(self::CREDENTIAL_IDLE_DAYS);

        ClientCredential::with('client')->chunkById(50, function ($credentials) use (&$alerts, $cutoff) {
            foreach ($credentials as $credential) {
                $lastActivity = Activity::query()
                    ->where('log_name', 'credential_vault')
                    ->where('subject_type', ClientCredential::class)
                    ->where('subject_id', $credential->id)
                    ->max('created_at');

                $lastTouch = $lastActivity ? \Carbon\Carbon::parse($lastActivity) : $credential->created_at;

                if ($lastTouch->greaterThan($cutoff)) {
                    continue;
                }

                $alerts[] = [
                    'type' => FirmAlert::TYPE_CREDENTIAL_IDLE,
                    'severity' => FirmAlert::SEVERITY_INFO,
                    'title' => 'Credential unused: ' . $credential->portal_name,
                    'message' => sprintf(
                        'Vault entry "%s" for %s has had no access since %s.',
                        $credential->portal_name,
                        $credential->client?->name ?? 'client',
                        $lastTouch->format('d M Y')
                    ),
                    'client_id' => $credential->client_id,
                    'related_type' => ClientCredential::class,
                    'related_id' => $credential->id,
                    'fingerprint' => 'credential_idle:' . $credential->id,
                    'metadata' => [
                        'portal_name' => $credential->portal_name,
                        'last_touch' => $lastTouch->toIso8601String(),
                    ],
                ];
            }
        });

        return $alerts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function detectComplianceStacks(): array
    {
        $alerts = [];

        $stacks = ServiceDue::query()
            ->whereIn('service_dues.status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
            ->join('client_services', 'service_dues.client_service_id', '=', 'client_services.id')
            ->select('client_services.client_id', DB::raw('COUNT(*) as due_count'))
            ->groupBy('client_services.client_id')
            ->having('due_count', '>=', self::COMPLIANCE_STACK_MIN)
            ->get();

        foreach ($stacks as $stack) {
            $client = Client::find($stack->client_id);
            if (! $client) {
                continue;
            }

            $alerts[] = [
                'type' => FirmAlert::TYPE_COMPLIANCE_STACK,
                'severity' => FirmAlert::SEVERITY_WARNING,
                'title' => 'Compliance backlog: ' . $client->name,
                'message' => sprintf(
                    '%s has %d open compliance dues (pending/overdue).',
                    $client->name,
                    $stack->due_count
                ),
                'client_id' => $client->id,
                'related_type' => null,
                'related_id' => null,
                'fingerprint' => 'compliance_stack:' . $client->id,
                'metadata' => ['due_count' => (int) $stack->due_count],
            ];
        }

        return $alerts;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function upsert(array $data): string
    {
        FirmAlert::query()->updateOrCreate(
            ['fingerprint' => $data['fingerprint']],
            [
                'type' => $data['type'],
                'severity' => $data['severity'],
                'title' => $data['title'],
                'message' => $data['message'],
                'client_id' => $data['client_id'],
                'related_type' => $data['related_type'],
                'related_id' => $data['related_id'],
                'metadata' => $data['metadata'] ?? null,
                'dismissed_at' => null,
                'dismissed_by' => null,
            ]
        );

        return $data['fingerprint'];
    }
}
