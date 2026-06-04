# Dashboard Feature Test Register

Live QA register for **CA Dashboard** (`D:\New folder\Dashboard\CA Dashboard`).

**Test environment:** http://127.0.0.1:8000 (PHP built-in server + Vite)  
**Last run:** 2026-05-30 (go-live QA pass)  
**Roles:** `article`, `associate`, `partner` — see [FIRM_ROLES_AND_ACCESS.md](./FIRM_ROLES_AND_ACCESS.md)  
**Seeded logins:** `php artisan users:ensure-firm-logins` (password: `password`)

**Register status:** All rows below are `[x]`, `[!]`, or `[—]` — **no `[ ]` untested rows** as of 2026-05-29.

**Automated live browser QA:** `npm run test:browser` — **72 checks passed** `(browser 2026-05-30)` — workload, collections, productivity reports, palette API, article → My Day

**Automated HTTP QA:** `php artisan test` — **272 tests, 1008 assertions** `(HTTP 2026-05-30)` including `GoLiveReadinessTest`, `FirmLiveQATest`, `DeepPostFlowTest`, `IntelligencePhase*Test`

**Go-live report:** [GO_LIVE_QA_REPORT.md](./GO_LIVE_QA_REPORT.md)

**Legend:** `[x]` checked OK · `[!]` bug found & fixed · `[—]` blocked by role (expected 403/redirect) · `[ ]` not yet tested  
**Evidence tags:** `(browser 2026-05-25)` article UX pass · `(browser 2026-05-29)` Playwright catalog · `(HTTP 2026-05-29)` PHPUnit

**Pending (ops, not app defects):** Production smoke at `https://app.kuhu.org.in` when network allows; live Meta WhatsApp + inbound webhook env on production — see [TEST_AND_HANDOFF_PLAN.md](./TEST_AND_HANDOFF_PLAN.md).

---

## 1. Authentication

| # | Function | Route | Article | Associate | Partner | Notes |
|---|----------|-------|---------|-----------|---------|-------|
| 1.1 | Login page | `/login` | [x] | [x] | [x] | User dropdown, no password |
| 1.2 | Login submit | `POST /login` | [x] | [x] | [x] | Article → My Day; others → dashboard `(HTTP/browser 2026-05-30)` |
| 1.3 | Logout | `POST /logout` | [x] | [x] | [x] | Header **Sign out** button `(browser 2026-05-29)` |
| 1.4 | Root redirect | `/` | [x] | [x] | [x] | → login when guest |

---

## 2. Operations — Dashboard

| # | Function / Tab | Route / Action | Article | Associate | Partner | Notes |
|---|----------------|----------------|---------|-----------|---------|-------|
| 2.1 | Dashboard load | `/dashboard` | [—] | [x] | [x] | Article redirected to tasks |
| 2.2 | Tab: Overview | in-page | [—] | [x] | [x] | |
| 2.3 | Tab: Calendar | in-page | [—] | [x] | [x] | Resize / events |
| 2.4 | Calendar drag-update | `POST /calendar/update-date` | [—] | [x] | [x] | Own tasks/renewals & dues |
| 2.5 | Welcome modal dismiss | in-page | [!] | [x] | [x] | Fixed: backdrop click closes modal `(browser 2026-05-25)` |
| 2.6 | KPI: Outstanding → invoices | link | [!] | [x] | [x] | Hidden for article; associate sees My Client Invoices link |
| 2.7 | KPI: Unbilled → billing | link | [!] | [—] | [x] | Hidden article; associate 403 on billing |
| 2.8 | Global search | `/search/global` | [—] | [x] | [x] | Article blocked by middleware |
| 2.9 | Notifications mark read | `/notifications/mark-read/{id}` | [—] | [x] | [x] | |
| 2.10 | Settings profile | `/settings` | [—] | [x] | [x] | Article: tasks-only middleware |

---

## 3. Client 360 — Clients

