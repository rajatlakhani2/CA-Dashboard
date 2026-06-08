<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;

class ModuleGate
{
    /** Modules that cannot be disabled at firm level. */
    public const FIRM_ALWAYS_ON = ['settings', 'dashboard'];

    public static function groups(): array
    {
        return [
            'Command Centre' => ['dashboard'],
            'Clients & Documents' => ['clients', 'credentials', 'smart_documents'],
            'Work' => ['tasks', 'staff'],
            'Compliance' => ['service_dues', 'personal_renewals', 'compliance', 'dsc', 'tds'],
            'Finance' => ['invoices', 'billing', 'payments', 'expenses', 'subscriptions'],
            'Insights' => ['reports', 'activity'],
            'Administration' => ['settings', 'system'],
        ];
    }

    public static function firmModules(): array
    {
        $all = array_fill_keys(array_keys(ModuleAccess::MODULES), true);
        $raw = Setting::get('enabled_modules');

        if (! $raw) {
            return $all;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $all;
        }

        foreach (array_keys($all) as $key) {
            if (in_array($key, self::FIRM_ALWAYS_ON, true)) {
                $all[$key] = true;

                continue;
            }

            $all[$key] = (bool) ($decoded[$key] ?? true);
        }

        return $all;
    }

    public static function firmEnabled(string $module): bool
    {
        if (in_array($module, self::FIRM_ALWAYS_ON, true)) {
            return true;
        }

        return (bool) (self::firmModules()[$module] ?? true);
    }

    public static function allowed(?User $user, string $module): bool
    {
        if (! $user) {
            return false;
        }

        if (! self::firmEnabled($module)) {
            return false;
        }

        $access = $user->resolvedModuleAccess();

        return (bool) ($access[$module] ?? false);
    }

    public static function hasFinanceModule(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        foreach (['invoices', 'billing', 'payments', 'expenses', 'subscriptions'] as $module) {
            if (self::allowed($user, $module)) {
                return true;
            }
        }

        return false;
    }

    public static function saveFirmModules(array $input): void
    {
        $modules = [];
        foreach (array_keys(ModuleAccess::MODULES) as $key) {
            if (in_array($key, self::FIRM_ALWAYS_ON, true)) {
                $modules[$key] = true;

                continue;
            }

            $modules[$key] = (bool) ($input[$key] ?? false);
        }

        Setting::set('enabled_modules', json_encode($modules));
    }
}
