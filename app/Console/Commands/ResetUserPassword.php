<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetUserPassword extends Command
{
    protected $signature = 'users:reset-password {email} {--password= : New password (min 10 characters). Leave empty to generate one}';

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

        $password = (string) $this->option('password');
        if ($password === '') {
            $password = \Illuminate\Support\Str::password(14, symbols: false);
        } elseif (strlen($password) < 10) {
            $this->error('Password must be at least 10 characters.');

            return self::FAILURE;
        }
        $user->forceFill(['password' => $password])->save();
        $this->line('New password: '.$password);

        $this->info("Password updated for {$user->email} ({$user->role}).");

        return self::SUCCESS;
    }
}
