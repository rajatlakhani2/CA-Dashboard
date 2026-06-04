# RLA Dashboard — Full UI specification (for AI review & suggestions)

**Product:** Multi-tenant SaaS CA firm workspace (Laravel + Blade + Alpine.js + Tailwind).  
**Production URL:** https://app.kuhu.org.in  
**Brand in UI:** “RLA DASHBOARD” sidebar title; workspace name on dashboard is dynamic (firm name from settings).

**Purpose of this document:** Give another AI (or designer) a complete map of every screen, tab, and role variation so they can suggest UX improvements without reading the codebase.

---

## 1. Global shell (all authenticated pages)

### 1.1 Layout
- **Left sidebar** (dark indigo/slate): fixed on desktop; slide-over on mobile with overlay.
- **Top header:** Page title (from `@section('header')`), sticky white bar.
- **Main content:** Scrollable area to the right of sidebar.
- **Mobile bottom nav** (subset of links on small screens).

### 1.2 Top header actions (role-dependent)
- **Quick Search** — opens command palette (`Ctrl+K`): clients, tasks, pages, actions.
- **Zen mode** — collapses chrome for focus.
- **PWA install** — when browser supports it.
- **Notifications** bell — dropdown of unread items; “Mark all read”.
- **User menu** — profile, settings, logout.

### 1.3 Themes
- User theme preference (`modern` default) applied as `theme-{name}` on `<body>`.

### 1.4 Login / registration (unauthenticated)
- **Login** (`/login`): **Workspace ID** (slug) + email + password. Multi-tenant: wrong workspace blocks login.
- **Register firm** (`/register`): firm name, workspace slug, admin name/email/password — creates new organization + partner user.
- Post-login redirect: **article** → My Day; others → Dashboard.

---

## 2. Roles & navigation visibility

| Role | Sidebar | Notes |
|------|---------|--------|
| **partner** | Full menu + Partner Overview + admin | Firm owner |
| **manager** | Like partner but branch-scoped staff/clients | |
| **associate** | Clients (own portfolio), tasks, invoices read-only, dashboard | No staff/payments/settings |
| **article** | My Day, My Tasks, Submit Client only | No client list |
| **staff/intern** | Module flags from `module_access` JSON | |

Modules gated by `module_access` per user (see `App\Support\ModuleAccess`).

---

## 3. Sidebar structure (partner / manager default)

### 3.1 Core
1. **Dashboard** — Command Centre
2. **Partner Overview** (partner only) — KPI / firm-wide snapshot

### 3.2 Client 360
3. **Clients** — list, CRUD, import, purge
4. **Passwords** — client credentials vault
5. **Doc review** — document ingestion queue (partner/manager)
6. **Smart Archive** — document browser per client

### 3.3 Work Management
7. **My Day** — focused daily task view
8. **Tasks** — full task board/list
9. **Staff Directory** — users CRUD (partner/manager)
10. **Workload Planner** — team capacity view
11. **Time Tracking** — time entries
12. **Leaves** — leave requests (partner/manager)

### 3.4 Compliance
13. **Reminders** — service dues calendar/list
14. **Personal Renewals** — partner/staff personal compliance dates
15. **DSC Tracker** — digital signature certificates
16. **TDS Management** — TDS records

### 3.5 Finance & Billing
17. **Billing Queue** — unbilled work → invoice
18. **Invoices** (associate label: **My Client Invoices**)
19. **Collections** — overdue follow-up (partner/manager)
20. **Payments** — receipts against invoices
21. **Expenses** — firm expenses
22. **Subscriptions** — recurring client subscriptions

### 3.6 Analytics (expandable submenu)
23. **Compliance 360°** — firm compliance dashboard
24. **Service Report**
25. **Income Wise** (financial report)
26. **Due Date Report**
27. **Staff Productivity** (partner/manager)
28. **Client Profitability** (partner/manager)

### 3.7 Administration
29. **The Pulse** — activity feed
30. **Recycle Bin** — soft-deleted clients restore
31. **Service Master** — service catalog & due rules
32. **System Health** (partner) — migrations, cache, env checks
33. **Settings** — firm profile, GST, reminders, WhatsApp (partner)
34. **Users** — under settings: directory, create user, module access tabs
35. **Branches** — branch master (partner)
36. **WhatsApp** — template / messaging hub (if enabled)

---

## 4. Dashboard (`/dashboard`) — full tab breakdown

**Header:** Firm/workspace name, “Multi-user workspace · N team members”, quick actions: **+ Client**, **+ Task**, **+ Invoice** (policy-gated).

**Banner (conditional):** Amber alert if article-submitted clients await partner approval → link to Clients.

### 4.1 SaaS workspace strip (`workspace-header` partial)
- Badges: `SaaS Workspace`, workspace slug (`ID: rla`), plan (e.g. Professional), “Seats almost full” if ≤3 seats left.
- Firm name (H1), welcome line with user name + role + today’s date.
- **Team** count used/limit; **Manage users** (if allowed).
- Horizontal **team cards** (up to 12): initials, name, role, open task count; current user highlighted.
- Footer: motivational quote; label `Dashboard SaaS v1`; mobile quick +Client / +Task.

