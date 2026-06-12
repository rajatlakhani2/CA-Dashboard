<?php

namespace App\Services;

use App\Models\User;
use App\Services\Intelligence\AiAssistantService;
use App\Support\Branding;
use App\Support\DemoWorkspace;
use App\Support\ModuleGate;

class DashboardHelpChatService
{
    /** @return array<int, array{label: string, prompt: string}> */
    public function quickPrompts(?User $user): array
    {
        $prompts = [
            ['label' => '🎯 Mission Control', 'prompt' => 'What is Mission Control?'],
            ['label' => '☀️ My Day', 'prompt' => 'How does My Day work?'],
            ['label' => '🔄 Client 360', 'prompt' => 'What is Client 360?'],
            ['label' => '📧 Invoice email', 'prompt' => 'How do I email an invoice?'],
            ['label' => '⚡ Quick search', 'prompt' => 'How do I use Ctrl+K?'],
        ];

        if (DemoWorkspace::isDemoUser($user)) {
            $prompts[] = ['label' => '✨ Demo tour', 'prompt' => 'How do I restart the demo tour?'];
        }

        return $prompts;
    }

    /**
     * @return array{ok: bool, text: string, url: ?string, source: string}
     */
    public function reply(?User $user, string $message, ?string $page = null): array
    {
        $message = trim($message);
        if ($message === '') {
            return [
                'ok' => false,
                'text' => 'Please type a question about the dashboard.',
                'url' => null,
                'source' => 'validation',
            ];
        }

        $topic = $this->matchTopic($user, $message);
        if ($topic) {
            return [
                'ok' => true,
                'text' => $topic['text'],
                'url' => $topic['url'] ?? null,
                'source' => 'help',
            ];
        }

        $ai = app(AiAssistantService::class);
        if ($ai->isEnabled() && $user) {
            $aiResult = $this->aiReply($ai, $user, $message, $page);
            if ($aiResult['ok']) {
                return $aiResult;
            }
        }

        return [
            'ok' => true,
            'text' => "I can help with Mission Control, My Day, Client 360°, workload, billing, invoices (email & WhatsApp), The Pulse, and WhatsApp reminders.\n\nTry a quick prompt below, press Ctrl+K to jump anywhere, or ask something specific.",
            'url' => null,
            'source' => 'fallback',
        ];
    }

