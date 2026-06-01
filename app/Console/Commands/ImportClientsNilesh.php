<?php

namespace App\Console\Commands;

use App\Services\NileshFolderImporter;
use Illuminate\Console\Command;

class ImportClientsNilesh extends Command
{
    protected $signature = 'import:clients-nilesh {--path= : Folder path} {--assign-service : Assign Income Tax Return service}';

    protected $description = 'Import Nilesh Bhai clients from IT Return folders with ITR ack/computation metadata';

    public function handle(NileshFolderImporter $importer): int
    {
        $path = $this->option('path') ?: 'D:\\New folder\\Rajat\\Rajat\\IT Return\\Nileshbhai';

        $this->info('Scanning: '.$path);
        $result = $importer->run($path, (bool) $this->option('assign-service'));

        if (isset($result['error'])) {
            $this->error($result['error']);

            return self::FAILURE;
        }

        $this->info("Created: {$result['created']} | Updated: {$result['updated']} | Skipped: {$result['skipped']}");

        return self::SUCCESS;
    }
}
