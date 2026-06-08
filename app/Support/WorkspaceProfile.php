<?php

namespace App\Support;

use App\Models\Setting;

class WorkspaceProfile
{
    public const TYPE_CA_FIRM = 'ca_firm';

    public const TYPE_EXECUTIVE = 'executive';

    public static function types(): array
    {
        return [
            self::TYPE_CA_FIRM => 'CA Firm',
            self::TYPE_EXECUTIVE => 'Executive (CEO / CFO)',
        ];
    }

    public static function descriptions(): array
    {
        return [
            self::TYPE_CA_FIRM => 'Partners, managers, and articles — full client, compliance, and billing workflow.',
            self::TYPE_EXECUTIVE => 'CEO/CFO and managers — compliance, reminders, and oversight without firm billing modules.',
        ];
    }

    public static function current(): string
    {
        $type = (string) Setting::get('workspace_type', self::TYPE_CA_FIRM);

        return array_key_exists($type, self::types()) ? $type : self::TYPE_CA_FIRM;
    }

    public static function saveType(string $type): void
    {
        if (! array_key_exists($type, self::types())) {
            throw new \InvalidArgumentException("Unknown workspace type: {$type}");
        }

        Setting::set('workspace_type', $type);
    }

    /** @return array<string, string> */
    public static function roles(?string $type = null): array
    {
        $type ??= self::current();

        return match ($type) {
            self::TYPE_EXECUTIVE => [
                'ceo' => 'CEO / CFO',
                'manager' => 'Manager',
            ],
            default => [
                'partner' => 'Partner',
                'manager' => 'Manager',
                'article' => 'Article',
            ],
        };
    }

    /** @return array<string, string> */
    public static function roleHints(): array
    {
        return [
            'partner' => 'Full firm access including billing, users, and settings.',
            'manager' => 'Runs the team — tasks, clients, compliance, and reports.',
            'article' => 'Clerk workflow — clients and tasks only.',
            'ceo' => 'Workspace owner — oversight, compliance, reminders; finance modules follow firm toggles.',
            'manager' => 'Runs day-to-day work — tasks, clients, compliance, and reports.',
        ];
    }

    public static function roleHint(string $role): string
    {
        return self::roleHints()[$role] ?? '';
    }

    /** Recommended firm-wide module toggles per workspace type. */
    public static function modulePreset(?string $type = null): array
    {
        $type ??= self::current();
        $allOn = array_fill_keys(array_keys(ModuleAccess::MODULES), true);

        if ($type === self::TYPE_EXECUTIVE) {
            return array_merge($allOn, [
                'smart_documents' => false,
                'invoices' => false,
                'billing' => false,
                'payments' => false,
                'expenses' => false,
                'staff' => false,
                'credentials' => false,
                'tds' => false,
                'subscriptions' => false,
                'system' => false,
            ]);
        }

        return $allOn;
    }

    public static function applyModulePreset(?string $type = null): void
    {
        ModuleGate::saveFirmModules(self::modulePreset($type));
    }

    public static function isExecutive(): bool
    {
        return self::current() === self::TYPE_EXECUTIVE;
    }

    public static function isCaFirm(): bool
    {
        return self::current() === self::TYPE_CA_FIRM;
    }
}
