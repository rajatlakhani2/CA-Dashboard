<?php

namespace App\Services\Intelligence;

class DocumentFieldGuesser
{
    /**
     * Lightweight hints from filename until OCR API is configured.
     *
     * @return array{document_type: ?string, pan: ?string, amount: ?string, notes: ?string}
     */
    public function fromFilename(string $filename): array
    {
        $base = strtolower(pathinfo($filename, PATHINFO_FILENAME));
        $type = null;

        foreach (['gstr', 'itr', 'tds', '26as', 'notice', 'gst', 'audit', 'bank'] as $keyword) {
            if (str_contains($base, $keyword)) {
                $type = strtoupper($keyword === '26as' ? '26AS' : $keyword);
                break;
            }
        }

        $pan = null;
        if (preg_match('/[a-z]{5}[0-9]{4}[a-z]/i', $base, $m)) {
            $pan = strtoupper($m[0]);
        }

        $amount = null;
        if (preg_match('/(?:rs|inr)?[\s._-]?(\d{3,8})/i', $base, $m)) {
            $amount = $m[1];
        }

        return [
            'document_type' => $type,
            'pan' => $pan,
            'amount' => $amount,
            'notes' => 'Auto-guessed from filename — confirm or edit before approving.',
        ];
    }
}
