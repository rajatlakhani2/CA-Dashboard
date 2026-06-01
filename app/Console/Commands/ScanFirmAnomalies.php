<?php

namespace App\Console\Commands;

use App\Services\Intelligence\AnomalyScanner;
use Illuminate\Console\Command;

class ScanFirmAnomalies extends Command
{
    protected $signature = 'anomaly:scan';

    protected $description = 'Scan for firm anomalies (duplicate PAN, high outstanding, idle credentials, compliance stacks)';

    public function handle(AnomalyScanner $scanner): int
    {
        $result = $scanner->scan();

        $this->info(sprintf(
            'Anomaly scan complete. Active fingerprints: %d, auto-resolved stale: %d',
            $result['created'],
            $result['resolved']
        ));

        return self::SUCCESS;
    }
}
