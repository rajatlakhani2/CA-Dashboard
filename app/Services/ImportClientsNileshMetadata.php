<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Shared metadata helpers for Nilesh folder import (CLI + UI preview).
 */
class ImportClientsNileshMetadata
{
    /** @var list<string> */
    private const SKIP_FOLDER_PATTERNS = [
        '/^extra(\s+\d+)?$/i',
        '/^old$/i',
        '/^new folder(\s*\(\d+\))?$/i',
        '/^payment\s+(sheet|list)/i',
        '/^pending returns$/i',
        '/^petrol pump$/i',
        '/^gst_offline/i',
        '/^notes?\b/i',
        '/^notes\s+njp$/i',
        '/^udin uploaded/i',
        '/^form\s*3c[bd]/i',
        '/^audit report/i',
        '/^tax audit\b/i',
        '/^tax payment\b/i',
        '/^income tax\b.*\.xlsx$/i',
        '/^itr status/i',
        '/^itc tracking/i',
        '/^status\.xlsx$/i',
        '/^sheet(\s*\d+)?\.xlsx$/i',
        '/^final udin/i',
        '/^dsc\s+list/i',
        '/^dscvalidity/i',
        '/^sahil list/i',
    ];

    public function shouldSkipFolder(string $name): bool
    {
        $lower = strtolower($name);

        if (in_array($lower, ['.ds_store', 'thumbs.db', 'desktop.ini'], true)
            || str_starts_with($name, '.')
            || strlen($name) < 2) {
            return true;
        }

        foreach (self::SKIP_FOLDER_PATTERNS as $pattern) {
            if (preg_match($pattern, $name)) {
                return true;
            }
        }

        return preg_match('/\.(xlsx?|pdf|docx?|jpeg|jpg|png|txt)$/i', $name) === 1;
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

    /**
     * @return array{has_gst: bool, gstin: ?string, gst_files: string[], certificates: string[]}
     */
    public function extractGstMetadata(string $dir): array
    {
        $meta = [
            'has_gst' => $this->folderNameImpliesGst(basename($dir)),
            'gstin' => null,
            'gst_files' => [],
            'certificates' => [],
        ];

        foreach (File::directories($dir) as $subDir) {
            $subName = strtolower(basename($subDir));
            if ($this->subfolderImpliesGst($subName)) {
                $meta['has_gst'] = true;
            }
            if (in_array($subName, ['certificate', 'certificates', 'certifcate'], true)) {
                $meta['has_gst'] = true;
            }
        }

        foreach (File::allFiles($dir) as $file) {
            $name = $file->getFilename();
            $lower = strtolower($name);
            $relative = str_replace('\\', '/', str_replace($dir.DIRECTORY_SEPARATOR, '', $file->getPathname()));

            if (! $meta['gstin']) {
                $meta['gstin'] = $this->extractGstinFromText($name)
                    ?? $this->extractGstinFromText($relative);
            }

            if ($this->fileImpliesGst($lower, $relative)) {
                $meta['has_gst'] = true;
                $meta['gst_files'][] = $relative;
            }

            if (str_contains($lower, 'certificate') || str_contains($lower, 'registration')) {
                if ($meta['has_gst'] || str_contains(strtolower($relative), 'gst')) {
                    $meta['certificates'][] = $relative;
                    $meta['has_gst'] = true;
                }
            }
        }

        $meta['gst_files'] = array_values(array_unique(array_slice($meta['gst_files'], 0, 8)));
        $meta['certificates'] = array_values(array_unique(array_slice($meta['certificates'], 0, 5)));

        return $meta;
    }

    public function extractGstinFromText(string $text): ?string
    {
        if (preg_match('/(\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z])/i', $text, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    protected function folderNameImpliesGst(string $folderName): bool
    {
        return (bool) preg_match('/\bgst\b/i', $folderName);
    }

    protected function subfolderImpliesGst(string $subName): bool
    {
        return in_array($subName, ['gst', 'gst return', 'gstr-1', 'gstr-3b', 'gstr1', 'gstr3b'], true)
            || str_contains($subName, 'gst return')
            || str_contains($subName, 'gst certificate');
    }

    protected function fileImpliesGst(string $lowerName, string $relativePath): bool
    {
        if (preg_match('/\bgstr[\s\-]?[013]?[b1]?\b/i', $lowerName)) {
            return true;
        }

        $needles = [
            'gst return',
            'gst registration',
            'gst certificate',
            'gst-challan',
            'gst challan',
            'gstin',
        ];

        foreach ($needles as $needle) {
            if (str_contains($lowerName, $needle) || str_contains(strtolower($relativePath), $needle)) {
                return true;
            }
        }

        return str_contains(strtolower($relativePath), '/gst/')
            && (str_contains($lowerName, '.pdf') || str_contains($lowerName, 'return') || str_contains($lowerName, 'receipt'));
    }
}
