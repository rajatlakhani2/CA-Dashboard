<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProductionBootstrap extends Command
{
    protected $signature = 'app:production-bootstrap';

    protected $description = 'Run production-safe migrations, firm logins, and clear caches (one command for cPanel)';

    public function handle(): int
    {
        $this->info('Running migrations...');
        $migrate = $this->call('migrate', ['--force' => true]);

        if ($migrate !== self::SUCCESS) {
            $this->error('Migrate failed — fix the error above, then run this command again.');

            return self::FAILURE;
        }

        $this->call('users:ensure-firm-logins');
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('view:clear');

        $this->newLine();
        $this->info('Production bootstrap complete.');
        $this->line('Login: https://app.kuhu.org.in/login');
        $this->line('  rajat@rlassociates.in / password');

        return self::SUCCESS;
    }
}
