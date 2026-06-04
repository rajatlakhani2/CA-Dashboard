<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;

class ShowOrganizationSlug extends Command
{
    protected $signature = 'organization:slug {--set=}';

    protected $description = 'Show (or set) workspace login ID for the default organization';

    public function handle(): int
    {
        $org = Organization::orderBy('id')->first();
        if (! $org) {
            $this->error('No organizations. Run: php artisan migrate --force');

            return 1;
        }

        if ($slug = $this->option('set')) {
            $org->slug = strtolower($slug);
            $org->save();
            $this->info("Workspace ID set to: {$org->slug}");
        }

        $this->table(['ID', 'Name', 'Workspace ID (login)', 'Users', 'Plan'], [[
            $org->id,
            $org->name,
            $org->slug,
            $org->users()->count(),
            $org->plan,
        ]]);
        $this->line('Staff sign in at /login with Workspace ID + email + password.');

        return 0;
    }
}
