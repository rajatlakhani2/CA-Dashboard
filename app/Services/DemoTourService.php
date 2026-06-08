<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
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
        $invoiceUrl = $this->demoInvoiceShowUrl($user);

        $catalog = [
            [
                'type' => 'modal',
                'modal' => 'whatsapp-morning',
                'emoji' => '📱',
                'title' => 'Morning WhatsApp',
                'tagline' => 'Your day starts before you open the laptop',
                'description' => 'Automated task reminders go out twice daily. Staff get their pending list; CEOs and managers get a firm-wide summary.',
            ],
            [
                'type' => 'spotlight',
                'url' => route('dashboard'),
                'element' => '[data-demo-tour="mission-control"]',
                'emoji' => '🎯',
                'title' => 'Mission Control',
                'tagline' => 'Leadership view in 10 seconds',
                'description' => 'See what is due today, what is at risk, and who is overloaded — plus outstanding and unbilled counts for CFOs.',
                'side' => 'bottom',
                'module' => 'dashboard',
            ],
            [
                'type' => 'spotlight',
                'url' => route('tasks.my-day'),
                'element' => '[data-demo-tour="my-day"]',
                'emoji' => '☀️',
                'title' => 'My Day',
                'tagline' => 'Focused work mode for every team member',
                'description' => 'Due today, start/done actions, quick notes, and time logging — the same tasks from the morning WhatsApp, ready to act on.',
                'side' => 'bottom',
                'module' => 'tasks',
            ],
            [
                'type' => 'spotlight',
                'url' => $client360Url,
                'element' => '[data-demo-tour="client-360"]',
                'emoji' => '🔄',
                'title' => 'Client 360°',
                'tagline' => 'One account, every detail',
                'description' => 'Health score, compliance, finance, documents, tasks, and portal access — no switching between CRM, billing, and folders.',
                'side' => 'bottom',
                'module' => 'clients',
            ],
            [
                'type' => 'spotlight',
                'url' => route('dashboard', ['tab' => 'calendar']),
                'element' => '[data-demo-tour="schedule-calendar"]',
                'emoji' => '📅',
                'title' => 'Interactive schedule',
                'tagline' => 'Drag deadlines, skip the forms',
                'description' => 'Tasks and renewals on one calendar. Drag an event to a new date and the record updates instantly.',
                'side' => 'top',
                'tab' => 'calendar',
                'module' => 'dashboard',
            ],
            [
                'type' => 'spotlight',
                'url' => route('workload.index'),
                'element' => '[data-demo-tour="workload-board"]',
                'emoji' => '⚖️',
                'title' => 'Workload planner',
                'tagline' => 'Balance the team in one drag',
                'description' => 'See open tasks per person and drag cards from overloaded columns to someone with capacity.',
                'side' => 'bottom',
                'module' => 'tasks',
                'role' => 'partner',
            ],
            [
                'type' => 'spotlight',
                'url' => route('personal-renewals.index'),
                'element' => '[data-demo-tour="renewals-view"]',
                'emoji' => '🔔',
                'title' => 'Renewals & key dates',
                'tagline' => 'Never miss a licence or contract',
                'description' => 'List view for action, calendar for planning, and one-click WhatsApp reminders when something is due.',
                'side' => 'left',
                'module' => 'personal_renewals',
            ],
            [
                'type' => 'spotlight',
                'url' => route('invoices.index', ['tab' => 'unbilled']),
                'element' => '[data-demo-tour="unbilled-queue"]',
                'emoji' => '💼',
                'title' => 'Billing queue',
                'tagline' => 'Turn completed work into revenue',
                'description' => 'Finished tasks surface here. Select items and generate invoices — fees flow from work you already tracked.',
                'side' => 'top',
                'module' => 'invoices',
            ],
            [
                'type' => 'spotlight',
                'url' => $invoiceUrl,
                'element' => '[data-demo-tour="invoice-send-actions"]',
                'emoji' => '📧',
                'title' => 'Send invoice by email & WhatsApp',
                'tagline' => 'Collect faster — one click to the client',
                'description' => 'Email the PDF invoice directly from Vouchex, or send a WhatsApp payment reminder with amount and due date — no copy-paste.',
                'side' => 'bottom',
                'module' => 'invoices',
            ],
            [
                'type' => 'spotlight',
                'url' => route('activity.index'),
                'element' => '[data-demo-tour="the-pulse"]',
                'emoji' => '💓',
                'title' => 'The Pulse',
                'tagline' => 'Know what changed across the firm',
                'description' => 'Live activity feed — tasks updated, clients edited, invoices raised. Leadership stays informed without status meetings.',
                'side' => 'left',
                'module' => 'activity',
            ],
            [
                'type' => 'spotlight',
                'url' => route('dashboard'),
                'element' => '[data-tour="quick-search"]',
                'emoji' => '⚡',
                'title' => 'Quick search (Ctrl+K)',
                'tagline' => 'Jump anywhere in one keystroke',
                'description' => 'Type an account, task, invoice, or page name — built for fast operations during calls and stand-ups.',
                'side' => 'bottom',
                'module' => null,
            ],
            [
                'type' => 'modal',
                'modal' => 'whatsapp-evening',
                'emoji' => '🌙',
                'title' => 'Evening WhatsApp',
                'tagline' => 'Close the day with accountability',
                'description' => 'A second evening reminder plus an end-of-day digest keeps staff on track overnight.',
            ],
            [
                'type' => 'spotlight',
                'url' => route('whatsapp.index'),
                'element' => '[data-demo-tour="wa-reminder-settings"]',
                'emoji' => '⏰',
                'title' => 'Reminder automation',
                'tagline' => 'Set it once, runs every day',
                'description' => 'Configure morning and evening WhatsApp times and how many days ahead to include in reminders.',
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
                'emoji' => '✨',
                'title' => 'Run your business from one workspace',
                'tagline' => 'From morning WhatsApp to evening wrap-up',
                'subtitle' => 'Whether you are a CEO, CFO, manager, or team member — see how Vouchex keeps work, clients, and collections in sync.',
                'bullets' => [
                    '📱 Morning & evening WhatsApp for staff and leadership',
                    '🔄 Mission Control, My Day, and Client 360°',
                    '📧 Invoice by email & WhatsApp — collect faster',
                ],
            ],
            'staffName' => self::DEMO_STAFF_NAME,
            'dismissUrl' => route('demo-tour.dismiss'),
            'completeUrl' => route('demo-tour.complete'),
            'isDemo' => DemoWorkspace::isDemoUser($user),
            'version' => 'workflow-v4-20260608',
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

    private function demoInvoiceShowUrl(?User $user): string
    {
        if (! $user) {
            return route('invoices.index');
        }

        $invoice = Invoice::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->where('invoice_number', 'DEMO-INV-001')
            ->first();

        return $invoice
            ? route('invoices.show', $invoice)
            : route('invoices.index', ['tab' => 'raised']);
    }
}
