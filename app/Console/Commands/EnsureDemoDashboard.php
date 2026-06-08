<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnsureDemoDashboard extends Command
{
    protected $signature = 'demo:ensure-dashboard';

    protected $description = 'Create or refresh the public demo workspace (demodashboard / demo@vouchex.in)';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => 'Database\\Seeders\\DemoDashboardSeeder',
            '--force' => true,
        ]);

        $this->info('Demo workspace ready.');
        $this->line('  URL:       https://app.kuhu.org.in/login?workspace=demodashboard');
        $this->line('  Workspace: demodashboard');
        $this->line('  Email:     demo@vouchex.in');
        $this->line('  Password:  demo@1234');

        return self::SUCCESS;
    }
}
