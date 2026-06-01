<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Shared metadata helpers for Nilesh folder import (CLI + UI preview).
 */
class ImportClientsNileshMetadata
{
    public function shouldSkipFolder(string $name): bool
    {
        $lower = strtolower($name);

        return in_array($lower, ['.ds_store', 'thumbs.db', 'desktop.ini'], true)
            || str_starts_with($name, '.')
            || strlen($name) < 2;
    }

    public function extractItrMetadata(string $dir): array
    {
        $meta = ['pan' => null, 'ack_file' => null, 'computation_file' => null];
        $files = File::allFiles($dir);

        foreach ($files as $file) {
            $basename = strtolower($file->getFilename());
            if (str_contains($basename, 'ack') && preg_match('/\.pdf$/i', $basename)) {
                $meta['ack_file'] = $file->getFilename();
            }
            if ((str_contains($basename, 'computation') || str_contains($basename, 'compu')) && preg_match('/\.pdf$/i', $basename)) {
                $meta['computation_file'] = $file->getFilename();
            }
            if (! $meta['pan'] && preg_match('/([A-Z]{5}[0-9]{4}[A-Z])/i', $basename, $m)) {
                $meta['pan'] = strtoupper($m[1]);
            }
        }

        return $meta;
    }

    public function findPanInFiles(string $dir): ?string
    {
        foreach (File::allFiles($dir) as $file) {
            if (preg_match('/([A-Z]{5}[0-9]{4}[A-Z])/i', $file->getFilename(), $m)) {
                return strtoupper($m[1]);
            }
        }

        return null;
    }
}
