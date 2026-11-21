@extends('public.layout')

@section('title', 'Practice management software for CA firms')

@php
    $check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
    $i = fn (string $paths) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
    $icon = [
        'clients' => $i('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'),
        'due' => $i('<path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><path d="M16 2v4M8 2v4M3 10h5"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/>'),
        'tasks' => $i('<path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8M13 12h8M13 18h8"/>'),
        'team' => $i('<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>'),
        'billing' => $i('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>'),
        'key' => $i('<path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/>'),
        'reports' => $i('<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/>'),
        'bell' => $i('<path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10 20a2 2 0 0 0 4 0"/>'),
        'partner' => $i('<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>'),
        'manager' => $i('<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>'),
        'staff' => $i('<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>'),
        'client-portal' => $i('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/>'),
        'lock' => $i('<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>'),
        'eye' => $i('<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>'),
        'clock' => $i('<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>'),
        'history' => $i('<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>'),
        'db' => $i('<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/>'),
        'building' => $i('<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>'),
        'globe' => $i('<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'),
        'phone' => $i('<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>'),
    ];
@endphp

@section('content')
{{-- Hero --}}
<section class="pb-hero">
    <div class="pb-wrap">
        <p class="pb-eyebrow">Practice management for CA firms</p>
        <h1>Run your whole CA practice from <span>one secure dashboard</span></h1>
        <p class="pb-hero__sub">Clients, statutory due dates, daily tasks, billing and your team in one place. Built for Indian chartered accountancy firms of every size, from a sole practitioner to a multi-branch firm, on the laptop and on the phone.</p>
        <div class="pb-hero__actions">
            <a href="{{ route('register.organization') }}" class="pb-btn pb-btn--primary pb-btn--lg">Create your firm’s workspace</a>
            <a href="{{ route('login') }}" class="pb-btn pb-btn--outline pb-btn--lg">Sign in</a>
        </div>
        <ul class="pb-trust">
            <li>{!! $check !!} Set up in a few minutes</li>
            <li>{!! $check !!} Your firm’s data kept separate from every other firm</li>
            <li>{!! $check !!} Works on laptop and phone</li>
        </ul>
    </div>
</section>

{{-- Who it is for --}}
<section class="pb-section pb-section--tint" id="roles">
    <div class="pb-wrap">
        <h2 class="pb-title">The right view for everyone in the firm</h2>
        <p class="pb-lead">One shared workspace for the whole firm, shown differently depending on who signs in.</p>
        <div class="pb-grid pb-grid--4">
            <article data-reveal style="--d: 0" class="pb-card pb-role">
                <span class="pb-icon">{!! $icon['partner'] !!}</span>
                <div><h3>Partners</h3><p>See the whole firm at a glance: overdue filings, unpaid invoices, team workload and client health. Approve new clients and control who can open which module.</p></div>
            </article>
            <article data-reveal style="--d: 1" class="pb-card pb-role">
                <span class="pb-icon">{!! $icon['manager'] !!}</span>
                <div><h3>Managers</h3><p>Plan and assign work across the team, balance workload, follow up on documents and keep the clients you manage on schedule.</p></div>
            </article>
            <article data-reveal style="--d: 2" class="pb-card pb-role">
                <span class="pb-icon">{!! $icon['staff'] !!}</span>
                <div><h3>Staff and articles</h3><p>Start the day with “My Day”: what is due today and overdue, one tap to start or finish, add notes and log time from the phone.</p></div>
            </article>
            <article data-reveal style="--d: 3" class="pb-card pb-role">
                <span class="pb-icon">{!! $icon['client-portal'] !!}</span>
                <div><h3>Clients</h3><p>Clients do not get an account. When documents are needed, the firm sends a secure, time-limited link where the client can upload them.</p></div>
            </article>
        </div>
    </div>
</section>

