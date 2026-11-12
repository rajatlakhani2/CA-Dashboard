<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard layouts a user can pick in Settings or from the dashboard switcher.
 *
 * Resolution order: the user's own choice → the firm default → classic.
 * Layouts not built yet are listed but marked unavailable, and resolve to classic.
 */
class DashboardLayout
{
    public const CLASSIC = 'classic';
    public const INBOX = 'inbox';
    public const BOARD = 'board';
    public const CLIENT = 'client';

    public const FIRM_SETTING_KEY = 'default_dashboard_layout';

    /** Flip a layout to true once its view ships. */
    private const AVAILABLE = [
        self::CLASSIC => true,
        self::INBOX => false,
        self::BOARD => false,
        self::CLIENT => false,
    ];

    /** @return array<string, array{label: string, description: string, available: bool}> */
    public static function options(): array
    {
        $meta = [
            self::CLASSIC => ['Classic', 'The current dashboard: mission control, firm pulse, schedule and executive summary.'],
            self::INBOX => ['Action inbox', 'One prioritised list of filings, payments, DSCs and documents, with charts and a compliance calendar.'],
            self::BOARD => ['Work board', 'Kanban columns – overdue, due this week, waiting on client, in progress.'],
            self::CLIENT => ['Client first', 'Pick a client and see dues, unpaid invoices, documents and activity on one screen.'],
        ];

        $out = [];
        foreach ($meta as $key => [$label, $description]) {
            $out[$key] = [
                'label' => $label,
                'description' => $description,
                'available' => self::AVAILABLE[$key],
            ];
        }

        return $out;
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::AVAILABLE);
    }

    /** @return list<string> */
    public static function availableKeys(): array
    {
        return array_keys(array_filter(self::AVAILABLE));
    }

    public static function isAvailable(?string $layout): bool
    {
        return $layout !== null && (self::AVAILABLE[$layout] ?? false);
    }

    public static function firmDefault(): string
    {
        $value = (string) Setting::get(self::FIRM_SETTING_KEY, self::CLASSIC);

        return self::isAvailable($value) ? $value : self::CLASSIC;
    }

    /** The layout this user's dashboard should render. */
    public static function forUser(?User $user): string
    {
        $own = self::userChoice($user);

        if (self::isAvailable($own)) {
            return $own;
        }

        return self::firmDefault();
    }

    /** The user's own stored choice (null = follow firm default). */
    public static function userChoice(?User $user): ?string
    {
        if (! $user || ! self::columnExists()) {
            return null;
        }

        $value = $user->dashboard_layout;

        return in_array($value, self::keys(), true) ? $value : null;
    }

    private static function columnExists(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasColumn('users', 'dashboard_layout');
    }
}
