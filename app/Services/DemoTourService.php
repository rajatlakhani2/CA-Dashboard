<?php

namespace App\Services;

use App\Models\User;
use App\Support\DemoWorkspace;
use App\Support\ModuleGate;

class DemoTourService
{
    public function shouldShowWelcome(?User $user): bool
    {
        if (! DemoWorkspace::isDemoUser($user)) {
            return false;
        }

        if (session('demo_tour_pending')) {
            return true;
        }

        if ($user->demo_tour_completed_at) {
            return false;
        }

        return ! session('demo_tour_dismissed');
    }

    /** @return array<int, array{element: string, title: string, description: string, side: string}> */
    public function stepsFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $catalog = [
            [
                'element' => '[data-tour="nav-dashboard"]',
                'title' => 'Dashboard',
                'description' => 'Your command centre — overview, calendar schedule, workload, and firm metrics in one place.',
                'side' => 'right',
                'module' => 'dashboard',
            ],
            [
                'element' => '[data-tour="nav-clients"]',
                'title' => 'Clients',
                'description' => 'Manage client profiles, compliance worksheets, portal access, and documents.',
                'side' => 'right',
                'module' => 'clients',
            ],
            [
                'element' => '[data-tour="nav-tasks"]',
                'title' => 'Tasks',
                'description' => 'Create work, assign to your team, and track status from pending to completed.',
                'side' => 'right',
                'module' => 'tasks',
            ],
            [
                'element' => '[data-tour="nav-workload"]',
                'title' => 'Workload Planner',
                'description' => 'See open tasks by assignee and drag cards to reassign work across the team.',
                'side' => 'right',
                'module' => 'tasks',
                'role' => 'partner',
            ],
            [
                'element' => '[data-tour="nav-reminders"]',
                'title' => 'Reminders',
                'description' => 'Service dues and compliance deadlines so nothing slips past filing dates.',
                'side' => 'right',
                'module' => 'service_dues',
            ],
            [
                'element' => '[data-tour="nav-personal-renewals"]',
                'title' => 'Personal Renewals',
                'description' => 'Track DSC, memberships, and personal renewals with list + calendar views.',
                'side' => 'right',
                'module' => 'personal_renewals',
            ],
            [
                'element' => '[data-tour="nav-invoices"]',
                'title' => 'Invoices',
                'description' => 'Raise invoices from completed work, track unbilled tasks, and manage collections.',
                'side' => 'right',
                'module' => 'invoices',
            ],
            [
                'element' => '[data-tour="quick-search"]',
                'title' => 'Quick Search',
                'description' => 'Press Ctrl+K to jump to clients, tasks, invoices, or any page instantly.',
                'side' => 'bottom',
                'module' => null,
            ],
            [
                'element' => '[data-tour="nav-settings"]',
                'title' => 'Settings',
                'description' => 'Firm name, enabled modules, team users, and branding — configure your workspace here.',
                'side' => 'left',
                'module' => 'settings',
            ],
        ];

        return array_values(array_filter($catalog, function (array $step) use ($user) {
            if (($step['role'] ?? null) === 'partner' && ! $user->isWorkspaceOwner()) {
                return false;
            }

            $module = $step['module'] ?? null;
            if ($module === null) {
                return true;
            }

            return ModuleGate::allowed($user, $module);
        }));
    }

    public function payloadFor(?User $user): array
    {
        return [
            'show' => $this->shouldShowWelcome($user),
            'steps' => $this->stepsFor($user),
            'dismissUrl' => route('demo-tour.dismiss'),
            'completeUrl' => route('demo-tour.complete'),
            'isDemo' => DemoWorkspace::isDemoUser($user),
        ];
    }
}