{{-- Features --}}
<section class="pb-section" id="features">
    <div class="pb-wrap">
        <h2 class="pb-title">Everything a practice runs on</h2>
        <p class="pb-lead">Built around the daily work of an Indian CA firm, from the first client document to the final receipt.</p>
        <div class="pb-grid pb-grid--4">
            <article data-reveal style="--d: 0" class="pb-card">
                <span class="pb-icon">{!! $icon['clients'] !!}</span>
                <h3>Clients and onboarding</h3>
                <p>One profile per client with everything in one view.</p>
                <ul>
                    <li>Client 360: services, tasks, invoices and timeline</li>
                    <li>Health score for compliance, payments and documents</li>
                    <li>Onboarding checklist and approval of new clients</li>
                    <li>Import client lists from Excel</li>
                </ul>
            </article>
            <article data-reveal style="--d: 1" class="pb-card">
                <span class="pb-icon">{!! $icon['due'] !!}</span>
                <h3>Compliance and due dates</h3>
                <p>Statutory deadlines worked out for you, client by client.</p>
                <ul>
                    <li>Due dates generated from each client’s services</li>
                    <li>Calendar of GST, TDS, ITR, ROC and other filings</li>
                    <li>Document checklist per service</li>
                    <li>DSC expiry and personal renewal tracking</li>
                </ul>
            </article>
            <article data-reveal style="--d: 2" class="pb-card">
                <span class="pb-icon">{!! $icon['tasks'] !!}</span>
                <h3>Tasks and the work board</h3>
                <p>Every piece of work has an owner, a due date and a status.</p>
                <ul>
                    <li>My Day for each person, Work board for the firm</li>
                    <li>Swipe to finish or move to tomorrow on the phone</li>
                    <li>Notes, time logs and task templates</li>
                    <li>Bulk clean-up of old overdue items</li>
                </ul>
            </article>
            <article data-reveal style="--d: 3" class="pb-card">
                <span class="pb-icon">{!! $icon['team'] !!}</span>
                <h3>Team and workload</h3>
                <p>Know who is doing what before deadlines slip.</p>
                <ul>
                    <li>Workload planner across staff and branches</li>
                    <li>Staff directory, leaves and time tracking</li>
                    <li>Roles: partner, manager, associate, staff, article</li>
                    <li>Module access set per person</li>
                </ul>
            </article>
            <article data-reveal style="--d: 0" class="pb-card">
                <span class="pb-icon">{!! $icon['billing'] !!}</span>
                <h3>Billing and collections</h3>
                <p>Finished work flows straight into billing.</p>
                <ul>
                    <li>Billing queue of completed, unbilled work</li>
                    <li>GST invoices as PDF, payment receipts</li>
                    <li>Collections list of who to call today</li>
                    <li>Client ledger, expenses and subscriptions</li>
                </ul>
            </article>
            <article data-reveal style="--d: 1" class="pb-card">
                <span class="pb-icon">{!! $icon['key'] !!}</span>
                <h3>Passwords and portals</h3>
                <p>Client portal logins kept safely, opened in one click.</p>
                <ul>
                    <li>Encrypted vault for GST, Income Tax and TRACES logins</li>
                    <li>Every view of a password is recorded</li>
                    <li>Quick launch of government portals</li>
                    <li>Smart archive for client documents</li>
                </ul>
            </article>
            <article data-reveal style="--d: 2" class="pb-card">
                <span class="pb-icon">{!! $icon['reports'] !!}</span>
                <h3>Reports and insights</h3>
                <p>Numbers partners actually use.</p>
                <ul>
                    <li>Compliance 360° across all clients</li>
                    <li>Service, income and due-date reports</li>
                    <li>Staff productivity and client profitability</li>
                    <li>Activity log of everything that changed</li>
                </ul>
            </article>
            <article data-reveal style="--d: 3" class="pb-card">
                <span class="pb-icon">{!! $icon['bell'] !!}</span>
                <h3>Reminders and automation</h3>
                <p>The right nudge at the right time, without chasing.</p>
                <ul>
                    <li>Email and WhatsApp reminders you switch on</li>
                    <li>Morning brief of the day’s dues</li>
                    <li>Phone alerts for new tasks and payments</li>
                    <li>Payment and document follow-ups</li>
                </ul>
            </article>
        </div>
    </div>
</section>

