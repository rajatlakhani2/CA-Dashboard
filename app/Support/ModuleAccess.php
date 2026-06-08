<?php

namespace App\Support;

class ModuleAccess
{
    public const MODULES = [
        'dashboard' => 'Dashboard',
        'clients' => 'Clients',
        'tasks' => 'Tasks',
        'service_dues' => 'Service Dues',
        'personal_renewals' => 'Personal Renewals',
        'smart_documents' => 'Smart Archive',
        'invoices' => 'Invoices',
        'billing' => 'Billing Queue',
        'payments' => 'Payments',
        'expenses' => 'Expenses',
        'reports' => 'Reports',
        'staff' => 'Staff',
        'credentials' => 'Credentials',
        'compliance' => 'Compliance 360',
        'dsc' => 'DSC Tracker',
        'tds' => 'TDS',
        'subscriptions' => 'Subscriptions',
        'activity' => 'Activity Pulse',
        'settings' => 'Settings',
        'system' => 'System Health',
    ];

    public static function defaultsForRole(string $role): array
    {
        $all = array_fill_keys(array_keys(self::MODULES), true);

        return match (strtolower($role)) {
            'partner', 'manager', 'ceo' => $all,
            'associate' => array_merge($all, [
                'billing' => false,
                'payments' => false,
                'expenses' => false,
                'reports' => false,
                'staff' => false,
                'credentials' => false,
                'compliance' => false,
                'dsc' => false,
                'tds' => false,
                'subscriptions' => false,
                'activity' => false,
                'system' => false,
            ]),
            'article' => [
                'dashboard' => false,
                'clients' => true,
                'tasks' => true,
                'service_dues' => false,
                'personal_renewals' => false,
                'smart_documents' => false,
                'invoices' => false,
                'billing' => false,
                'payments' => false,
                'expenses' => false,
                'reports' => false,
                'staff' => false,
                'credentials' => false,
                'compliance' => false,
                'dsc' => false,
                'tds' => false,
                'subscriptions' => false,
                'activity' => false,
                'settings' => false,
                'system' => false,
            ],
            'staff', 'intern' => array_merge($all, [
                'billing' => false,
                'invoices' => false,
                'payments' => false,
                'expenses' => false,
                'subscriptions' => false,
                'reports' => false,
                'staff' => false,
                'credentials' => false,
                'compliance' => false,
                'dsc' => false,
                'tds' => false,
                'activity' => false,
                'system' => false,
            ]),
            default => array_merge($all, [
                'billing' => false,
                'payments' => false,
                'reports' => false,
                'staff' => false,
                'credentials' => false,
                'system' => false,
            ]),
        };
    }
}