| # | Function / Tab | Route | Article | Associate | Partner | Notes |
|---|----------------|-------|---------|-----------|---------|-------|
| 3.1 | Client list | `/clients` | [—] | [x] | [x] | Article → tasks redirect `(HTTP 2026-05-29)` |
| 3.2 | Search / filters | query params | [—] | [x] | [x] | Associate: own portfolio only |
| 3.3 | Client create | `/clients/create` | [x] | [x] | [x] | Article: pending approval flow |
| 3.4 | Client store | `POST /clients` | [x] | [x] | [x] | `(HTTP 2026-05-29)` FirmRolesTest |
| 3.5 | Client show | `/clients/{id}` | [—] | [x] | [x] | Article: no list access to show |
| 3.6 | Client edit | `/clients/{id}/edit` | [—] | [x] | [x] | |
| 3.7 | Tab: Basic Info | in-page | [—] | [x] | [x] | |
| 3.8 | Tab: Contact & Address | in-page | [—] | [x] | [x] | |
| 3.9 | Tab: Engagement & Billing | in-page | [—] | [x] | [x] | |
| 3.10 | Tab: Services | in-page | [—] | [x] | [x] | Service checkboxes |
| 3.11 | Tab: Personal Reminders | in-page | [—] | [x] | [x] | Add renewal modal |
| 3.12 | Tab: Passwords & Credentials | in-page | [—] | [x] | [x] | In-client vault; index 403 associate |
| 3.13 | Import / export / bulk delete | | [—] | [—] | [x] | Partner only for bulk; associate no import |
| 3.14 | Client show → invoices | cross-link | [!] | [x] | [x] | Article hidden; associate read-only `(HTTP 2026-05-29)` |
| 3.15 | Client show → billing queue | cross-link | [!] | [—] | [x] | Article hidden; associate 403 |
| 3.16 | Client show → ledger | cross-link | [!] | [—] | [x] | Article hidden; associate no ledger |
| 3.17 | Worksheet add/delete | `clients/{id}/worksheets` | [—] | [x] | [x] | |
| 3.18 | Onboarding | `/onboarding/{client}` | [—] | [x] | [x] | |
| 3.19 | Client approve (pending queue) | `POST clients/{id}/approve` | [—] | [—] | [x] | `(HTTP 2026-05-29)` FirmRolesTest |

**Inter-tab dependencies (Client Edit):**

```text
Basic → Contact → Engagement → Services (wizard Next buttons)
Personal Reminders ↔ Personal Renewals module (same data)
Credentials ↔ Service Due / Portal names (operational)
Services → Service Dues generation (artisan services:generate-dues)
```

---

## 4. Client 360 — Passwords (Credentials)

| # | Function | Route | Article | Associate | Partner | Notes |
|---|----------|-------|---------|-----------|---------|-------|
| 4.1 | Credential index | `/credentials` | [—] | [—] | [x] | 403 article/associate |
| 4.2 | Credential store | `POST /credentials` | [—] | [—] | [x] | Partner/manager from client tab |
| 4.3 | Credential delete | `DELETE /credentials/{id}` | [—] | [—] | [x] | |
| 4.4 | Reveal password audit | `POST .../audit` | [—] | [—] | [x] | |
| 4.5 | Copy username/password audit | `POST .../audit` | [—] | [—] | [x] | |

---

## 5. Client 360 — Smart Archive

| # | Function | Route | Article | Associate | Partner | Notes |
|---|----------|-------|---------|-----------|---------|-------|
| 5.1 | Document index | `/smart-documents` | [—] | [x] | [x] | Article middleware redirect |
| 5.2 | Client documents | `/smart-documents/{client}` | [—] | [x] | [x] | Policy: view client |

---

## 6. Work — Tasks

| # | Function | Route | Article | Associate | Partner | Notes |
|---|----------|-------|---------|-----------|---------|-------|
| 6.1 | Task list | `/tasks` | [x] | [x] | [x] | Article primary landing `(HTTP 2026-05-29)` |
| 6.2 | Kanban view | `/tasks` (view) | [x] | [x] | [x] | |
| 6.3 | Task create | `/tasks/create` | [—] | [x] | [x] | Article redirected |
| 6.4 | Task edit | `/tasks/{id}/edit` | [—] | [x] | [x] | Article: status only |
| 6.5 | Status update (AJAX) | `PATCH /tasks/{id}/status` | [x] | [x] | [x] | `(HTTP 2026-05-29)` |
| 6.6 | Mark FOC | `PATCH .../mark-foc` | [—] | [—] | [x] | Partner/manager only |

---

## 7. Work — Staff / Leaves / Time

