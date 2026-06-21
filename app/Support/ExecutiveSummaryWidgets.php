<?php

namespace App\Support;

use App\Models\User;

class ExecutiveSummaryWidgets
{
    /** @return array<string, bool> */
    public static function allowed(?User $user): array
    {
        if (! $user) {
            return [
                'exec-kpis' => true,
                'exec-calendar' => true,
                'exec-pulse' => true,
            ];
        }

        $showMyDay = $user->canAccessModule('tasks');
        $showTomorrow = $user->canAccessModule('tasks') || $user->canAccessModule('service_dues');
        $managesFirm = $user->managesFirmModules() || $user->isWorkspaceOwner();

        return array_filter([
            'exec-my-day' => $showMyDay,
            'exec-due-tomorrow' => $showTomorrow,
            'exec-kpis' => true,
            'exec-calendar' => true,
            'exec-pulse' => true,
            'exec-finance' => ModuleGate::hasFinanceModule($user),
            'exec-firm' => $managesFirm,
            'exec-empty-hint' => ! $showMyDay && ! $showTomorrow,
        ]);
    }

    /** @return list<string> */
    public static function defaultOrder(?User $user): array
    {
        $order = [
            'exec-my-day',
            'exec-due-tomorrow',
            'exec-kpis',
            'exec-calendar',
            'exec-pulse',
            'exec-finance',
            'exec-firm',
            'exec-empty-hint',
        ];
        $allowed = self::allowed($user);

        return array_values(array_filter(
            $order,
            fn (string $id) => (bool) ($allowed[$id] ?? false)
        ));
    }
}
