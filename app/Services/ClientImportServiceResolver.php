<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Collection;

class ClientImportServiceResolver
{
    /** @var Collection<int, Service>|null */
    private ?Collection $catalog = null;

    /**
     * @return array{service_ids: int[], resolved: string[], unknown: string[]}
     */
    public function resolve(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return ['service_ids' => [], 'resolved' => [], 'unknown' => []];
        }

        $tokens = preg_split('/[,;|\/]+/', $raw) ?: [];
        $serviceIds = [];
        $resolved = [];
        $unknown = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $service = $this->matchToken($token);
            if ($service) {
                if (! in_array($service->id, $serviceIds, true)) {
                    $serviceIds[] = $service->id;
                    $resolved[] = $service->name;
                }
            } else {
                $unknown[] = $token;
            }
        }

        return [
            'service_ids' => $serviceIds,
            'resolved' => $resolved,
            'unknown' => $unknown,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function aliases(): array
    {
        return [
            'income tax' => 'ITR',
            'income tax return' => 'ITR',
            'it return' => 'ITR',
            'itr' => 'ITR',
            'tax audit' => 'TAX_AUDIT',
            'statutory audit' => 'STAT_AUDIT',
            'gst' => 'GST',
            'gst return' => 'GST',
            'gstr-3b' => 'GST',
            'gstr3b' => 'GST',
            'gstr-1' => 'GSTR1-M',
            'gstr1' => 'GSTR1-M',
            'gstr-1 (monthly)' => 'GSTR1-M',
            'other' => 'OTHER',
            'other services' => 'OTHER',
            'consultancy' => 'OTHER',
        ];
    }

    /**
     * @return Collection<int, Service>
     */
    public function availableServices(): Collection
    {
        return $this->catalog();
    }

    protected function matchToken(string $token): ?Service
    {
        $normalized = strtolower(trim($token));
        if ($normalized === '') {
            return null;
        }

        $aliasCode = $this->aliases()[$normalized] ?? null;
        if ($aliasCode) {
            return $this->catalog()->firstWhere('code', $aliasCode);
        }

        foreach ($this->catalog() as $service) {
            if (strtolower($service->name) === $normalized) {
                return $service;
            }
            if (strtolower($service->code) === $normalized) {
                return $service;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, Service>
     */
    protected function catalog(): Collection
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        if (Service::query()->count() === 0) {
            DefaultServicesCatalog::ensureExists();
        }

        return $this->catalog = Service::query()->get(['id', 'name', 'code']);
    }
}
