<?php

namespace App\Console\Commands;

use App\Exports\NileshClientsImportExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

class ExportNileshImportSheet extends Command
{
    protected $signature = 'export:nilesh-import-sheet
                            {--path= : Folder path (default: Nileshbhai on this PC)}
                            {--output= : Output .xlsx path (default: storage/app/nilesh_clients_import.xlsx)}
                            {--include-missing-pan : Include rows without PAN (default: skip them)}';

    protected $description = 'Build dashboard-format Excel from Nilesh client folders for Preview import upload';

    public function handle(): int
    {
        $path = $this->option('path') ?: 'D:\\New folder\\Rajat\\Rajat\\IT Return\\Nileshbhai';
        $output = $this->option('output') ?: storage_path('app/nilesh_clients_import.xlsx');

        if (! File::isDirectory($path)) {
            $this->error("Directory not found: {$path}");

            return self::FAILURE;
        }

        $skipMissingPan = ! $this->option('include-missing-pan');
        $export = new NileshClientsImportExport($path, skipMissingPan: $skipMissingPan);
        $rows = $export->array();
        $duplicates = $export->duplicatePanRows();
        $clientRows = max(0, count($rows) - 1);

        $withPan = 0;
        $withGst = 0;
        foreach (array_slice($rows, 1) as $row) {
            if (! empty($row[4])) {
                $withPan++;
            }
            if (str_contains((string) ($row[14] ?? ''), 'GST Return')) {
                $withGst++;
            }
        }

        File::ensureDirectoryExists(dirname($output));
        File::put($output, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        $desktop = $this->desktopPath();
        if ($desktop) {
            $desktopFile = $desktop.DIRECTORY_SEPARATOR.'nilesh_clients_import.xlsx';
            File::copy($output, $desktopFile);
            $this->info("Also copied to: {$desktopFile}");
        }

        $this->info("Wrote {$clientRows} clients (dashboard import format).");
        $this->line($output);
        $this->info("With PAN: {$withPan} | With GST Return service: {$withGst}");
        if ($skipMissingPan) {
            $this->comment('Skipped folders with no PAN (masked XXXX ignored; other PDFs scanned).');
        }
        if (count($duplicates) > 0) {
            $dupPath = dirname($output).DIRECTORY_SEPARATOR.'nilesh_duplicate_pans.csv';
            $dupHandle = fopen($dupPath, 'w');
            if ($dupHandle) {
                fputcsv($dupHandle, ['pan', 'duplicate_folder_name', 'kept_folder_name']);
                foreach ($duplicates as $dup) {
                    fputcsv($dupHandle, [$dup['pan'], $dup['name'], $dup['kept_name']]);
                    $this->warn("Duplicate PAN {$dup['pan']}: skipped \"{$dup['name']}\" (kept \"{$dup['kept_name']}\")");
                }
                fclose($dupHandle);
                $this->comment("Duplicate report: {$dupPath}");
            }
        }
        $this->newLine();
        $this->comment('Upload on app.kuhu.org.in → Clients → Preview import → Confirm.');
        $this->comment('Do not change row 1 column headings.');

        return self::SUCCESS;
    }

    protected function desktopPath(): ?string
    {
        $home = getenv('USERPROFILE') ?: getenv('HOME');
        if (! $home) {
            return null;
        }

        $desktop = $home.DIRECTORY_SEPARATOR.'Desktop';
        if (File::isDirectory($desktop)) {
            return $desktop;
        }

        $oneDrive = $home.DIRECTORY_SEPARATOR.'OneDrive'.DIRECTORY_SEPARATOR.'Desktop';
        if (File::isDirectory($oneDrive)) {
            return $oneDrive;
        }

        return null;
    }
}
