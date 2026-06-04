# UX roadmap 2026 — RLA Dashboard

Source: external product review against [UI_FULL_SPEC_FOR_AI.md](./UI_FULL_SPEC_FOR_AI.md).  
**Verdict:** Feature depth is strong (9+/10 for CA coverage). Next investment is **hierarchy, mission-control dashboard, health scores, and SaaS activation** — not more modules.

---

## What to keep (already strong)

| Area | Status |
|------|--------|
| Full CA practice stack (CRM → compliance → billing → docs) | Ship |
| Multi-tenant workspace login + org isolation | Ship |
| Roles: partner / manager / associate / article | Ship |
| Dashboard tab model: Overview / Schedule / Workload / Financials | Correct structure |
| Task UI v4, calendar dots, billing queue | Keep iterating |

---

## Priority matrix

| Priority | Theme | Impact | Effort |
|----------|--------|--------|--------|
| **P0** | Mission-control first screen (partner) | Very high | Medium |
| **P0** | Remove legacy / overload (purge copy, sidebar density) | High | Low |
| **P1** | Client health score on client card + detail | Very high | Medium |
| **P1** | Firm pulse widget on dashboard (not buried in The Pulse) | High | Low–medium |
| **P1** | Revenue command center (Financials tab) | High | Medium |
| **P2** | Clients table ↔ card view toggle | High | Medium |
| **P2** | WhatsApp quick actions on client surfaces | High (India) | Medium |
| **P2** | Notifications grouped by category | Medium | Medium |
| **P2** | Global search 2.0 (unified entity types) | High | High |
| **P3** | SaaS onboarding wizard post-register | Activation | High |
| **P3** | AI assistant first-class (natural language ops) | Differentiation | High |
| **P3** | Sidebar regroup (Command Centre → Insights → Admin) | Medium | Medium |
| **P3** | Mobile FAB + 5-tab bottom nav | Medium | Medium |

---

## P0 — Mission Control dashboard (partner login)

**Goal:** Answer in **≤10 seconds** without scrolling:

- Today: tasks due, compliance due, collections pending, risk count  
- Firm: clients, unbilled work, outstanding, cash (today + MTD)  
- Team: overloaded vs idle (workload snapshot)  
- Alerts: GST/ITR overdue, DSC expiring, non-billed clients  

**UI pattern:**

```
Good morning, {name} · {workspace}

[TODAY strip — 6–8 compact metrics, single row scroll on mobile]

[RISK ALERTS — max 5, link to filtered lists]

[AI INSIGHTS — 3–5 bullets, optional Phase 3]

[ACTIVITY FEED — last 10 firm events, Phase 1 lite]

[Tabs: Overview | Schedule | Workload | Financials — unchanged]
```

**Backend:** Mostly aggregate existing models (`Task`, `ServiceDue`, `Invoice`, `Payment`, `Dsc`, `Client`) — new `DashboardMissionControlService` or extend `DashboardController`.

**Associate/article:** Smaller strip (my tasks, my dues, my clients only).

---

## P1 — Client health score

**Per client (0–100) with sub-scores:**

| Signal | Weight (example) |
|--------|------------------|
| Compliance (overdue dues) | 40% |
| Payments (outstanding / overdue invoices) | 30% |
| Documents (checklist incomplete) | 20% |
| Open tasks age | 10% |

**Surfaces:**

- Client detail header (badge + breakdown row)  
- Clients index card view (Phase 2)  
- Dashboard “clients needing attention” list  

**Implementation:** `ClientHealthScoreService` + cached column or computed on read; nightly refresh optional.

---

## P1 — Firm pulse on dashboard

Move **summary** of “The Pulse” to dashboard (keep full page for history).

Example widget:

- Today: tasks completed, clients added, ₹ collected, filings completed  
- Data: `activity_log` / existing pulse queries  

---

## P1 — Revenue command center (Financials tab)

Replace flat cards with:

- MTD target vs achieved (progress bar) — target from settings or manual  
- Collection efficiency %  
- Outstanding total  
- Optional: sparkline last 6 months  

Reuse billing/payment aggregates already on dashboard.

---

