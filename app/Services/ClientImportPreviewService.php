<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ClientImportPreviewService
{
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
            $gstin = $row['gstin'];
            $clientCode = $row['client_code'];

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

            if ($gstin !== '' && isset($seenGstin[$gstin])) {
                $rowWarnings[] = "Duplicate GSTIN in file (row {$seenGstin[$gstin]})";
            }

            if ($clientCode !== '' && isset($seenCode[$clientCode])) {
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
            if ($gstin !== '') {
                $seenGstin[$gstin] = $row['row'];
            }
            if ($clientCode !== '') {
                $seenCode[$clientCode] = $row['row'];
            }

            $existing = Client::query()->where('pan', $pan)->first();

            if ($gstin !== '') {
                $gstinOwner = Client::query()->where('gstin', $gstin)->where('pan', '!=', $pan)->first();
                if ($gstinOwner) {
                    $rowWarnings[] = "GSTIN already used by {$gstinOwner->name}";
                }
            }

            if ($clientCode !== '' && $existing && $existing->client_code !== $clientCode) {
                $rowWarnings[] = 'Client code differs from existing record';
            }

            if ($clientCode !== '' && ! $existing) {
                $codeOwner = Client::query()->where('client_code', $clientCode)->first();
                if ($codeOwner) {
                    $rowWarnings[] = "Client code already used by {$codeOwner->name}";
                }
            }

            $entry = array_merge($row, [
                'branch_id' => $branchId,
                'warnings' => $rowWarnings,
            ]);

            if (! empty($rowWarnings)) {
                $warnings[] = [
                    'row' => $row['row'],
                    'name' => $name,
                    'pan' => $pan,
                    'messages' => $rowWarnings,
                ];
            }

            if ($existing) {
                $entry['existing_id'] = $existing->id;
                $entry['existing_name'] = $existing->name;
                $update[] = $entry;
            } else {
                $create[] = $entry;
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

            $parsed[] = [
                'row' => $i + 1,
                'empty' => $name === '' && $pan === '',
                'name' => $name,
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
            ];
        }

        return $parsed;
    }

    protected function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));

        return match ($header) {
            'mobile', 'contact_phone', 'primary_contact_phone' => 'phone',
            default => $header,
        };
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
