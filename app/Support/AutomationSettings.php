<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Firm-level switches for Phase 3 automations. Every automation is OFF until a
 * partner turns it on in Settings → Automations. Values are stored in `settings`
 * for the current organisation (OrganizationContext).
 */
class AutomationSettings
{
    public const TIMEZONE = 'Asia/Kolkata';

    public const DEFAULTS = [
        'auto_morning_brief' => '0',
        'auto_morning_brief_hour' => '9',
        'auto_payment_reminders' => '0',
        'auto_payment_reminder_days' => '7,15,30',
        'auto_document_requests' => '0',
        'auto_document_request_days' => '5',
        'auto_client_hour' => '11',
        'auto_weekly_summary' => '0',
        'auto_weekly_summary_hour' => '8',
        'whatsapp_only_to' => '',
    ];

    public static function all(): array
    {
        $out = [];
        foreach (self::DEFAULTS as $key => $default) {
            $out[$key] = (string) Setting::get($key, $default);
        }

        return $out;
    }

    public static function enabled(string $key): bool
    {
        return (string) Setting::get($key, self::DEFAULTS[$key] ?? '0') === '1';
    }

    public static function int(string $key): int
    {
        return (int) Setting::get($key, self::DEFAULTS[$key] ?? '0');
    }

    /** @return list<int> e.g. [7, 15, 30] */
    public static function reminderDays(): array
    {
        $raw = (string) Setting::get('auto_payment_reminder_days', self::DEFAULTS['auto_payment_reminder_days']);
        $days = array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)), fn ($d) => $d > 0 && $d <= 365)));
        sort($days);

        return $days ?: [7, 15, 30];
    }

    public static function save(array $data): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            if (array_key_exists($key, $data)) {
                Setting::set($key, (string) $data[$key]);
            }
        }
    }

    public static function firmName(): string
    {
        return (string) (Setting::get('company_name') ?: Branding::dashboardName());
    }
}
