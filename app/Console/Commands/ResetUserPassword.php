<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetUserPassword extends Command
{
    protected $signature = 'users:reset-password {email} {--password=password}';

    protected $description = 'Reset a user password (for production recovery)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            $this->error("No user found for: {$email}");
            $this->line('Run: php artisan users:ensure-firm-logins');

            return self::FAILURE;
        }

        $user->forceFill(['password' => (string) $this->option('password')])->save();

        $this->info("Password updated for {$user->email} ({$user->role}).");

        return self::SUCCESS;
    }
}