## P2 — Modern client CRM

- **Table view** (current) — power users, export, bulk  
- **Card view** — name, manager, outstanding, health score, open tasks, [View]  
- Toggle persisted in user preference / localStorage  

---

## P2 — WhatsApp-first quick actions

On client row/card/detail:

- Send reminder (due)  
- Send invoice (link/PDF)  
- Payment follow-up (template)  
- Document request  

Reuse existing WhatsApp routes/templates where present; uniform “⋯” menu.

---

## P2 — Notifications 2.0

Bell dropdown → **grouped sections:**

- Compliance (overdue count)  
- Billing (pending invoices)  
- DSC (expiring)  
- Tasks (overdue)  

Link each group to pre-filtered index pages.

---

## P2 — Global search 2.0

Extend `CommandPaletteBuilder`:

- Single query → sections: Clients, Tasks, Invoices, Payments, Documents, Passwords, Services  
- Fuzzy match on name/code/PAN/invoice #  

---

## P3 — SaaS onboarding wizard

Post `/register` redirect to `/onboarding/workspace`:

| Step | Action |
|------|--------|
| 1 | Confirm firm profile |
| 2 | Import clients (template / skip) |
| 3 | Invite team |
| 4 | Create first task |
| 5 | Create first invoice |

Progress: **Workspace setup · X% complete** in header until done.

Store `onboarding_completed_at` on `organizations`.

---

## P3 — AI assistant (first-class)

Dock or slide-over from dashboard + client detail:

- Canned + NL queries backed by scoped DB reads  
- Examples: overdue GST clients, unbilled 90d, workload by staff, draft engagement letter  

Build on existing `ClientAiController` patterns; add firm-level `FirmAiController`.

---

## P3 — Sidebar regroup (target IA)

```
Command Centre
  Dashboard, Partner Overview
Clients
  Clients, Smart Archive, Password Vault, Doc Review
Work
  My Day, Tasks, Workload, Time, Leaves
Compliance
  Reminders, DSC, TDS, Renewals
Billing
  Queue, Invoices, Payments, Collections, Expenses, Subscriptions
Insights
  Compliance 360°, Productivity, Profitability, Revenue reports
Administration
  Staff, Branches, Settings, Services, WhatsApp, System Health
```

Rename labels only in `layouts/app.blade.php` first; route names unchanged.

---

## P3 — Mobile

Bottom nav:

- Home · Clients · Tasks · Calendar · Quick Add (FAB sheet: client / task / invoice / payment)

Calendar → dashboard Schedule tab deep link.

---

## Suggested build order (sprints)

### Sprint 1 (1–2 weeks)
- Mission-control KPI strip + risk alert row (partner dashboard)  
- Firm pulse widget (dashboard)  
- Financials tab progress bars  
- Deploy clients purge copy fix (no legacy names)  

### Sprint 2 (2–3 weeks)
- Client health score (service + client show + “at risk” list)  
- Clients card/table toggle  
- WhatsApp quick-action menu on client show  

### Sprint 3 (3–4 weeks)
- Grouped notifications  
- Search 2.0  
- Sidebar IA rename/regroup  

### Sprint 4 (4+ weeks)
- Onboarding wizard  
- AI command panel  
- Mobile FAB + nav  

---

## Review scores (baseline → target)

| Area | Now | Target |
|------|-----|--------|
| Features | 9.5 | 9.5 |
| Multi-tenant SaaS | 9 | 9.5 |
| CA workflow | 10 | 10 |
| Mobile UX | 7 | 8.5 |
| Information hierarchy | 7.5 | 9 |
| Modern SaaS feel | 7 | 8.5 |
| AI readiness | 8 | 9 |
| Market readiness | 9 | 9.5 |

---

## Prompt for implementation AI

```
Implement Sprint 1 of docs/UX_ROADMAP_2026.md only.
- Extend DashboardController + dashboard.blade.php
- Add DashboardMissionControlService with partner/associate variants
- Do not rename routes or break tenant isolation
- Match existing Tailwind/indigo dashboard styling
- Add feature tests for mission control aggregates
```

---

*Last updated: 2026-06-04*
