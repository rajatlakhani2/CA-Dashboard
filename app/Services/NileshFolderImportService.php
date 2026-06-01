<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NileshFolderImportService
{
    private ImportClientsNileshMetadata $metadata;

    public function __construct(?ImportClientsNileshMetadata $metadata = null)
    {
        $this->metadata = $metadata ?? new ImportClientsNileshMetadata;
    }

    public function preview(string $path): array
    {
        if (! File::isDirectory($path)) {
            return ['error' => "Directory not found: {$path}", 'create' => [], 'update' => [], 'skip' => []];
        }

        $create = [];
        $update = [];
        $skip = [];

        foreach (File::directories($path) as $dir) {
            $clientName = trim(basename($dir));

            if ($this->metadata->shouldSkipFolder($clientName)) {
                $skip[] = ['folder' => $clientName, 'reason' => 'Ignored folder name'];
                continue;
            }

            $itrMeta = $this->metadata->extractItrMetadata($dir);
            $pan = $itrMeta['pan'] ?? $this->metadata->findPanInFiles($dir);
            $client = Client::query()->where('name', $clientName)->first();

            if (! $client && $pan) {
                $client = Client::query()->where('pan', $pan)->first();
            }

            $row = [
                'folder' => $clientName,
                'pan' => $pan,
                'ack' => $itrMeta['ack_file'] ?? null,
                'computation' => $itrMeta['computation_file'] ?? null,
            ];

            if ($client) {
                $row['existing_id'] = $client->id;
                $update[] = $row;
            } else {
                $create[] = $row;
            }
        }

        return [
            'create' => $create,
            'update' => $update,
            'skip' => $skip,
            'total_folders' => count($create) + count($update) + count($skip),
        ];
    }
}

