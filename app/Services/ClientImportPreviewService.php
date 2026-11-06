<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ClientImportPreviewService
{
    public function __construct(
        protected ClientImportServiceResolver $serviceResolver,
    ) {}

    /**
     * @return array{create: array, update: array, invalid: array, warnings: array, skip: array}
     */
    public function preview(UploadedFile|string $file, ?int $branchId = null): array
    {
        $parsed = $this->parseSheet($file);
        $create = [];
        $update = [];
        $invalid = [];
        $warnings = [];
        $seenPan = [];
        $seenGstin = [];
        $seenCode = [];

        foreach ($parsed as $row) {
            if ($row['empty']) {
                continue;
            }

            $name = $row['name'];
            $pan = $row['pan'];
            $gstin = filled($row['gstin'] ?? null) ? (string) $row['gstin'] : null;
            $clientCode = filled($row['client_code'] ?? null) ? (string) $row['client_code'] : null;

            $rowWarnings = [];

            if ($pan !== '' && isset($seenPan[$pan])) {
                $invalid[] = [
                    'row' => $row['row'],
                    'name' => $name,
                    'pan' => $pan,
                    'errors' => ["Duplicate PAN in file (first at row {$seenPan[$pan]})"],
                ];
                continue;
            }

            if ($gstin !== null && isset($seenGstin[$gstin])) {
                $rowWarnings[] = "Duplicate GSTIN in file (row {$seenGstin[$gstin]})";
            }

            if ($clientCode !== null && isset($seenCode[$clientCode])) {
                $rowWarnings[] = "Duplicate client code in file (row {$seenCode[$clientCode]})";
            }

            $validator = Validator::make(
                [
                    'name' => $name,
                    'pan' => $pan,
                    'email' => $row['email'],
                    'category' => $row['category'] ?: null,
                ],
                [
                    'name' => 'required',
                    'pan' => ['required', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i'],
                    'email' => 'nullable|email',
                    'category' => ['nullable', Rule::in(['A', 'B', 'C'])],
                ]
            );

            if ($validator->fails()) {
                $invalid[] = [
                    'row' => $row['row'],
                    'name' => $name,
                    'pan' => $pan,
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            if ($pan !== '') {
                $seenPan[$pan] = $row['row'];
            }
            if ($gstin !== null) {
                $seenGstin[$gstin] = $row['row'];
            }
            if ($clientCode !== null) {
                $seenCode[$clientCode] = $row['row'];
            }

            $existing = $this->findClientByPan($pan);

            if ($gstin !== null) {
                $gstinOwner = Client::query()->where('gstin', $gstin)->where('pan', '!=', $pan)->first();
                if ($gstinOwner) {
                    $rowWarnings[] = "GSTIN already used by {$gstinOwner->name}";
                }
            }

            if ($clientCode !== null && ! $existing) {
                $codeOwner = Client::withTrashed()->where('client_code', $clientCode)->first();
                if ($codeOwner) {
                    $rowWarnings[] = "Client code already used by {$codeOwner->name} (a new code will be assigned on import)";
                }
            }

            if (! empty($row['unknown_services'])) {
                $rowWarnings[] = 'Unknown service(s): ' . implode(', ', $row['unknown_services']);
            }

            if ($existing) {
                $entry = array_merge($row, [
                    'branch_id' => $branchId,
                    'client_code' => $existing->client_code,
                    'warnings' => $rowWarnings,
                    'existing_id' => $existing->id,
                    'existing_name' => $existing->name,
                ]);
                if ($existing->trashed()) {
                    $rowWarnings[] = 'Client was deleted earlier — will be restored and updated on import';
                    $entry['warnings'] = $rowWarnings;
                }
                $update[] = $entry;
            } else {
                $entry = array_merge($row, [
                    'branch_id' => $branchId,
                    'warnings' => $rowWarnings,
                ]);
                $create[] = $entry;
            }

            if (! empty($rowWarnings)) {
                $warnings[] = [
                    'row' => $row['row'],
                    'name' => $name,
                    'pan' => $pan,
                    'messages' => $rowWarnings,
                ];
            }
        }

        return [
            'create' => $create,
            'update' => $update,
            'invalid' => $invalid,
            'warnings' => $warnings,
            'skip' => [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseSheet(UploadedFile|string $file): array
    {
        $rows = Excel::toArray([], $file);
        $sheet = $rows[0] ?? [];

        if (empty($sheet)) {
            return [];
        }

        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $sheet[0]);
        $parsed = [];

        for ($i = 1; $i < count($sheet); $i++) {
            $raw = $sheet[$i];
            $data = [];
            foreach ($headers as $idx => $header) {
                if ($header !== '') {
                    $data[$header] = $raw[$idx] ?? null;
                }
            }

            $name = trim((string) ($data['name'] ?? ''));
            $pan = strtoupper(trim((string) ($data['pan'] ?? '')));
            $gstin = strtoupper(trim((string) ($data['gstin'] ?? '')));
            $clientCode = trim((string) ($data['client_code'] ?? ''));

            $servicesRaw = $this->stringOrNull($data['services'] ?? null);
            $serviceMatch = $this->serviceResolver->resolve($servicesRaw);

            $parsed[] = [
                'row' => $i + 1,
                'empty' => $name === '' && $pan === '',
                'name' => $name,
                'group_name' => $this->stringOrNull($data['group_name'] ?? null),
                'pan' => $pan,
                'gstin' => $gstin !== '' ? $gstin : null,
                'client_code' => $clientCode !== '' ? $clientCode : null,
                'entity_type' => $this->stringOrNull($data['entity_type'] ?? null),
                'industry' => $this->stringOrNull($data['industry'] ?? null),
                'cin' => $this->upperOrNull($data['cin'] ?? null),
                'tan' => $this->upperOrNull($data['tan'] ?? null),
                'registered_address' => $this->stringOrNull($data['registered_address'] ?? null),
                'status' => $this->stringOrNull($data['status'] ?? null) ?: Client::STATUS_ACTIVE,
                'category' => strtoupper($this->stringOrNull($data['category'] ?? null) ?: 'C'),
                'primary_contact_name' => $this->stringOrNull($data['primary_contact_name'] ?? null),
                'phone' => $this->stringOrNull($data['phone'] ?? null),
                'email' => $this->stringOrNull($data['email'] ?? null),
                'date_of_birth' => $this->dateOrNull($data['date_of_birth'] ?? null),
                'whatsapp_number' => $this->stringOrNull($data['whatsapp_number'] ?? null),
                'whatsapp_opt_in' => $this->boolOrNull($data['whatsapp_opt_in'] ?? null),
                'city' => $this->stringOrNull($data['city'] ?? null),
                'state' => $this->stringOrNull($data['state'] ?? null),
                'pincode' => $this->stringOrNull($data['pincode'] ?? null),
                'referred_by' => $this->stringOrNull($data['referred_by'] ?? null),
                'services_raw' => $servicesRaw,
                'service_ids' => $serviceMatch['service_ids'],
                'services_resolved' => $serviceMatch['resolved'],
                'unknown_services' => $serviceMatch['unknown'],
            ];
        }

        return $parsed;
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

    protected function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));

        return match ($header) {
            'mobile', 'contact_phone', 'primary_contact_phone' => 'phone',
            'service', 'service_types', 'services_provided', 'type_of_services' => 'services',
            'group', 'client reference', 'reference', 'portfolio', 'family', 'family group' => 'group_name',
            'dob', 'date of birth', 'birth date', 'date of incorporation', 'doi' => 'date_of_birth',
            'whatsapp', 'whatsapp no', 'whatsapp number', 'whatsapp_no' => 'whatsapp_number',
            'whatsapp reminders', 'whatsapp consent', 'whatsapp_consent' => 'whatsapp_opt_in',
            'pin', 'pin code', 'pin_code', 'zip' => 'pincode',
            'reference by', 'referred by', 'referral' => 'referred_by',
            default => $header,
        };
    }

    /** Accepts Excel serial dates, d/m/Y, d-m-Y and Y-m-d. Returns Y-m-d or null. */
    protected function dateOrNull(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return null;
        }

        try {
            if (is_numeric($value) && (float) $value > 1000) {
                return \Carbon\Carbon::create(1899, 12, 30)->addDays((int) $value)->format('Y-m-d');
            }
            foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/y'] as $format) {
                $date = \DateTime::createFromFormat('!'.$format, $value);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }

            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function boolOrNull(mixed $value): ?bool
    {
        $value = strtolower((string) $this->stringOrNull($value));
        if ($value === '') {
            return null;
        }

        return in_array($value, ['1', 'yes', 'y', 'true', 'haa', 'ha'], true);
    }

    protected function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    protected function upperOrNull(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        return $value ? strtoupper($value) : null;
    }
}
