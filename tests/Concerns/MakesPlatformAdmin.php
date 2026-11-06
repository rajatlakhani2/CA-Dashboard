<?php

namespace Tests\Concerns;

use App\Models\Organization;
use App\Models\User;

/**
 * System Health / backups are limited to partners of the workspace named in
 * config('app.platform_admin_workspace'). This creates such a partner.
 */
trait MakesPlatformAdmin
{
    protected function platformAdmin(array $attributes = []): User
    {
        $org = Organization::firstOrCreate(
            ['slug' => 'platform-ops'],
            ['name' => 'Platform Ops', 'plan' => 'professional', 'seat_limit' => 10],
        );

        config(['app.platform_admin_workspace' => $org->slug]);

        return User::factory()->create(array_merge([
            'role' => 'partner',
            'organization_id' => $org->id,
        ], $attributes));
    }
}