{{-- How it works --}}
<section class="pb-section pb-section--tint" id="how">
    <div class="pb-wrap">
        <h2 class="pb-title">How work moves through the firm</h2>
        <p class="pb-lead">From setting up a client to collecting the fee, every step is in one place.</p>
        <div class="pb-grid pb-grid--4 pb-steps">
            <article data-reveal style="--d: 0" class="pb-card pb-step">
                <h3>Set up clients and services</h3>
                <p>Add or import clients and tick the services you handle for them. Their due dates are created automatically.</p>
            </article>
            <article data-reveal style="--d: 1" class="pb-card pb-step">
                <h3>Assign the work</h3>
                <p>Turn due dates into tasks for the right person. Everyone sees their own list in My Day.</p>
            </article>
            <article data-reveal style="--d: 2" class="pb-card pb-step">
                <h3>Collect documents and finish</h3>
                <p>Request documents through a secure client link, track the checklist, and mark the filing done.</p>
            </article>
            <article data-reveal style="--d: 3" class="pb-card pb-step">
                <h3>Bill and get paid</h3>
                <p>Completed work lands in the billing queue. Raise the invoice, record the payment, follow up on dues.</p>
            </article>
        </div>
    </div>
</section>

{{-- Security --}}
<section class="pb-section" id="security">
    <div class="pb-wrap">
        <h2 class="pb-title">Built for confidential client data</h2>
        <p class="pb-lead">A CA firm holds its clients’ most sensitive information. CA Dashboard is designed around that.</p>
        <div class="pb-grid pb-grid--4">
            <article data-reveal style="--d: 0" class="pb-card"><span class="pb-icon">{!! $icon['building'] !!}</span><h3>Separate firm workspaces</h3><p>Each firm signs in to its own workspace. One firm can never see another firm’s clients or work.</p></article>
            <article data-reveal style="--d: 1" class="pb-card"><span class="pb-icon">{!! $icon['eye'] !!}</span><h3>Role and module access</h3><p>People only see the clients and modules their role allows. Partners decide who can open finance, passwords or reports.</p></article>
            <article data-reveal style="--d: 2" class="pb-card"><span class="pb-icon">{!! $icon['lock'] !!}</span><h3>Encrypted passwords</h3><p>Saved portal logins are encrypted, and every time someone reveals one it is written to the audit log.</p></article>
            <article data-reveal style="--d: 3" class="pb-card"><span class="pb-icon">{!! $icon['clock'] !!}</span><h3>Automatic sign-out</h3><p>Idle sessions end on their own, and the phone app can be locked with fingerprint or face unlock.</p></article>
            <article data-reveal style="--d: 0" class="pb-card"><span class="pb-icon">{!! $icon['history'] !!}</span><h3>Full activity history</h3><p>Changes to clients, tasks, invoices and payments are logged, with who made them and when.</p></article>
            <article data-reveal style="--d: 1" class="pb-card"><span class="pb-icon">{!! $icon['db'] !!}</span><h3>Daily backups</h3><p>The database is backed up every day, and deleted records go to a recycle bin before they are gone for good.</p></article>
            <article data-reveal style="--d: 2" class="pb-card"><span class="pb-icon">{!! $icon['globe'] !!}</span><h3>Secure connection</h3><p>All traffic between your device and the server is encrypted over HTTPS.</p></article>
            <article data-reveal style="--d: 3" class="pb-card"><span class="pb-icon">{!! $icon['partner'] !!}</span><h3>Client approvals</h3><p>Clients added by staff or articles wait for a partner’s approval before they become active.</p></article>
        </div>
    </div>
</section>

