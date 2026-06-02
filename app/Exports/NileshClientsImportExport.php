<?php

namespace App\Exports;

use App\Services\ImportClientsNileshMetadata;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class NileshClientsImportExport implements FromArray, WithTitle
{
    public function __construct(
        private string $folderPath,
        private ?ImportClientsNileshMetadata $metadata = null,
    ) {
        $this->metadata ??= new ImportClientsNileshMetadata;
    }

    public function array(): array
    {
        $sheet = [
            [
                'client_code',
                'name',
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
            ],
        ];

        if (! File::isDirectory($this->folderPath)) {
            return $sheet;
        }

        $directories = File::directories($this->folderPath);
        usort($directories, fn ($a, $b) => strcasecmp(basename($a), basename($b)));

        foreach ($directories as $dir) {
            $name = trim(basename($dir));
            if ($this->metadata->shouldSkipFolder($name)) {
                continue;
            }

            $itr = $this->metadata->extractItrMetadata($dir);
            $gst = $this->metadata->extractGstMetadata($dir);
            $pan = $itr['pan'] ?? $this->metadata->findPanInFiles($dir);
            $gstin = $gst['gstin'] ?? null;

            $services = ['IT Return'];
            if ($gst['has_gst']) {
                $services[] = 'GST Return';
            }

            $sheet[] = [
                '',
                $name,
                $this->guessEntityType($name),
                '',
                $pan ?? '',
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
