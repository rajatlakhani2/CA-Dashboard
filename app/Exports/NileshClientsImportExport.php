<?php

namespace App\Exports;

use App\Services\ImportClientsNileshMetadata;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class NileshClientsImportExport implements FromArray, WithTitle
{
    /** @var list<array{pan: string, name: string, kept_name: string}> */
    private array $duplicatePanRows = [];

    public function __construct(
        private string $folderPath,
        private ?ImportClientsNileshMetadata $metadata = null,
        private bool $skipMissingPan = true,
        private bool $skipDuplicatePan = true,
    ) {
        $this->metadata ??= new ImportClientsNileshMetadata;
    }

    /**
     * @return list<array{pan: string, name: string, kept_name: string}>
     */
    public function duplicatePanRows(): array
    {
        return $this->duplicatePanRows;
    }

    public function array(): array
    {
        $headers = [
            'client_code',
            'name',
            'group_name',
            'entity_type',
            'industry',
            'pan',
            'gstin',
            'cin',
            'tan',
            'registered_address',
            'status',
            'category',
            'primary_contact_name',
            'phone',
            'email',
            'services',
        ];

        if (! File::isDirectory($this->folderPath)) {
            return [$headers];
        }

        $directories = File::directories($this->folderPath);
        usort($directories, fn ($a, $b) => strcasecmp(basename($a), basename($b)));

        $sheet = [$headers];
        $seenPan = [];

        foreach ($directories as $dir) {
            $name = trim(basename($dir));
            if ($this->metadata->shouldSkipFolder($name)) {
                continue;
            }

            $itr = $this->metadata->extractItrMetadata($dir);
            $gst = $this->metadata->extractGstMetadata($dir);
            $pan = strtoupper((string) ($itr['pan'] ?? $this->metadata->resolvePanForClientFolder($dir) ?? ''));
            $gstin = $gst['gstin'] ?? null;

            if ($this->skipMissingPan && $pan === '') {
                continue;
            }

            if ($pan !== '' && isset($seenPan[$pan])) {
                $this->duplicatePanRows[] = [
                    'pan' => $pan,
                    'name' => $name,
                    'kept_name' => $seenPan[$pan],
                ];
                if ($this->skipDuplicatePan) {
                    continue;
                }
            }

            if ($pan !== '') {
                $seenPan[$pan] = $name;
            }

            $services = ['IT Return'];
            if ($gst['has_gst']) {
                $services[] = 'GST Return';
            }

            $sheet[] = [
                '',
                $name,
                'Nileshbhai',
                $this->guessEntityType($name),
                '',
                $pan,
                $gstin ?? '',
                '',
                '',
                '',
                'Active',
                'C',
                $name,
                '',
                '',
                implode(', ', $services),
            ];
        }

        return $sheet;
    }

    public function title(): string
    {
        return 'Client Import Template';
    }

    protected function guessEntityType(string $clientName): string
    {
        $upper = strtoupper($clientName);

        if (str_contains($upper, ' HUF')) {
            return 'HUF';
        }
        if (str_contains($upper, ' PVT') || str_contains($upper, ' PRIVATE')) {
            return 'Private Limited';
        }
        if (str_contains($upper, ' LLP')) {
            return 'LLP';
        }
        if (str_contains($upper, ' PARTNERSHIP')) {
            return 'Partnership';
        }

        return 'Individual';
    }
}