{{-- Mobile --}}
<section class="pb-section pb-section--tint" id="mobile">
    <div class="pb-wrap">
        <div class="pb-grid pb-grid--2" style="align-items: center; gap: 32px;">
            <div>
                <p class="pb-eyebrow">On the phone</p>
                <h2 class="pb-title" style="text-align: left;">Your firm in your pocket</h2>
                <p style="color: #cbd5e1; margin: 0 0 20px;">The Android app and the iPhone home-screen app open the same live workspace, so everything you do on the phone is instantly on the laptop too.</p>
                <a href="{{ route('app.download') }}" class="pb-btn pb-btn--primary">Get the mobile app</a>
            </div>
            <article data-reveal style="--d: 0" class="pb-card">
                <span class="pb-icon">{!! $icon['phone'] !!}</span>
                <h3>Made for the day out of office</h3>
                <ul>
                    <li>My Day and the Work board, sized for one hand</li>
                    <li>Swipe a task to finish it or move it to tomorrow</li>
                    <li>Call or WhatsApp a client in one tap</li>
                    <li>Scan client documents with the camera</li>
                    <li>Alerts for new tasks, dues tomorrow and payments</li>
                    <li>Fingerprint or face lock, light and dark mode</li>
                </ul>
            </article>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="pb-section" id="faq">
    <div class="pb-wrap">
        <h2 class="pb-title">Common questions</h2>
        <p class="pb-lead">For partners setting up the firm and for team members signing in for the first time.</p>
        <div class="pb-faq">
            <details data-reveal>
                <summary>Who can use CA Dashboard?</summary>
                <p>Any chartered accountancy firm, from a sole practitioner to a firm with several partners and branches. Firms handling GST, income tax, TDS, ROC and audit work for their clients get the most out of it.</p>
            </details>
            <details data-reveal>
                <summary>How do we get started?</summary>
                <p>A partner clicks “Create your firm’s workspace”, enters the firm’s name and chooses a workspace ID. Then add your clients (one by one or from Excel), tick the services you handle for each, and add your team. Due dates and tasks start appearing straight away.</p>
            </details>
            <details data-reveal>
                <summary>How does my team sign in?</summary>
                <p>The partner adds each team member with their email and role. They sign in with the firm’s workspace ID, their email and their password, on the website or in the mobile app.</p>
            </details>
            <details data-reveal>
                <summary>I forgot my password or workspace ID. What should I do?</summary>
                <p>Use “Forgot password?” on the sign-in page to reset your password by email. Your firm’s partner can tell you the workspace ID, or write to us at the address below.</p>
            </details>
            <details data-reveal>
                <summary>Can our clients sign in?</summary>
                <p>No. Clients never get an account. When you need documents, the firm sends the client a secure, time-limited link where they can upload files without seeing anything else.</p>
            </details>
            <details data-reveal>
                <summary>Will every staff member see all clients?</summary>
                <p>No. What each person sees depends on their role and the modules a partner allows. Staff and articles see the work assigned to them; partners decide who can open finance, passwords, reports and administration.</p>
            </details>
            <details data-reveal>
                <summary>Does CA Dashboard file returns or replace accounting software?</summary>
                <p>No. It is a practice-management tool: it tracks clients, due dates, work, billing and the team. Returns are still filed on the government portals and books kept in your accounting software. CA Dashboard helps you remember, assign, follow up and bill.</p>
            </details>
            <details data-reveal>
                <summary>Does it work on the phone?</summary>
                <p>Yes. Every page works in the phone’s browser, and there is an Android app plus an iPhone home-screen app with phone features such as alerts, fingerprint lock and camera scanning.</p>
            </details>
            <details data-reveal>
                <summary>How is our data protected?</summary>
                <p>Each firm has its own separate workspace, access follows roles and module permissions, portal passwords are encrypted, every change is logged, sessions sign out when idle, and the database is backed up daily. See the <a href="{{ route('privacy') }}">Privacy Policy</a> for details.</p>
            </details>
        </div>
    </div>
</section>

{{-- Final call to action --}}
<section class="pb-section" style="padding-top: 8px;">
    <div class="pb-wrap pb-wrap--narrow">
        <div class="pb-cta" data-reveal>
            <h2>Bring your whole practice into one place</h2>
            <p>Create your firm’s workspace in a few minutes, or sign in if your firm already uses CA Dashboard.</p>
            <div class="pb-hero__actions" style="margin-bottom:0">
                <a href="{{ route('register.organization') }}" class="pb-btn pb-btn--primary pb-btn--lg">Create your firm’s workspace</a>
                <a href="{{ route('login') }}" class="pb-btn pb-btn--outline pb-btn--lg">Sign in</a>
            </div>
        </div>
    </div>
</section>
@endsection
