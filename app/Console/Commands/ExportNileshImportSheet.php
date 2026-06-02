<?php

namespace App\Console\Commands;

use App\Services\ImportClientsNileshMetadata;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportNileshImportSheet extends Command
{
    protected $signature = 'export:nilesh-import-sheet
                            {--path= : Folder path (default: Nileshbhai on this PC)}
                            {--output= : Output CSV path (default: storage/app/nilesh_clients_import.csv)}';

    protected $description = 'Build a small CSV/Excel-ready file from Nilesh client folders for upload to live site (no folder upload needed)';

    public function handle(ImportClientsNileshMetadata $metadata): int
    {
        $path = $this->option('path') ?: 'D:\\New folder\\Rajat\\Rajat\\IT Return\\Nileshbhai';
        $output = $this->option('output') ?: storage_path('app/nilesh_clients_import.csv');

        if (! File::isDirectory($path)) {
            $this->error("Directory not found: {$path}");

            return self::FAILURE;
        }

        $headers = [
            'name',
            'pan',
            'gstin',
            'entity_type',
            'status',
            'category',
            'primary_contact_name',
            'phone',
            'email',
            'registered_address',
            'services',
        ];

        $handle = fopen($output, 'w');
        if ($handle === false) {
            $this->error("Cannot write: {$output}");

            return self::FAILURE;
        }

        fputcsv($handle, $headers);

        $rows = 0;
        $withGst = 0;

        foreach (File::directories($path) as $dir) {
            $name = trim(basename($dir));
            if ($metadata->shouldSkipFolder($name)) {
                continue;
            }

            $itr = $metadata->extractItrMetadata($dir);
            $gst = $metadata->extractGstMetadata($dir);
            $pan = $itr['pan'] ?? $metadata->findPanInFiles($dir);
            $gstin = $gst['gstin'] ?? null;

            $services = ['IT Return'];
            if ($gst['has_gst']) {
                $services[] = 'GST Return';
                $withGst++;
            }

            fputcsv($handle, [
                $name,
                $pan ?? '',
                $gstin ?? '',
                '',
                'Active',
                'C',
                '',
                '',
                '',
                '',
                implode(', ', $services),
            ]);
            $rows++;
        }

        fclose($handle);

        $this->info("Wrote {$rows} client rows to:");
        $this->line($output);
        $this->info("Rows with GST Return in services column: {$withGst}");
        $this->newLine();
        $this->comment('Upload this file on app.kuhu.org.in → Clients → Preview import → Confirm.');

        return self::SUCCESS;
    }
}
