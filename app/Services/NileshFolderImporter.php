<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NileshFolderImporter
{
    public function __construct(
        private readonly ImportClientsNileshMetadata $metadata = new ImportClientsNileshMetadata
    ) {}

    public function run(string $path, bool $assignService = false): array
    {
        if (! File::isDirectory($path)) {
            return ['error' => "Directory not found: {$path}", 'created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $nilesh = User::query()->where('email', 'nilesh@rlassociates.in')->first();
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (File::directories($path) as $dir) {
            $clientName = trim(basename($dir));

            if ($this->metadata->shouldSkipFolder($clientName)) {
                $skipped++;
                continue;
            }

            $itrMeta = $this->extractItrMetadata($dir);
            $pan = $itrMeta['pan'] ?? $this->metadata->findPanInFiles($dir);
            $client = Client::query()->where('name', $clientName)->first();

            $payload = [
                'name' => $clientName,
                'group_name' => 'Nilesh Bhai',
                'tags' => ['Nileshbhai client'],
                'status' => Client::STATUS_ACTIVE,
                'category' => 'C',
                'approval_status' => Client::APPROVAL_APPROVED,
                'manager_id' => $nilesh?->id,
                'office_notes' => $this->formatOfficeNotes($itrMeta),
            ];

            if ($pan) {
                $payload['pan'] = $pan;
            }

            try {
                if (! $client && $pan) {
                    $client = Client::query()->where('pan', $pan)->first();
                }

                if ($client) {
                    if ($pan && Client::query()->where('pan', $pan)->where('id', '!=', $client->id)->exists()) {
                        unset($payload['pan']);
                    }
                    $client->fill($payload);
                    if (! $client->client_code) {
                        $client->client_code = 'NB-'.strtoupper(Str::random(5));
                    }
                    $client->save();
                    $updated++;
                } else {
                    $payload['client_code'] = 'NB-'.strtoupper(Str::random(5));
                    if ($pan && Client::query()->where('pan', $pan)->exists()) {
                        unset($payload['pan']);
                    } elseif ($pan) {
                        $payload['pan'] = $pan;
                    }
                    Client::create($payload);
                    $created++;
                }
            } catch (\Throwable) {
                $skipped++;
            }
        }

        if ($assignService) {
            \Illuminate\Support\Facades\Artisan::call('services:ensure-income-tax-return', ['--assign-all' => true]);
        }

        return compact('created', 'updated', 'skipped');
    }

    private function extractItrMetadata(string $dir): array
    {
        $meta = [
            'acknowledgement' => [],
            'computation' => [],
            'assessment_years' => [],
            'pan' => null,
            'ack_file' => null,
            'computation_file' => null,
        ];

        foreach (File::allFiles($dir) as $file) {
            $name = $file->getFilename();
            $lower = strtolower($name);

            if (preg_match('/[A-Z]{5}[0-9]{4}[A-Z]{1}/', $name, $m)) {
                $meta['pan'] = $meta['pan'] ?? strtoupper($m[0]);
            }

            if (preg_match('/(20\d{2}[-_]?\d{2}|AY\s*20\d{2})/i', $name, $ay)) {
                $meta['assessment_years'][] = $ay[0];
            }

            if (str_contains($lower, 'ack') || str_contains($lower, 'acknowledgement') || str_contains($lower, 'itr-v')) {
                $meta['acknowledgement'][] = $name;
                $meta['ack_file'] ??= $name;
            }

            if (str_contains($lower, 'computation') || str_contains($lower, 'coi')
                || str_contains($lower, 'income') || str_contains($lower, 'tax calculation')) {
                $meta['computation'][] = $name;
                $meta['computation_file'] ??= $name;
            }
        }

        $meta['assessment_years'] = array_values(array_unique($meta['assessment_years']));

        return $meta;
    }

    private function formatOfficeNotes(array $meta): string
    {
        $lines = ['Portfolio: Nilesh Bhai — Income Tax Return'];

        if (! empty($meta['pan'])) {
            $lines[] = 'PAN: '.$meta['pan'];
        }
        if (! empty($meta['assessment_years'])) {
            $lines[] = 'Assessment years (files): '.implode(', ', $meta['assessment_years']);
        }
        if (! empty($meta['acknowledgement'])) {
            $lines[] = 'ITR Acknowledgement: '.implode('; ', array_slice($meta['acknowledgement'], 0, 5));
        }
        if (! empty($meta['computation'])) {
            $lines[] = 'Computation of Income: '.implode('; ', array_slice($meta['computation'], 0, 5));
        }

        return implode("\n", $lines);
    }
}
