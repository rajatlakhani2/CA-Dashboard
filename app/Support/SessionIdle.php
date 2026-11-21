<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;

/** Firm setting: sign people out after this many minutes without activity (like VouchEx). */
class SessionIdle
{
    public const DEFAULT_MINUTES = 5;

    public const MIN_MINUTES = 1;

    public const MAX_MINUTES = 120;

    public static function minutesFor(?User $user): int
    {
        $previous = OrganizationContext::id();
        if (! $previous && $user?->organization_id) {
            OrganizationContext::set((int) $user->organization_id);
        }

        try {
            $value = (int) Setting::get('auto_logout_minutes', (string) self::DEFAULT_MINUTES);
        } finally {
            if (! $previous) {
                OrganizationContext::set(null);
            }
        }

        if ($value <= 0) {
            $value = self::DEFAULT_MINUTES;
        }

        return max(self::MIN_MINUTES, min(self::MAX_MINUTES, $value));
    }
}
