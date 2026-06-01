# Feature Suggestions

**Last reconciled:** 2026-05-29 (aligned with repo + `TEST_AND_HANDOFF_PLAN.md`).

| Status | Meaning |
| --- | --- |
| Done | Shipped and covered by tests or handoff QA |
| Partial | Started; more UX or coverage needed |
| Open | Not built or only sketched |

---

## Done (security & platform)

### 1. Authorization and Permission Matrix — **Done**

- Route `role` middleware, module policies, branch scoping, `Client::visibleTo()`, staff task scoping.
- Tests: `SecurityAccessTest`, `PolicyAccessTest`, `FinanceCompliancePolicyTest`, `ClientVisibilityTest`, `FirmRolesTest`, `FirmLiveQATest`.

### 2. Remove Or Restrict Installer Route — **Done**

- Public `/install-db` removed. System actions partner-only via `RestrictDangerousSystemActions`.

### 3. Credential Vault Hardening — **Done**

- Partner/manager access, encrypted storage, reveal/copy/create/update/delete audit.
- Categories (GST, IT, MCA, TAN, Bank, PF, ESIC, Other), filter on vault index, last accessed user/time updated on reveal/copy.

### 4. Unified Status Enums — **Done**

- Model constants on tasks, dues, invoices, subscriptions, DSC, clients, etc.

### 5. Feature-Level Tests For New Modules — **Done**

- Billing, payments, subscriptions, DSC, TDS, reports, staff, tasks, backups, intelligence phases — see `tests/Feature/*`.

### 22. Add Policies — **Done**

- Policies for clients, credentials, invoices, payments, reports, expenses, subscriptions, DSC, TDS, tasks, staff, branches, settings, system.

---

## Done (product — also in intelligence roadmap)

### 6. Client Timeline — **Done**

- Timeline tab on client show (`ClientTimelineBuilder`); Phase 2.

### 9. Billing Automation Rules — **Done**

- Billing rules, `BillingRuleApplier`, draft invoice from queue (`BillingDraftInvoiceBuilder`).

### 10. Collection Follow-Up Center — **Done**

- `/collections`, aging, follow-ups, call list (`CollectionsCallListBuilder`); Phase 2.

### 12. Recurring Task Templates — **Done**

- Task templates + `TaskTemplateSpawner` / spawn from client show.

### 13. Partner Dashboard — **Done**

- `/partner-dashboard`, KPIs, alerts, at-risk compliance, staff workload snippet.

### 17. Global Command Palette — **Done**

- Ctrl+K: quick actions + navigation on open; search with `#` (actions) and `>` (clients); grouped results; `CommandPaletteBuilder` + `GET /search/palette`.

### 18. Empty States And Onboarding Hints — **Done**

- Shared `partials/empty-state` on billing, invoices, tasks, service dues, clients, payments, credentials, expenses.

### 24. Add Database Indexes — **Done**

- `2026_05_25_071741_add_performance_indexes_to_tables.php` (core tables).
- `2026_05_29_200000_add_remaining_performance_indexes.php` (service dues, credentials category, document checklist tables, time entries).

---

## Open (product)

### 7. Workload Planner — **Done (MVP)**

- `/workload` (partner/manager): kanban by assignee, overdue highlights, planned load (2h/task heuristic) vs logged hours (30d), drag-to-reassign / unassign.
- Open: calendar week grid, per-task estimated hours field, billable vs non-billable breakdown.

### 8. Compliance Calendar Filters — **Done**

Dashboard Schedule tab: filter by service, assignee/manager, branch (partner), category, status; toggle tasks/dues; live refresh via `GET /calendar/events`.

### 14. Staff Productivity Report — **Done**

`/reports/staff-productivity` — completions, on-time %, avg delay, hours (billable split), productivity score.

### 15. Client Profitability Report — **Done**

`/reports/client-profitability` — revenue, collected, outstanding, hours, realization %, ₹/hour, low-margin flags.

### 11. Document Checklist Per Service — **Done**

- Service Master: required documents per service (`service_document_requirements`).
- Client Work tab: mark received per opted service (`client_service_document_checks`).
- Compliance schedule: missing-doc badges link to client Work tab (`?tab=work#document-checklists`).

### 16. Split Sidebar By Groups — **Done**

Sidebar uses labeled sections (Dashboard, Client 360, Work, Compliance, Finance, Analytics collapsible, Administration). Analytics submenu includes productivity/profitability reports.

### 19. Import Preview And Validation — **Done**

- Excel: **Preview import** on clients list → review create/update/warnings/invalid → **Confirm** (stored file, no re-upload).
- Duplicate PAN in file blocked; GSTIN/client code warnings.
- Nilesh folder import preview remains separate (`clients/import/nilesh`).

### 20. Mobile Work Mode — **Done**

- My Day for staff/article/intern: Start/Done, inline notes, quick time log (0.5/1/2h), mobile bottom nav (My Day, Tasks, Time).
- Login redirect to My Day for field roles with tasks module.

---

## Open (technical)

### 21. Extract Form Requests — **Done**

- Clients, tasks, payments, credentials, calendar, user role, invoices (store/update), staff (store/allot).

### 23. Move Dashboard Queries Into Services — **Done**

`DashboardMetricsService` — summary tiles, compliance, alerts, tasks, approvals; controller keeps calendar builder + welcome modal.

### 25. Audit Sensitive Actions — **Done**

- Credential vault; invoice delete/send/**update**; payment delete/**create**; client bulk delete; user role change; module access changes; system backup (UI + `backup:run` CLI).
- Log channel: `sensitive_actions` (Spatie activity log). Tests: `SensitiveActionAuditTest`.

---

## Highest Priority (historical — see Done above)

<details>
<summary>Original descriptions (archive)</summary>

### 1. Authorization and Permission Matrix

Add clear role-based access across routes and actions.

- Partners: full access
- Managers: clients, tasks, staff workload, reports, billing review
- Staff: assigned/self-created tasks, own time entries, limited client visibility
- Interns: assigned/self-created tasks only

### 2. Remove Or Restrict Installer Route

Remove `/install-db` or protect it behind partner-only access and environment checks.

### 3. Credential Vault Hardening

- Partner/manager-only access
- Reveal/copy audit (done)
- Credential categories; last accessed fields (open)

### 4. Unified Status Enums

Replace repeated string statuses with shared constants or PHP enums.

### 5. Feature-Level Tests For New Modules

Add tests for billing queue, payments, subscriptions, DSC, TDS, reports, and role permissions.

</details>

---

## Suggested Implementation Order (updated)

**Backlog in this doc is complete** for the CA Dashboard MVP scope (e-invoice/IRN excluded).

**Optional / out of scope unless requested:**

- Workload calendar week grid, per-task estimated hours (#7 extras)
- Full offline PWA / service worker (basic manifest + mobile meta in layout only)
- Intelligence backlog: OCR API, AI WhatsApp intent, Razorpay webhooks — see `FUTURE_INTELLIGENCE_ROADMAP.md`
