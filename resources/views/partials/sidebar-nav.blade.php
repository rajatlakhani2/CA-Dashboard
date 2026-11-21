{{-- Sidebar menu: VouchEx-style groups that open and close (remembered per browser).
     Every link keeps the same permission rule it had before. --}}
@php
    $user = auth()->user();
    $canManageFirm = $user?->managesFirmModules();
    $isPartner = $user?->isPartner();
    $isArticle = $user?->isArticle();
    $mod = fn (string $key) => $user?->canAccessModule($key) ?? false;
    $showFinance = \App\Support\ModuleGate::hasFinanceModule($user);
    $on = fn (...$patterns) => request()->routeIs(...$patterns);

    // Icons: Lucide outlines (24×24, stroke 2), chosen to match what each page is for.
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'my-tasks' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>',
        'add-client' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
        'clients' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'passwords' => '<path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/>',
        'doc-review' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h4"/><path d="M14 2v6h6"/><path d="M20 8v3"/><circle cx="16" cy="17" r="3"/><path d="m21 22-2.9-2.9"/>',
        'archive' => '<rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/>',
        'my-day' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
        'tasks' => '<path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8M13 12h8M13 18h8"/>',
        'staff' => '<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="11" r="2"/><path d="M5 16.5a3 3 0 0 1 6 0"/><path d="M14 10h5M14 14h3"/>',
        'workload' => '<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>',
        'time' => '<path d="M10 2h4M12 14l3-3"/><circle cx="12" cy="14" r="8"/>',
        'leaves' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m14 14-4 4M10 14l4 4"/>',
        'due-dates' => '<path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><path d="M16 2v4M8 2v4M3 10h5"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/>',
        'renewals' => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
        'dsc' => '<path d="M20 19.5v.5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8.5L18 5.5"/><path d="M8 18h1"/><path d="M18.42 9.61a2.1 2.1 0 1 1 2.97 2.97L16.95 17 13 18l.99-3.95 4.43-4.44Z"/>',
        'billing-queue' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'invoices' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
        'collections' => '<path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17"/><path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"/><path d="m2 16 6 6"/><circle cx="16" cy="9" r="2.9"/><circle cx="6" cy="5" r="3"/>',
        'payments' => '<path d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3"/><path d="M9 13c6.667 0 6.667-10 0-10"/>',
        'expenses' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
        'subscriptions' => '<path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/>',
        'compliance-360' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'service-report' => '<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/>',
        'income' => '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>',
        'due-report' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M17 14h-6M13 18H7M7 14h.01M17 18h.01"/>',
        'productivity' => '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
        'profitability' => '<circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/>',
        'whatsapp' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
        'pulse' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'recycle' => '<path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2M10 11v6M14 11v6"/>',
        'services' => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
        'system' => '<rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><path d="M6 6h.01M6 18h.01"/>',
    ];

    $item = fn (string $label, string $url, string $icon, bool $active, ?string $tour = null) => compact('label', 'url', 'icon', 'active', 'tour');

    $groups = [];
    if ($isArticle) {
        $items = [];
        if ($mod('tasks')) {
            $items[] = $item('My Tasks', route('tasks.index'), 'my-tasks', $on('tasks.index', 'tasks.edit', 'tasks.create', 'tasks.my-day'));
        }
        if ($user?->can('create', \App\Models\Client::class)) {
            $items[] = $item('Submit Client', route('clients.create'), 'add-client', $on('clients.create'));
        }
        $groups[] = ['My Work', $items];
    } else {
        if ($mod('dashboard')) {
            $groups[] = ['Overview', [$item('Dashboard', route('dashboard'), 'dashboard', $on('dashboard', 'dashboard.*'), 'nav-dashboard')]];
        }

        $items = [];
        if ($mod('clients')) {
            $items[] = $item('Clients', route('clients.index'), 'clients', $on('clients.*'), 'nav-clients');
        }
        if ($mod('credentials')) {
            $items[] = $item('Passwords', route('credentials.index'), 'passwords', $on('credentials.*'));
        }
        if ($canManageFirm) {
            $items[] = $item('Doc review', route('document-ingestions.index'), 'doc-review', $on('document-ingestions.*'));
        }
        if ($mod('smart_documents')) {
            $items[] = $item('Smart Archive', route('smart-documents.index'), 'archive', $on('smart-documents.*'));
        }
        $groups[] = ['Clients', $items];

        $items = [];
        if ($mod('tasks')) {
            $items[] = $item('My Day', route('tasks.my-day'), 'my-day', $on('tasks.my-day'));
            $items[] = $item('Tasks', route('tasks.index'), 'tasks', $on('tasks.*') && ! $on('tasks.my-day'), 'nav-tasks');
        }
        if ($mod('staff')) {
            $items[] = $item('Staff Directory', route('staff.index'), 'staff', $on('staff.*'));
        }
        if ($canManageFirm && $mod('tasks')) {
            $items[] = $item('Workload Planner', route('workload.index'), 'workload', $on('workload.*'), 'nav-workload');
        }
        if ($mod('tasks')) {
            $items[] = $item('Time Tracking', route('time-entries.index'), 'time', $on('time-entries.*'));
        }
        if ($mod('staff') && $canManageFirm) {
            $items[] = $item('Leaves', route('leaves.index'), 'leaves', $on('leaves.*'));
        }
        $groups[] = ['Work', $items];

        $items = [];
        if ($mod('service_dues')) {
            $items[] = $item('Reminders', route('service-dues.index'), 'due-dates', $on('service-dues.*'), 'nav-reminders');
        }
        if ($mod('personal_renewals')) {
            $items[] = $item('Personal Renewals', route('personal-renewals.index'), 'renewals', $on('personal-renewals.*'), 'nav-personal-renewals');
        }
        if ($mod('dsc')) {
            $items[] = $item('DSC Tracker', route('dscs.index'), 'dsc', $on('dscs.*'));
        }
        $groups[] = ['Compliance', $items];

        $items = [];
        if ($showFinance) {
            if ($mod('billing')) {
                $items[] = $item('Billing Queue', route('billing.index'), 'billing-queue', $on('billing.*'));
            }
            if ($mod('invoices') || $user?->canViewPortfolioInvoices()) {
                $items[] = $item($user?->isAssociate() ? 'My Client Invoices' : 'Invoices', route('invoices.index'), 'invoices', $on('invoices.*'), 'nav-invoices');
            }
            if ($mod('payments') && $canManageFirm) {
                $items[] = $item('Collections', route('collections.index'), 'collections', $on('collections.*'));
            }
            if ($mod('payments')) {
                $items[] = $item('Payments', route('payments.index'), 'payments', $on('payments.*'));
            }
            if ($mod('expenses')) {
                $items[] = $item('Expenses', route('expenses.index'), 'expenses', $on('expenses.*'));
            }
            if ($mod('subscriptions')) {
                $items[] = $item('Subscriptions', route('subscriptions.index'), 'subscriptions', $on('subscriptions.*'));
            }
        }
        $groups[] = ['Billing', $items];

        $items = [];
        if ($mod('reports') || $mod('compliance')) {
            $items[] = $item('Compliance 360°', route('compliance.index'), 'compliance-360', $on('compliance.index'));
            $items[] = $item('Service Report', route('reports.index').'?tab=service', 'service-report', $on('reports.index', 'reports.service', 'reports.client', 'reports.task'));
            $items[] = $item('Income Wise', route('reports.financial').'?tab=income', 'income', $on('reports.financial'));
            $items[] = $item('Due Date Report', route('reports.compliance').'?tab=due_date', 'due-report', $on('reports.compliance', 'reports.due-date'));
            if ($canManageFirm) {
                $items[] = $item('Staff Productivity', route('reports.staff-productivity'), 'productivity', $on('reports.staff-productivity'));
                $items[] = $item('Client Profitability', route('reports.client-profitability'), 'profitability', $on('reports.client-profitability'));
            }
        }
        $groups[] = ['Insights', $items];

        $items = [];
        if ($canManageFirm) {
            $items[] = $item('WhatsApp', route('whatsapp.index'), 'whatsapp', $on('whatsapp.*'));
        }
        if ($mod('activity')) {
            $items[] = $item('The Pulse', route('activity.index'), 'pulse', $on('activity.*'));
        }
        if ($canManageFirm) {
            $items[] = $item('Recycle Bin', route('recycle-bin.index'), 'recycle', $on('recycle-bin.*'));
        }
        if ($mod('service_dues') && $canManageFirm) {
            $items[] = $item('Service Master', route('services.index'), 'services', $on('services.*'));
        }
        if ($mod('system') && $isPartner) {
            $items[] = $item('System Health', route('system.index'), 'system', $on('system.*'));
        }
        $groups[] = ['Administration', $items];
    }

    $groups = array_values(array_filter($groups, fn ($g) => count($g[1]) > 0));
