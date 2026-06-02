<?php

namespace App\Console\Commands;

use App\Services\NileshFolderImporter;
use Illuminate\Console\Command;

class ImportClientsNilesh extends Command
{
    protected $signature = 'import:clients-nilesh
                            {--path= : Folder path (default: Nileshbhai on this PC)}
                            {--no-services : Skip assigning IT Return / GST Return services}';

    protected $description = 'Import Nilesh Bhai clients from folder tree with ITR + GST metadata and services';

    public function handle(NileshFolderImporter $importer): int
    {
        $path = $this->option('path') ?: 'D:\\New folder\\Rajat\\Rajat\\IT Return\\Nileshbhai';

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
