<?php

namespace App\Support;

use App\Models\User;

class DemoWorkspace
{
    public const SLUG = 'demodashboard';

    public const EMAIL = 'demo@vouchex.in';

    public const PASSWORD = 'demo@1234';

    public static function isDemoOrganization(?\App\Models\Organization $organization): bool
    {
        if (! $organization) {
            return false;
        }

        return strtolower((string) $organization->slug) === self::SLUG
            || (bool) $organization->is_demo;
    }

    public static function isDemoUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return self::isDemoOrganization($user->organization);
    }
}
