<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NileshFolderImporter
{
    public function __construct(
        private readonly ImportClientsNileshMetadata $metadata = new ImportClientsNileshMetadata
    ) {}

    public function run(string $path, bool $assignServices = true): array
    {
        if (! File::isDirectory($path)) {
            return ['error' => "Directory not found: {$path}", 'created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $nilesh = User::query()->where('email', 'nilesh@rlassociates.in')->first();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $withGst = 0;

        foreach (File::directories($path) as $dir) {
            $clientName = trim(basename($dir));

            if ($this->metadata->shouldSkipFolder($clientName)) {
                $skipped++;
                continue;
            }

            $itrMeta = $this->extractItrMetadata($dir);
            $gstMeta = $this->metadata->extractGstMetadata($dir);
            $pan = $itrMeta['pan'] ?? $this->metadata->findPanInFiles($dir);
            $gstin = $gstMeta['gstin'] ?? null;

            $client = Client::query()->where('name', $clientName)->first();

            $payload = [
                'name' => $clientName,
                'group_name' => 'Nilesh Bhai',
                'tags' => ['Nileshbhai client'],
                'status' => Client::STATUS_ACTIVE,
                'category' => 'C',
                'approval_status' => Client::APPROVAL_APPROVED,
                'manager_id' => $nilesh?->id,
                'office_notes' => $this->formatOfficeNotes($itrMeta, $gstMeta),
                'gst_applicable' => $gstMeta['has_gst'] || $gstin !== null,
            ];

            if ($pan) {
                $payload['pan'] = $pan;
            }
            if ($gstin) {
                $payload['gstin'] = $gstin;
            }

            try {
                if (! $client && $pan) {
                    $client = Client::query()->where('pan', $pan)->first();
                }

                if ($client) {
                    if ($pan && Client::query()->where('pan', $pan)->where('id', '!=', $client->id)->exists()) {
                        unset($payload['pan']);
                    }
                    if ($gstin && Client::query()->where('gstin', $gstin)->where('id', '!=', $client->id)->exists()) {
                        unset($payload['gstin']);
                    }
                    $client->fill($payload);
                    if (! $client->client_code) {
                        $client->client_code = 'NB-'.strtoupper(Str::random(5));
                    }
                    $client->save();
                    if ($assignServices) {
                        $this->assignPortfolioServices($client, $gstMeta['has_gst']);
                    }
                    if ($gstMeta['has_gst']) {
                        $withGst++;
                    }
                    $updated++;
                } else {
                    $payload['client_code'] = 'NB-'.strtoupper(Str::random(5));
                    if ($pan && Client::query()->where('pan', $pan)->exists()) {
                        unset($payload['pan']);
                    } elseif ($pan) {
                        $payload['pan'] = $pan;
                    }
                    if ($gstin && Client::query()->where('gstin', $gstin)->exists()) {
                        unset($payload['gstin']);
                    }
                    $client = Client::create($payload);
                    if ($assignServices) {
                        $this->assignPortfolioServices($client, $gstMeta['has_gst']);
                    }
                    if ($gstMeta['has_gst']) {
                        $withGst++;
                    }
                    $created++;
                }
            } catch (\Throwable) {
                $skipped++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'with_gst' => $withGst,
        ];
    }

    protected function assignPortfolioServices(Client $client, bool $includeGst): void
    {
        $itr = Service::query()->where('code', 'ITR')->first()
            ?? Service::query()->where('name', 'like', '%IT Return%')->first();
        $gst = Service::query()->where('code', 'GST')->first()
            ?? Service::query()->where('name', 'like', '%GST Return%')->first();

        if (! $itr) {
            return;
        }

        $sync = [
            $itr->id => [
                'status' => ClientService::STATUS_ACTIVE,
                'custom_due_day' => null,
            ],
        ];

        if ($includeGst && $gst) {
            $sync[$gst->id] = [
                'status' => ClientService::STATUS_ACTIVE,
                'custom_due_day' => null,
            ];
        }

        $client->optedServices()->sync($sync);
    }

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $itrMeta
     * @param  array<string, mixed>  $gstMeta
     */
    private function formatOfficeNotes(array $itrMeta, array $gstMeta): string
    {
        $lines = ['Portfolio: Nilesh Bhai — Income Tax Return'];

        if (! empty($itrMeta['pan'])) {
            $lines[] = 'PAN: '.$itrMeta['pan'];
        }
        if (! empty($itrMeta['assessment_years'])) {
            $lines[] = 'Assessment years (files): '.implode(', ', $itrMeta['assessment_years']);
        }
        if (! empty($itrMeta['acknowledgement'])) {
            $lines[] = 'ITR Acknowledgement: '.implode('; ', array_slice($itrMeta['acknowledgement'], 0, 5));
        }
        if (! empty($itrMeta['computation'])) {
            $lines[] = 'Computation of Income: '.implode('; ', array_slice($itrMeta['computation'], 0, 5));
        }

        if ($gstMeta['has_gst']) {
            $lines[] = 'GST: Yes (return / certificate / registration files in folder)';
        }
        if (! empty($gstMeta['gstin'])) {
            $lines[] = 'GSTIN: '.$gstMeta['gstin'];
        }
        if (! empty($gstMeta['certificates'])) {
            $lines[] = 'GST certificates / registration: '.implode('; ', $gstMeta['certificates']);
        }
        if (! empty($gstMeta['gst_files'])) {
            $lines[] = 'GST returns / challans: '.implode('; ', $gstMeta['gst_files']);
        }

        return implode("\n", $lines);
    }
}
