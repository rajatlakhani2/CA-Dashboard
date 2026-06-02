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
            if (! $meta['pan']) {
                $meta['pan'] = $this->extractPanFromText($file->getFilename());
            }
        }

        if (! $meta['pan']) {
            $meta['pan'] = $this->resolvePanForClientFolder($dir);
        }

        return $meta;
    }

    /**
     * Resolve PAN from filenames first, then scan PDF contents (skips masked XXXX patterns).
     */
    public function resolvePanForClientFolder(string $dir): ?string
    {
        foreach (File::allFiles($dir) as $file) {
            $pan = $this->extractPanFromText($file->getFilename());
            if ($pan) {
                return $pan;
            }
        }

        $pdfs = collect(File::allFiles($dir))
            ->filter(fn ($file) => preg_match('/\.pdf$/i', $file->getFilename()) === 1)
            ->sortBy(fn ($file) => $this->pdfPanSearchPriority($file->getFilename()));

        foreach ($pdfs as $file) {
            $pan = $this->extractPanFromPdfContents($file->getPathname());
            if ($pan) {
                return $pan;
            }
        }

        return null;
    }

    public function findPanInFiles(string $dir): ?string
    {
        return $this->resolvePanForClientFolder($dir);
    }

    public function extractPanFromText(string $text): ?string
    {
        if (! preg_match_all('/([A-Z]{5}[0-9]{4}[A-Z])/i', $text, $matches)) {
            return null;
        }

        foreach (array_unique(array_map('strtoupper', $matches[1])) as $candidate) {
            if (! $this->isMaskedPan($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function isMaskedPan(string $pan): bool
    {
        $pan = strtoupper(trim($pan));

        if ($pan === '' || preg_match('/X/', $pan)) {
            return true;
        }

        if (preg_match('/^(.)\1{4}[0-9]{4}.$/', $pan)) {
            return true;
        }

        return false;
    }

    public function extractPanFromPdfContents(string $path): ?string
    {
        $binary = @file_get_contents($path, false, null, 0, 8_000_000);
        if ($binary === false || $binary === '') {
            return null;
        }

        $pan = $this->extractPanFromText($binary);
        if ($pan) {
            return $pan;
        }

        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $binary, $streams)) {
            foreach ($streams[1] as $stream) {
                $decoded = @gzuncompress($stream);
                if ($decoded === false && strlen($stream) > 2) {
                    $decoded = @gzuncompress(substr($stream, 2));
                }
                if (is_string($decoded) && $decoded !== '') {
                    $pan = $this->extractPanFromText($decoded);
                    if ($pan) {
                        return $pan;
                    }
                }
            }
        }

        return null;
    }

    protected function pdfPanSearchPriority(string $filename): int
    {
        $lower = strtolower($filename);

        return match (true) {
            str_contains($lower, 'pan') => 0,
            str_contains($lower, 'ack') || str_contains($lower, 'itr-v') => 1,
            str_contains($lower, 'computation') || str_contains($lower, 'compu') => 2,
            str_contains($lower, 'itr') || str_contains($lower, 'income') => 3,
            str_contains($lower, 'aadhaar') || str_contains($lower, 'aadhar') => 4,
            default => 5,
        };
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