### 4.2 KPI row (clickable cards)
| Card | Links to | Visible |
|------|----------|---------|
| Total Clients (+ new this month) | clients.index | all |
| My Tasks (open count) | tasks.index | all |
| Due This Month | service-dues.index | all |
| Outstanding fees | invoices.index | partner/manager |
| Unbilled work | billing.index | partner/manager |

### 4.3 Dashboard tabs (Alpine `activeTab`)

#### Tab A — **Overview** (default)
- **Left (2/3):** “Today’s priority queue” — up to 8 open tasks assigned to me; client name, title link, WhatsApp quick link if phone, due badge (overdue = red).
- **Right (1/3):**
  - “Upcoming overview” — next 4 service due alerts.
  - “Upcoming deadlines” — three pills: Next 7 days / 7–15 / 15–30 (counts, link to due-date report or reminders).
  - “High-risk clients” (if any) — rose list with View links.

#### Tab B — **Schedule**
- FullCalendar month grid (`cal-grid-minimal`): white day cells, colored **dots** per event (blue=tasks, violet=dues, emerald=done, rose=overdue).
- Filters bar: show tasks/dues, due status, service, assignee, branch, client category — refetches JSON events.
- Interactions: click dot for detail; drag reschedule; click day to add task (where enabled).

#### Tab C — **Workload**
- Three stat cards: Pending tasks (mine), Services due this month, Completion rate % with progress bar.
- Two columns: My task queue list; deadline breakdown pills (same 7/15/30 windows).

#### Tab D — **Financials** (partner/manager only)
- Cards: Outstanding fees, Overdue collections, Collected this month.
- Grid: Recently updated clients (avatar initials, name, relative time) → edit client.

**Welcome modal** may show on first visit (partial included at bottom).

---

## 5. Partner Overview (`/partner/dashboard`)

Separate page from main dashboard: firm-wide KPIs, charts, team metrics (partner-only). Use when reviewing entire practice, not personal queue.

---

## 6. Clients module

### 6.1 Clients index (`/clients`)
- **Pending approvals** (partner): yellow box — article submissions with Approve button.
- **PAN lookup hints** (amber): trashed / pending / hidden-by-scope messages with action links.
- **Toolbar:** Search (name, code, PAN), status filter, category filter.
- **Actions:** Add Client, Download Template, Export Excel, **Preview import** (file upload → preview page), Delete Selected (current page).
- **Danger zone (partner only):** Delete by `group_name` — must type `DELETE`; **no default value**; placeholders `portfolio-a` / `imported-portfolio` only. *(Production may show old copy until deploy + view:clear.)*
- **Table:** selectable rows, client code, name, PAN, manager, status, category, group_name, actions (view/edit/delete per policy).

### 6.2 Create / Edit client
- Sections: identity (name, PAN, GSTIN), classification (category A/B/C, status), **group_name** placeholder “Portfolio A, Corporate”, manager (partner), contacts, GST flag, office notes, services.
- Article create → pending approval, redirect to tasks.

### 6.3 Client detail (`/clients/{id}`) — tabs

**Header actions:** Client Ledger (partner/manager), Onboarding, DSCs, + New Invoice, Edit Profile, Portal link.

**Summary strip:** PAN, Manager, Outstanding (₹), Next due, Last invoice, Active tasks count.

**Partner extras:** AI assistant panel, OCR upload → document review queue, compliance risk banner.

| Tab | Contents |
|-----|----------|
| **Work** | Document checklists per service (mark received); active tasks; service dues; worksheets; linked compliance items |
| **Finance** | YTD billed/collected/outstanding; recent invoices (associate: read-only); billing hints |
| **Timeline** | Activity / status history |
| **Profile** | Full static profile, tags, notes, portal settings |

### 6.4 Import flows
- **Excel:** Upload on index → `import-preview` (create/update/invalid rows) → Confirm.
- **Folder import** (`/clients/import/folder`, partner): path scan → preview counts → confirm (server-side folder; dev/ops tool).

---

## 7. Tasks module

### 7.1 My Day (`/tasks/my-day`)
- Daily focus: due today / overdue / quick complete.

### 7.2 Tasks index (`/tasks`)
- Filters: status, assignee, client, priority.
- List/board style task rows with status badges.

### 7.3 Create task (`/tasks/create`)
- **UI v4:** single medium-width layout, one table for client / title / due / assignee / priority / billing fields / checklist (badge in UI: “Task UI v4 · table”).
- Command Centre styling: compact rows, not multi-step wizard.

### 7.4 Edit task
- Full edit form; articles limited to status updates only.

---

## 8. Compliance pages (summary)