| # | Function | Route | Article | Associate | Partner | Notes |
|---|----------|-------|---------|-----------|---------|-------|
| 7.1 | Staff directory | `/staff` | [—] | [—] | [x] | 403 associate `(HTTP 2026-05-29)` |
| 7.2 | Staff show / allot / reminder | `/staff/{id}` | [—] | [—] | [x] | |
| 7.3 | Leaves | `/leaves` | [—] | [—] | [x] | Sidebar under Work (partner/manager) |
| 7.4 | Time entries | `/time-entries` | [—] | [x] | [x] | Article redirected |

---

## 8. Compliance

| # | Function | Route | Article | Associate | Partner | Notes |
|---|----------|-------|---------|-----------|---------|-------|
| 8.1 | Service dues list | `/service-dues` | [—] | [x] | [x] | Article redirected |
| 8.2 | Mark complete | `POST .../complete` | [—] | [x] | [x] | |
| 8.3 | Generate dues | `POST .../generate` | [—] | [—] | [x] | Partner/manager middleware |
| 8.4 | WhatsApp due alert | `POST .../whatsapp` | [—] | [—] | [x] | `(HTTP 2026-05-29)` DeepPostFlowTest mock |
| 8.5 | Personal renewals | `/personal-renewals` | [—] | [x] | [x] | |
| 8.6 | Service master | `/services` | [—] | [—] | [x] | |
| 8.7 | DSC tracker | `/dscs` | [—] | [—] | [x] | |
| 8.8 | TDS | `/tds` | [—] | [—] | [x] | |
| 8.9 | Compliance 360 | `/compliance-360` | [—] | [—] | [x] | Not `/compliance` (404) |

---

## 9. Finance

| # | Function | Route | Article | Associate | Partner | Notes |
|---|----------|-------|---------|-----------|---------|-------|
| 9.1 | Invoices index | `/invoices` | [—] | [x] | [x] | Associate: read-only portfolio `(HTTP 2026-05-29)` |
| 9.2 | Invoice create/edit/show/PDF | | [—] | [—]/[x] | [x] | Associate: show/PDF [x]; create/edit [—] |
| 9.3 | Payments | `/payments` | [—] | [—] | [x] | Associate 403 `(HTTP 2026-05-29)` |
| 9.4 | Billing queue | `/billing` | [—] | [—] | [x] | `(HTTP 2026-05-29)` DeepPostFlowTest partner |
| 9.5 | Expenses | `/expenses` | [—] | [—] | [x] | |
| 9.6 | Subscriptions | `/subscriptions` | [—] | [—] | [x] | Not in sidebar |
| 9.7 | Ledger / SOA | `/ledger/{client}` | [—] | [—] | [x] | |

**Inter-module:** Service due / task / worksheet → Billing → Invoice → Payment → Ledger.

---

## 10. Reporting

| # | Function | Route | Article | Associate | Partner | Notes |
|---|----------|-------|---------|-----------|---------|-------|
| 10.1 | Reports hub | `/reports` | [—] | [—] | [x] | |
| 10.2 | Financial / income | `/reports/financial` | [—] | [—] | [x] | |
| 10.3 | Compliance / due date | `/reports/compliance` | [—] | [—] | [x] | |
| 10.4 | Service report | `/reports/service` | [—] | [—] | [x] | |
| 10.5 | Client / task reports | `/reports/client`, `/reports/task` | [—] | [—] | [x] | |
| 10.6 | CSV exports | `*.export` routes | [—] | [—] | [x] | |

---

## 11. Administration

| # | Function | Route | Article | Associate | Partner | Notes |
|---|----------|-------|---------|-----------|---------|-------|
| 11.1 | Activity pulse | `/activity` | [—] | [—] | [x] | |
| 11.2 | Recycle bin | `/recycle-bin` | [—] | [—] | [x] | |
| 11.3 | System health | `/system` | [—] | [—] | [x] | Partner only |
| 11.4 | Branches | `/branches` | [—] | [—] | [x] | Partner only |
| 11.5 | Users / roles | `/users` | [—] | [—] | [x] | Partner only |
| 11.6 | WhatsApp settings | `/notifications/whatsapp` | [—] | [—] | [x] | |

---

## 12. Inter-Module Dependency Matrix

