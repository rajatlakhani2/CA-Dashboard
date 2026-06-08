<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use App\Support\DemoWorkspace;
use App\Support\ModuleGate;

class DemoTourService
{
    public const DEMO_STAFF_NAME = 'Neha Kapoor';

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

    /** @return array<int, array<string, mixed>> */
    public function stepsFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $client360Url = $this->demoClientShowUrl($user);

        $catalog = [
            [
                'type' => 'modal',
                'modal' => 'whatsapp-morning',
                'title' => 'Your day starts on WhatsApp',
                'description' => 'Before anyone opens the app, Vouchex sends automated task reminders twice a day. Staff get their pending list; CEOs and managers get a firm-wide summary.',
            ],
            [
                'type' => 'spotlight',
                'url' => route('dashboard'),
                'element' => '[data-demo-tour="mission-control"]',
                'title' => 'Mission Control',
                'description' => 'CEOs and managers see what is due today, what is at risk, and who on the team is overloaded — all in one glance. CFOs spot outstanding and unbilled work here too.',
                'side' => 'bottom',
                'module' => 'dashboard',
            ],
            [
                'type' => 'spotlight',
                'url' => route('tasks.my-day'),
                'element' => '[data-demo-tour="my-day"]',
                'title' => 'My Day',
                'description' => 'Associates and staff get a focused mobile work view — due today, start/done actions, quick notes, and time logging. The same tasks from the morning WhatsApp land here.',
                'side' => 'bottom',
                'module' => 'tasks',
            ],
            [
                'type' => 'spotlight',
                'url' => $client360Url,
                'element' => '[data-demo-tour="client-360"]',
                'title' => 'Client 360°',
                'description' => 'One profile for everything about an account — health score, compliance, finance, documents, tasks, and portal access. No switching between CRM, billing, and file folders.',
                'side' => 'bottom',
                'module' => 'clients',
            ],
            [
                'type' => 'spotlight',
                'url' => route('dashboard', ['tab' => 'calendar']),
                'element' => '[data-demo-tour="schedule-calendar"]',
                'title' => 'Interactive schedule',
                'description' => 'Tasks and deadlines on one calendar. Drag an event to reschedule — the record updates without opening a form.',
                'side' => 'top',
                'tab' => 'calendar',
                'module' => 'dashboard',
            ],
            [
                'type' => 'spotlight',
                'url' => route('workload.index'),
                'element' => '[data-demo-tour="workload-board"]',
                'title' => 'Workload planner',
                'description' => 'Managers see open work per person. Drag a card from an overloaded column to someone with capacity — reassignment in one motion.',
                'side' => 'bottom',
                'module' => 'tasks',
                'role' => 'partner',
            ],
            [
                'type' => 'spotlight',
                'url' => route('personal-renewals.index'),
                'element' => '[data-demo-tour="renewals-view"]',
                'title' => 'Renewals & key dates',
                'description' => 'Licences, contracts, and personal renewals in a list for action and a calendar for planning. Send WhatsApp reminders in one click.',
                'side' => 'left',
                'module' => 'personal_renewals',
            ],
            [
                'type' => 'spotlight',
                'url' => route('invoices.index', ['tab' => 'unbilled']),
                'element' => '[data-demo-tour="unbilled-queue"]',
                'title' => 'Billing queue',
                'description' => 'CFOs: completed work should not sit unbilled. Select finished tasks, generate an invoice — fees flow from work you already tracked.',
                'side' => 'top',
                'module' => 'invoices',
            ],
            [
                'type' => 'spotlight',
                'url' => route('activity.index'),
                'element' => '[data-demo-tour="the-pulse"]',
                'title' => 'The Pulse',
                'description' => 'A live activity feed for the whole firm — who changed a task, updated a client, raised an invoice. Leadership stays informed without asking for status updates.',
                'side' => 'left',
                'module' => 'activity',
            ],
            [
                'type' => 'spotlight',
                'url' => route('dashboard'),
                'element' => '[data-tour="quick-search"]',
                'title' => 'Quick search (Ctrl+K)',
                'description' => 'Press Ctrl+K from anywhere. Jump to an account, task, invoice, or page — built for fast operations during calls and stand-ups.',
                'side' => 'bottom',
                'module' => null,
            ],
            [
                'type' => 'modal',
                'modal' => 'whatsapp-evening',
                'title' => 'Evening accountability on WhatsApp',
                'description' => 'A second reminder in the evening plus an end-of-day digest keeps staff accountable. Configure morning, evening, and digest times in automation settings.',
            ],
            [
                'type' => 'spotlight',
                'url' => route('whatsapp.index'),
                'element' => '[data-demo-tour="wa-reminder-settings"]',
                'title' => 'Reminder automation',
                'description' => 'Set morning and evening WhatsApp times and how many days ahead to include. Staff get personal lists; leadership gets the firm summary.',
                'side' => 'top',
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
            'welcome' => [
                'title' => 'Run your business from one workspace',
                'subtitle' => 'Whether you are a CEO, CFO, manager, or team member — see how Vouchex keeps work aligned from morning WhatsApp through evening wrap-up.',
                'bullets' => [
                    'Morning & evening WhatsApp reminders for staff and leadership',
                    'Mission Control, My Day, and Client 360° in one flow',
                    'Workload, billing queue, and The Pulse activity feed',
                ],
            ],
            'staffName' => self::DEMO_STAFF_NAME,
            'dismissUrl' => route('demo-tour.dismiss'),
            'completeUrl' => route('demo-tour.complete'),
            'isDemo' => DemoWorkspace::isDemoUser($user),
            'version' => 'workflow-v3-20260608',
        ];
    }

    private function demoClientShowUrl(?User $user): string
    {
        if (! $user) {
            return route('clients.index');
        }

        $client = Client::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->where('client_code', 'DEMO-ACME')
            ->first();

        return $client
            ? route('clients.show', $client)
            : route('clients.index');
    }
}