| Page | Primary UI |
|------|------------|
| **Reminders** | Filterable due list; mark complete; link client/service |
| **Personal Renewals** | Table of personal expiry dates |
| **DSC Tracker** | DSC rows with expiry, client link |
| **TDS** | TDS return / payment tracking table |
| **Compliance 360°** | Dashboard of risk scores, overdue services |

---

## 9. Finance pages (summary)

| Page | Primary UI |
|------|------------|
| **Billing Queue** | Unbilled lines; select → create invoice; FOC/unbilled flags |
| **Invoices** | Tabs/filters: draft, sent, paid, overdue; create, PDF, email |
| **Collections** | Overdue invoice call list |
| **Payments** | Record payment; allocate to invoices |
| **Expenses** | Expense entry with category |
| **Subscriptions** | Recurring fee schedules |
| **Client Ledger** | Per-client running balance (from client header) |

**Associate invoice UX:** List/show/PDF for **own clients only**; no create/edit/delete; sidebar “My Client Invoices”.

---

## 10. Reports (Analytics submenu)

Each report: date range filters, export where implemented, tables/charts.

- Service Report — uptake/completion by service type  
- Income Wise — revenue breakdown  
- Due Date Report — compliance calendar export mindset  
- Staff Productivity — tasks closed per staff  
- Client Profitability — revenue vs effort proxy  

---

## 11. Administration pages

| Page | Primary UI |
|------|------------|
| **The Pulse** | Chronological firm activity |
| **Recycle Bin** | Deleted clients; restore |
| **Service Master** | Services, default due days, document requirements |
| **System Health** | Artisan shortcuts, disk, queue status |
| **Settings** | Company, GST, invoice numbering, reminders |
| **Users** (settings) | Tabs: Directory, Create user, Module access matrix |
| **Branches** | Branch list add/remove |
| **Staff Directory** | Team cards, roles, branch assignment |
| **Workload Planner** | Who has how many open tasks |
| **Time Tracking** | Log hours per client/task |
| **Leaves** | Approve/reject leave |
| **Doc review** | Ingestion queue approve/reject |
| **Smart Archive** | Client picker → folder tree of documents |
| **Passwords** | Encrypted credential entries per client |
| **WhatsApp** | Integration settings / logs |

---

## 12. Command palette & search

- **Ctrl+K:** fuzzy search clients, tasks, navigate to modules.
- Global search endpoint for header (where enabled).

---

## 13. Visual design language (current)

- **Dashboard:** Light theme override on dashboard page — white cards, soft borders, indigo accent tabs.
- **Sidebar:** Dark gradient active item, section labels in small caps.
- **KPI cards:** Top colored border (blue/amber/rose/emerald/violet).
- **Calendar:** Minimal grid, dot indicators (not full event bars).
- **Forms:** Tailwind `ring-line`, primary indigo buttons.
- **Status badges:** Shared partial for Active/On-Hold/invoice states.

---

## 14. SaaS / multi-tenant notes for reviewers

- Data isolated by `organization_id`; login requires workspace slug.
- New firms via `/register`; default demo workspace slug `rla` for legacy firm.
- Seeded roles: **Rajat Lakhani** (partner), **Firm Associate** (associate), **Article Clerk** (article) — no person-specific branding in code target state.
- Seat limit shown on workspace header.

---

## 15. UX roadmap (post-review)

A structured implementation plan from product review (mission control, health scores, revenue center, onboarding, etc.) lives in **[UX_ROADMAP_2026.md](./UX_ROADMAP_2026.md)**. Use that doc for prioritization; this file remains the **as-built** inventory.

---

## 16. Known production vs codebase gaps

1. **Clients purge box** on live site may still show “Nileshbhai” default until `resources/views/clients/index.blade.php` is deployed and `php artisan view:clear` run.
2. **Deploy path** is `~/public_html/app.kuhu.org.in`, not `~/app.kuhu.org.in`.
3. Updates via GitHub curl scripts, not always `git pull`.

---

## 17. Prompt template for another AI

Copy everything below into your review chat:

```
You are reviewing a CA practice management SaaS dashboard (RLA Dashboard).
Stack: Laravel Blade, Alpine.js, Tailwind, FullCalendar on dashboard Schedule tab.

Constraints:
- Multi-tenant login (workspace ID + email + password)
- Roles: partner, manager, associate, article, staff
- Associate: own clients only; invoices read-only
- Article: tasks + submit client only (no client list)

Please suggest improvements for:
1. Information hierarchy on Dashboard (Overview / Schedule / Workload / Financials tabs)
2. Sidebar grouping and naming for Indian CA firms
3. Clients list + import + danger-zone purge UX
4. Task create form (single-table v4 layout)
5. Mobile experience and bottom nav
6. Removing any legacy single-firm assumptions; strengthening SaaS onboarding

Reference the full screen inventory in sections 3–11 above.
Prioritize actionable UI copy, layout, and flow changes—not backend rewrites.
```

---

*Generated from codebase state: CA Dashboard master branch. Update this file when major UI ships.*
