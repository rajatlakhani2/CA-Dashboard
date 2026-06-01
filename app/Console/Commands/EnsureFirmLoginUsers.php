<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureFirmLoginUsers extends Command
{
    protected $signature = 'users:ensure-firm-logins';

    protected $description = 'Create or update Rajat (partner), Nilesh Bhai (associate), and Article login accounts';

    public function handle(): int
    {
        $this->ensureUserSchemaColumns();

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

    /**
     * Production DBs may be missing newer user columns if migrate stopped mid-way.
     */
    private function ensureUserSchemaColumns(): void
    {
        if (! Schema::hasTable('users')) {
            $this->warn('users table missing — run: php artisan migrate --force');

            return;
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('staff')->after('email');
            });
            $this->info('Added users.role column.');
        }

        if (! Schema::hasColumn('users', 'mobile')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('mobile', 20)->nullable();
            });
            $this->info('Added users.mobile column.');
        }

        if (! Schema::hasColumn('users', 'theme')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('theme')->default('modern');
            });
            $this->info('Added users.theme column.');
        }

        if (! Schema::hasColumn('users', 'module_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('module_access')->nullable();
            });
            $this->info('Added users.module_access column.');
        }
    }
}
