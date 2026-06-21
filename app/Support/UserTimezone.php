<?php

namespace App\Support;

use App\Models\User;

class UserTimezone
{
    public static function for(?User $user): string
    {
        $tz = $user?->timezone ?? null;

        if (is_string($tz) && $tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
            return $tz;
        }

        $appTz = config('app.timezone');

        return is_string($appTz) && $appTz !== '' ? $appTz : 'Asia/Kolkata';
    }

    /** @return array<string, string> */
    public static function selectOptions(): array
    {
        $labels = [
            'Asia/Kolkata' => 'India (IST — Asia/Kolkata)',
            'Asia/Dubai' => 'UAE (Asia/Dubai)',
            'Asia/Singapore' => 'Singapore',
            'UTC' => 'UTC',
            'Europe/London' => 'United Kingdom',
            'America/New_York' => 'US Eastern',
        ];

        $options = [];
        foreach ($labels as $id => $label) {
            if (in_array($id, timezone_identifiers_list(), true)) {
                $options[$id] = $label;
            }
        }

        return $options;
    }
}
