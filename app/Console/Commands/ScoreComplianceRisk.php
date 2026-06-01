<?php

namespace App\Console\Commands;

use App\Services\Intelligence\ComplianceRiskScorer;
use Illuminate\Console\Command;

class ScoreComplianceRisk extends Command
{
    protected $signature = 'compliance:score-risk';

    protected $description = 'Calculate compliance risk scores per client and service';

    public function handle(ComplianceRiskScorer $scorer): int
    {
        $result = $scorer->score();
        $this->info('Scored ' . $result['scored'] . ' client/service risk rows.');

        return self::SUCCESS;
    }
}