| Source module | Target module | Dependency | Article test | Associate test | Partner test |
|---------------|---------------|------------|--------------|----------------|--------------|
| Client → Services | Service Dues | Opted services drive due generation | [—] | [x] | [x] |
| Service Due | Client | `client_service_id` → client | [—] | [x] | [x] |
| Task | Client | `client_id` optional | [x] | [x] | [x] |
| Task | Invoice | `invoice_id` when billed | [—] | [—] | [x] |
| Worksheet | Billing | Unbilled items queue | [—] | [—] | [x] |
| Billing | Invoice | `billing.process` | [—] | [—] | [x] |
| Invoice | Payment | Status sync on payment | [—] | [—] | [x] |
| Payment | Invoice | Branch policy via invoice | [—] | [—] | [x] |
| Client | Credentials | `client_id` | [—] | [—] | [x] |
| Client | Personal renewal | `client_id` | [—] | [x] | [x] |
| Client | Onboarding | Per-client checklist | [—] | [x] | [x] |
| Client | Ledger | SOA from invoices/payments | [—] | [—] | [x] |
| Dashboard calendar | Tasks / dues / renewals | `calendar.update-date` | [—] | [x] | [x] |
| Reports | Client / Invoice / Task | Branch-scoped queries | [—] | [—] | [x] |
| Article submit | Partner approve | `approval_status` pending → approved | [x] | [x] | [x] |

---

## 13. Bugs Found & Fixed (this pass)

| ID | Issue | Fix | Status |
|----|-------|-----|--------|
| B-01 | Article saw sidebar links → 403 pages | Role-based sidebar in `layouts/app.blade.php` | Fixed |
| B-02 | Client show finance links 403 for article | Hide/guard invoice, billing, ledger links | Fixed |
| B-03 | Dashboard finance KPIs 403 for article | Wrap Outstanding/Unbilled cards | Fixed |
| B-04 | Welcome modal blocked sidebar | Backdrop click closes modal | Fixed |
| B-05 | Pagination `&raquo;` label | Use `{!! $paginator->links() !!}` in list views | Fixed |
| B-06 | Article saw Financials tab + report 403 links on dashboard | Hide tab/KPIs; route article to service-dues | Fixed |
| B-07 | Article saw YTD billed/collected on client show | Hide finance summary row for article | Fixed |

---

## 14. Automated Tests Added

- `tests/Feature/DashboardNavigationTest.php` — article vs associate sidebar, finance KPI/tab hiding, client show finance, pagination
- `tests/Feature/FirmRolesTest.php` — partner, associate portfolio, article approval flow
- `tests/Feature/FirmLiveQATest.php` — HTTP verification aligned to this register (2026-05-29)
- `tests/Feature/DeepPostFlowTest.php` — billing process, invoice WhatsApp, service-due WhatsApp (mocked)
- Existing: `ClientVisibilityTest`, `CredentialVaultAuditTest`, `NewerModuleFlowTest`, policy tests, `SystemBackupTest`

Run full suite: `php artisan test` — **156 passed** (2026-05-29).  
Run live browser: `npm run test:browser` (requires `php artisan serve` + firm logins).

---

## 15. Questions for Product Owner

1. **Test roles:** Resolved — use seeded `partner`, `associate`, `article` via `users:ensure-firm-logins`.
2. **Article billing visibility:** Resolved — remain hidden on client show; associate sees read-only YTD/invoices.
3. **WhatsApp:** Mock in tests (`DeepPostFlowTest`); production needs real Meta credentials in `.env`.
4. **Production URL:** `.env` may use `https://app.kuhu.org.in` — smoke result recorded in `TEST_AND_HANDOFF_PLAN.md` § Production smoke.
5. **Leaves / Subscriptions / Billing:** Billing/subscriptions partner/manager only; leaves in Work Management sidebar when staff module enabled.

---

## 16. Next QA Steps

1. ~~Add partner/associate/article users~~ — Done: `php artisan users:ensure-firm-logins`.
2. ~~Complete POST/action testing~~ — Covered by `DeepPostFlowTest`, `NewerModuleFlowTest`, `FirmRolesTest` (HTTP). Optional: manual browser re-check on production.
3. ~~Run `php artisan test`~~ — Run before each deploy; counts synced in `TEST_AND_HANDOFF_PLAN.md`.
4. Optional: full browser pass on production URL after deploy.