@endphp

<ul class="cd-nav" x-data="{
        key: 'cadash.sidebar.groups',
        open: {},
        init() {
            try { this.open = JSON.parse(localStorage.getItem(this.key) || '{}') || {}; } catch (e) { this.open = {}; }
        },
        isOpen(name, hasActive) { return hasActive || this.open[name] !== false; },
        toggle(name, hasActive) {
            this.open[name] = ! this.isOpen(name, hasActive);
            try { localStorage.setItem(this.key, JSON.stringify(this.open)); } catch (e) {}
        },
    }">
    @foreach($groups as [$heading, $items])
    @php
        $hasActive = collect($items)->contains('active', true);
        $slug = \Illuminate\Support\Str::slug($heading);
    @endphp
    <li class="cd-nav-group" :class="isOpen('{{ $heading }}', {{ $hasActive ? 'true' : 'false' }}) ? 'is-open' : 'is-closed'">
        <button type="button" class="cd-nav-heading" @click="toggle('{{ $heading }}', {{ $hasActive ? 'true' : 'false' }})"
            :aria-expanded="isOpen('{{ $heading }}', {{ $hasActive ? 'true' : 'false' }})" aria-controls="cd-nav-{{ $slug }}">
            <svg class="cd-nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            <span class="cd-nav-heading-label">{{ $heading }}</span>
            @if($hasActive)<span class="cd-nav-active-dot" aria-hidden="true"></span>@endif
        </button>
        <ul id="cd-nav-{{ $slug }}" class="cd-nav-items">
            @foreach($items as $it)
            <li>
                <a href="{{ $it['url'] }}" class="cd-nav-link {{ $it['active'] ? 'is-active' : '' }}" @if($it['active']) aria-current="page" @endif @if($it['tour']) data-tour="{{ $it['tour'] }}" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$it['icon']] !!}</svg>
                    <span>{{ $it['label'] }}</span>
                </a>
            </li>
            @endforeach
        </ul>
    </li>
    @endforeach
</ul>