    /** @return ?array{text: string, url: ?string} */
    private function matchTopic(?User $user, string $message): ?array
    {
        $q = strtolower($message);
        $brand = Branding::dashboardName();

        $topics = [
            [
                'keys' => ['mission control', 'command centre', 'command center', 'executive summary', 'dashboard overview', 'firm at a glance'],
                'text' => "🎯 Mission Control is your {$brand} home screen — today's deadlines, risk alerts, team workload, and accounts needing attention in one view.",
                'url' => $user && ModuleGate::allowed($user, 'dashboard') ? route('dashboard') : null,
                'module' => 'dashboard',
            ],
            [
                'keys' => ['my day', 'mobile work', 'due today', 'staff view'],
                'text' => "☀️ My Day is the focused view for associates — tasks due today or overdue, with Start, Done, notes, and time logging.",
                'url' => $user && ModuleGate::allowed($user, 'tasks') ? route('tasks.my-day') : null,
                'module' => 'tasks',
            ],
            [
                'keys' => ['client 360', 'client profile', 'health score', 'account 360'],
                'text' => "🔄 Client 360° puts compliance, finance, documents, tasks, and health score on one client page — no switching tools.",
                'url' => $user && ModuleGate::allowed($user, 'clients') ? route('clients.index') : null,
                'module' => 'clients',
            ],
            [
                'keys' => ['workload', 'reassign', 'drag task', 'kanban', 'overloaded'],
                'text' => "⚖️ Workload planner shows open tasks per team member. Drag cards between columns to reassign work instantly.",
                'url' => $user && ModuleGate::allowed($user, 'tasks') ? route('workload.index') : null,
                'module' => 'tasks',
            ],
            [
                'keys' => ['schedule', 'calendar', 'drag deadline', 'reschedule'],
                'text' => "📅 The Schedule tab on the dashboard combines tasks and deadlines. Drag events to move dates without opening a form.",
                'url' => $user && ModuleGate::allowed($user, 'dashboard') ? route('dashboard', ['tab' => 'calendar']) : null,
                'module' => 'dashboard',
            ],
            [
                'keys' => ['unbilled', 'billing queue', 'bill task', 'completed work'],
                'text' => "💼 The billing queue lists completed work not yet invoiced. Select items and create an invoice in a few clicks.",
                'url' => $user && ModuleGate::allowed($user, 'invoices') ? route('invoices.index', ['tab' => 'unbilled']) : null,
                'module' => 'invoices',
            ],
            [
                'keys' => ['email invoice', 'email an invoice', 'send invoice', 'mail invoice', 'invoice email', 'send by email'],
                'text' => "📧 Open any unpaid invoice → Email invoice sends the PDF to the client's billing email. WhatsApp reminder sends a payment nudge with amount and due date.",
                'url' => $user && ModuleGate::allowed($user, 'invoices') ? route('invoices.index', ['tab' => 'raised']) : null,
                'module' => 'invoices',
            ],
            [
                'keys' => ['whatsapp reminder', 'morning reminder', 'evening reminder', 'whatsapp digest'],
                'text' => "📱 WhatsApp reminders go out morning and evening with pending tasks. Staff get personal lists; leaders get a firm summary. Configure times under Notifications → WhatsApp.",
                'url' => $user && ModuleGate::allowed($user, 'settings') ? route('whatsapp.index') : null,
                'module' => 'settings',
            ],
            [
                'keys' => ['pulse', 'activity feed', 'activity log', 'what changed'],
                'text' => "💓 The Pulse is the firm activity feed — tasks updated, clients edited, invoices raised. Great for managers who want visibility without chasing updates.",
                'url' => $user && ModuleGate::allowed($user, 'activity') ? route('activity.index') : null,
                'module' => 'activity',
            ],
            [
                'keys' => ['ctrl+k', 'quick search', 'command palette', 'search'],
                'text' => "⚡ Press Ctrl+K to open quick search — jump to clients, tasks, invoices, or any page. Use # for actions and > for client search.",
                'url' => null,
                'module' => null,
            ],
            [
                'keys' => ['create task', 'new task', 'add task'],
                'text' => "✅ Use + Task on the dashboard or sidebar → Tasks → Create. Assign to yourself or the team and set a due date.",
                'url' => $user && ModuleGate::allowed($user, 'tasks') ? route('tasks.create') : null,
                'module' => 'tasks',
            ],
            [
                'keys' => ['create invoice', 'new invoice', 'raise invoice'],
                'text' => "🧾 Create from + Invoice on the dashboard, the billing queue, or Invoices → New. You can also bill from completed tasks in the unbilled tab.",
                'url' => $user && ModuleGate::allowed($user, 'invoices') ? route('invoices.create') : null,
                'module' => 'invoices',
            ],
            [
                'keys' => ['renewal', 'personal renewal', 'licence', 'license expiry'],
                'text' => "🔔 Personal renewals tracks licences, insurance, and key dates with list + calendar views and one-click WhatsApp reminders.",
                'url' => $user && ModuleGate::allowed($user, 'personal_renewals') ? route('personal-renewals.index') : null,
                'module' => 'personal_renewals',
            ],
            [
                'keys' => ['demo tour', 'guided tour', 'take a tour', 'restart tour'],
                'text' => "✨ Click Take a tour (bottom-right) to replay the guided demo. Partners see Mission Control, Client 360°, billing, Pulse, and WhatsApp flows.",
                'url' => null,
                'module' => null,
            ],
            [
                'keys' => ['hello', 'hi', 'help', 'start'],
                'text' => "👋 Hi! I'm your {$brand} help assistant. Ask how any feature works, or tap a quick prompt. I'll explain and open the right page when I can.",
                'url' => null,
                'module' => null,
            ],
        ];

        foreach ($topics as $topic) {
            $module = $topic['module'] ?? null;
            if ($module !== null && $user && ! ModuleGate::allowed($user, $module)) {
                continue;
            }

            foreach ($topic['keys'] as $key) {
                if (str_contains($q, $key)) {
                    return [
                        'text' => $topic['text'],
                        'url' => $topic['url'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @return array{ok: bool, text: string, url: ?string, source: string}
     */
    private function aiReply(AiAssistantService $ai, User $user, string $message, ?string $page): array
    {
        $role = $user->role ?? 'user';

        $system = 'You are a helpful dashboard guide for ' . Branding::dashboardName() . ', a professional services operations workspace. '
            . 'Answer briefly (2-5 sentences) about how to use dashboard features: Mission Control, My Day, Client 360, workload, schedule, billing, invoices (email & WhatsApp), The Pulse, Ctrl+K search, WhatsApp reminders. '
            . 'User role: ' . $role . '. '
            . 'Do not invent features. Do not give tax or legal advice. If unsure, suggest Ctrl+K or the sidebar.';

        $userPrompt = 'Current page: ' . ($page ?: 'unknown') . "\nQuestion: " . $message;

        $result = $ai->answerHelp($system, $userPrompt);

        if (! ($result['ok'] ?? false) || empty($result['text'])) {
            return [
                'ok' => false,
                'text' => $result['error'] ?? 'AI could not answer right now.',
                'url' => null,
                'source' => 'ai',
            ];
        }

        return [
            'ok' => true,
            'text' => trim($result['text']),
            'url' => null,
            'source' => 'ai',
        ];
    }
}
