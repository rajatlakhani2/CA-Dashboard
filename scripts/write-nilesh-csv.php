<?php

/**
 * Export Nilesh client folder tree to CSV for review / import.
 *
 * Usage:
 *   php scripts/write-nilesh-csv.php --path="D:\path\to\client folders"
 *   php scripts/write-nilesh-csv.php --path="D:\path\to\client folders" --out=nilesh_clients_import.csv
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$path = null;
$out = 'nilesh_clients_import.csv';

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--path=')) {
        $path = substr($arg, 7);
    } elseif (str_starts_with($arg, '--out=')) {
        $out = substr($arg, 6);
    }
}

if (! $path) {
    fwrite(STDERR, "Usage: php scripts/write-nilesh-csv.php --path=\"/path/to/folders\" [--out=file.csv]\n");
    exit(1);
}

$export = new App\Exports\NileshClientsImportExport($path);
$handle = fopen($out, 'w');
foreach ($export->array() as $row) {
    fputcsv($handle, $row);
}
fclose($handle);

echo "CSV written: {$out}\n";
$dupes = $export->duplicatePanRows();
if ($dupes !== []) {
    echo 'Duplicate PAN rows skipped: '.count($dupes)."\n";
}
