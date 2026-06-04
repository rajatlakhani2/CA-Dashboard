<?php

namespace App\Console\Commands;

use App\Services\NileshFolderImporter;
use Illuminate\Console\Command;

class ImportClientsNilesh extends Command
{
    protected $signature = 'import:clients-folder
                            {--path= : Root folder of client subfolders (required)}
                            {--no-services : Skip assigning IT Return / GST Return services}';

    protected $description = 'Import clients from a local IT Return folder tree (ITR + GST metadata)';

    public function handle(NileshFolderImporter $importer): int
    {
        $path = $this->option('path');
        if (! $path) {
            $this->error('Provide --path= to the folder containing client subfolders.');

            return self::FAILURE;
        }

        $this->info('Scanning: '.$path);
        $assignServices = ! $this->option('no-services');
        $result = $importer->run($path, $assignServices);

        if (isset($result['error'])) {
            $this->error($result['error']);

            return self::FAILURE;
        }

        $this->info("Created: {$result['created']} | Updated: {$result['updated']} | Skipped: {$result['skipped']}");
        if ($assignServices) {
            $this->info("Clients with GST Return assigned: {$result['with_gst']}");
        }

        return self::SUCCESS;
    }
}
