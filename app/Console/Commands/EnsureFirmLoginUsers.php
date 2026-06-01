<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnsureFirmLoginUsers extends Command
{
    protected $signature = 'users:ensure-firm-logins';

    protected $description = 'Create or update Rajat (partner), Nilesh Bhai (associate), and Article login accounts';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => 'Database\\Seeders\\FirmTeamSeeder',
            '--force' => true,
        ]);

        $this->table(
            ['ID', 'Name', 'Role', 'Email'],
            \App\Models\User::query()
                ->orderByRaw("CASE role WHEN 'partner' THEN 1 WHEN 'associate' THEN 2 WHEN 'article' THEN 3 ELSE 9 END")
                ->orderBy('name')
                ->get(['id', 'name', 'role', 'email'])
                ->map(fn ($user) => [$user->id, $user->name, $user->role, $user->email])
                ->all()
        );

        $this->info('Firm login accounts ready. Sign in with email + password (default: password). Mobile is required for daily task reminders.');

        return self::SUCCESS;
    }
}
