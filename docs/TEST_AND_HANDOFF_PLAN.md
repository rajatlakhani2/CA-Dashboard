# Test And Handoff Plan

## Purpose

This file records the verification work being done after the architecture and feature review. It is meant for another developer or operator to understand:

- What was tested
- Which commands were run
- What passed or failed
- Which fixes or follow-up actions are recommended next

## Current Context

The project is a Laravel 12 CA Dashboard with Blade/Tailwind UI. A structure review was completed and the following planning files were added:

- `docs/ARCHITECTURE.md`
- `docs/FEATURE_STRUCTURE.md`
- `docs/FEATURE_SUGGESTIONS.md`

The repository already had many unstaged changes before this test pass began, including controller, model, route, view, migration, and tooling changes. Avoid reverting unrelated work.

## Verification Scope

Planned checks:

1. Run Laravel route discovery.
2. Run the Laravel test suite.
3. Run the frontend production build.
4. Check migrations and database health.
5. Record failures, risks, and recommended next fixes.

## Commands And Results

### Route Discovery

Command:

```powershell
& '.tools\php\php.exe' -c '.tools\php\php.ini' artisan route:list
```

Result:

- Status: Passed.
- Initial review discovered 155 routes.
- After the security route refactor, Laravel discovered 154 routes because the public `/install-db` route was removed.
- After trimming unused branch resource routes, Laravel discovered 150 routes.
- After removing the unused task show route, Laravel discovered 149 routes.
- After trimming unused subscription resource routes and fixing TDS route model binding, Laravel discovered 146 routes.

### Laravel Tests

Command:

```powershell
& '.tools\php\php.exe' -c '.tools\php\php.ini' artisan test
```

Result:

- Initial status: Failed.
- Root cause: `2026_05_19_131930_add_code_to_branches_table.php` tried to add `branches.code`, but `2026_03_16_100200_create_feature_tables.php` already creates that column on a fresh database.
- Fix applied: The later migration now checks that the `branches` table exists and that the `code` column does not already exist before adding it.
- Final status: Passed.
- First final result: 35 tests passed, 97 assertions.
- After the security route refactor and added regression tests: 40 tests passed, 126 assertions.
- After the policy layer and branch-scope tests: 45 tests passed, 137 assertions.
- After branch-scoped listing/export/import tightening: 48 tests passed, 146 assertions.
- After operations policies and firm-settings separation: 53 tests passed, 168 assertions.
- After task policy and staff task scoping: 59 tests passed, 187 assertions.
- After staff policy and branch-scoped staff actions: 64 tests passed, 209 assertions.
- After finance/compliance/report policy coverage: 69 tests passed, 243 assertions.
- After newer module flow coverage: 77 tests passed, 328 assertions.
- After status constants normalization: 77 tests passed, 328 assertions.

### Frontend Build

Command:

```powershell
cmd /c npm run build
```

Result:

- Status: Passed.
- Vite built the production assets successfully.
- PWA service worker files were generated in `public/build`.

### Migration Check

Command:

```powershell
& '.tools\php\php.exe' -c '.tools\php\php.ini' artisan migrate:status
```

Result:

- Initial status: One pending migration:
  `2026_05_19_131930_add_code_to_branches_table`
- Applied command:

```powershell
& '.tools\php\php.exe' -c '.tools\php\php.ini' artisan migrate --force
```

- Final status: Passed.
- All migrations are now marked as run through batch 24.

## Code Changes Made During Verification

### Migration Guard

File:

```text
database/migrations/2026_05_19_131930_add_code_to_branches_table.php
```

Change:

- Added a guard around the `branches.code` column creation.
- The migration now runs safely on:
  - Fresh test databases where `branches.code` already comes from the feature-table migration.
  - Existing local databases where the column already exists but this migration was still pending.
- Left `down()` as a no-op to avoid dropping a column that may have been created by the earlier branch-table migration.

### User Theme Migration

File:

```text
database/migrations/2026_05_20_000001_add_theme_to_users_table.php
```

Change:

- Added a guarded `users.theme` migration because the `User` model and `SettingsController` already read/write the field.
- The migration is safe on existing databases that already have the column and creates it on fresh test databases.

### Security Route Refactor

Files:

```text
routes/web.php
routes/modules/operations.php
routes/modules/clients.php
routes/modules/work.php
routes/modules/compliance.php
routes/modules/finance.php
routes/modules/reports.php
tests/Feature/SecurityAccessTest.php
tests/Feature/InvoiceFeatureTest.php
tests/Feature/ServiceDueTest.php
```

Changes:

- Removed the public `/install-db` route.
- Added route-level `role` middleware to sensitive modules:
  - Partner-only: system health/deployment, users/role management, branch master.
  - Partner or manager: credentials, billing, reports, invoices, services, WhatsApp settings/sends, staff management, compliance 360, activity log, recycle bin, payment/expense/DSC/TDS/subscription/ledger operations, bulk client delete, service due generation, onboarding mutations, leave approvals, and mark-FOC.
- Kept day-to-day working routes open to authenticated users where staff access is likely needed, such as dashboard, clients, tasks, service due list/completion, personal renewals, leaves, time entries, smart documents, settings profile, search, and notification read actions.
- Added `SecurityAccessTest` to confirm:
  - `/install-db` returns 404.
  - Staff users are blocked from sensitive modules.
  - Managers can access manager-level modules.
  - Managers are blocked from partner-only modules.
  - Partners can access partner-only modules.
- Updated invoice and compliance feature tests to authenticate as a manager where the newly protected routes are intentionally manager-level.

### Route Module Split

Files:

```text
routes/web.php
routes/modules/operations.php
routes/modules/clients.php
routes/modules/work.php
routes/modules/compliance.php
routes/modules/finance.php
routes/modules/reports.php
```

Change:

- Replaced the large authenticated route block in `routes/web.php` with module includes.
- Grouped routes by business area:
  - `operations.php`: dashboard, calendar update, recycle bin, WhatsApp settings/test sends, activity, settings, search, system, notifications, branches, users.
  - `clients.php`: clients, worksheets, smart documents, onboarding, credentials.
  - `work.php`: tasks, staff, leaves, time entries.
  - `compliance.php`: service dues, personal renewals, services, compliance 360, DSC, TDS.
  - `finance.php`: invoices, billing, payments, expenses, ledger, subscriptions.
  - `reports.php`: all report routes.
- Route discovery stayed at 154 routes after the split.

### Policy Layer

Files:

```text
app/Http/Controllers/Controller.php
app/Providers/AppServiceProvider.php
app/Policies/ClientPolicy.php
app/Policies/ClientCredentialPolicy.php
app/Policies/InvoicePolicy.php
app/Policies/PaymentPolicy.php
app/Exports/ClientsExport.php
app/Imports/ClientsImport.php
app/Http/Controllers/ClientController.php
app/Http/Controllers/ClientCredentialController.php
app/Http/Controllers/InvoiceController.php
app/Http/Controllers/PaymentController.php
tests/Feature/PolicyAccessTest.php
tests/Feature/ClientModuleTest.php
```

Change:

- Enabled `AuthorizesRequests` on the base controller.
- Registered explicit policies in `AppServiceProvider`.
- Added policies for:
  - `Client`: import/export/delete/bulk delete.
  - `ClientCredential`: manager/partner access with branch-aware client checks.
  - `Invoice`: manager/partner access with branch-aware record checks.
  - `Payment`: manager/partner access inherited from the linked invoice branch.
- Added controller-level `authorize()` calls so route middleware is backed by action-level authorization.
- Scoped manager-facing invoice, payment, and credential list queries by branch.
- Scoped manager client exports by branch.
- Assigned imported clients to the importing manager's branch.
- Added `PolicyAccessTest` coverage for:
  - Managers can access invoices in their own branch.
  - Managers cannot access invoices from another branch.
  - Partners can access invoices across branches.
  - Payment access follows invoice branch.
  - Credential access follows client branch.
  - Invoice index only shows a manager's branch records.
  - Payment index only shows a manager's branch records.
  - Credential index only shows a manager's branch records.
  - Staff users cannot use manager-level model policies.
- Updated client module tests to authenticate as a manager where import/export policy checks now apply.

### Operations Policy Layer

Files:

```text
app/Policies/BranchPolicy.php
app/Policies/SettingPolicy.php
app/Providers/AppServiceProvider.php
app/Http/Controllers/BranchController.php
app/Http/Controllers/SettingsController.php
resources/views/settings/profile.blade.php
routes/modules/operations.php
tests/Feature/OperationsPolicyTest.php
```

Change:

- Added `BranchPolicy` and `SettingPolicy`.
- Registered both policies in `AppServiceProvider`.
- Added controller-level authorization in `BranchController` and `SettingsController`.
- Split settings updates into:
  - Profile settings: any authenticated user can update own name/email/mobile/theme/password.
  - Firm settings: only partners can update company details, GST defaults, and reminder times.
- Hid firm/company settings sections from non-partner users in the settings page.
- Trimmed branch resource routes to only implemented actions: `index`, `store`, and `destroy`.
- Added operations policy tests for:
  - Managers can update profile but cannot alter firm settings.
  - Partners can update firm settings.
  - Firm settings are hidden from managers.
  - Branch policy is partner-only.
  - Unused branch page routes are not usable as pages.

### Task Policy Layer

Files:

```text
app/Policies/TaskPolicy.php
app/Providers/AppServiceProvider.php
app/Http/Controllers/TaskController.php
routes/modules/work.php
resources/views/tasks/edit.blade.php
tests/Feature/TaskFeatureTest.php
tests/Feature/TaskPolicyTest.php
```

Change:

- Added `TaskPolicy` and registered it in `AppServiceProvider`.
- Added controller-level authorization to task list, create, store, edit, update, status update, delete, and mark-FOC actions.
- Scoped task indexes so partners/managers see team-wide tasks, while staff/intern users only see tasks assigned to them or created by them.
- Scoped assignee dropdowns so staff/intern users can only assign tasks to themselves or leave them unassigned.
- Removed trust in posted `created_by`; task ownership is now set from the authenticated user.
- Kept task deletion and mark-FOC manager/partner-only.
- Removed the unused `tasks.show` route from the resource route because no controller action or view exists for it.
- Hid the delete button on the task edit page unless the current user can delete the task.
- Added task policy regression tests for:
  - Staff list visibility.
  - Staff blocked from editing another staff member's task.
  - Staff can update own task status.
  - Staff blocked from updating/deleting another staff member's task.
  - Staff blocked from assigning tasks to other users and marking FOC.
  - Managers can see, assign, and mark team tasks FOC.

### Staff Policy Layer

Files:

```text
app/Policies/StaffPolicy.php
app/Providers/AppServiceProvider.php
app/Http/Controllers/StaffController.php
resources/views/staff/index.blade.php
tests/Feature/StaffPolicyTest.php
```

Change:

- Added `StaffPolicy` and registered it for the `User` model in `AppServiceProvider`.
- Added controller-level authorization to staff index, create, show, allot-work, and reminder actions.
- Scoped staff directory lists:
  - Partners see all staff/users.
  - Managers see non-partner users in their own branch.
  - Managers without a branch only see themselves.
- Scoped branch dropdowns for staff creation so managers only see their own branch.
- Restricted manager staff creation to staff/intern users in the manager's own branch.
- Scoped unassigned task allotment:
  - Partners can allot any unassigned task.
  - Managers can allot unassigned internal tasks or tasks linked to clients in their own branch.
- Added a guard so single-task reminders can only be sent for tasks assigned to the selected staff member.
- Hid manager/partner role options from the staff registration modal for non-partner users.
- Added staff policy regression tests for:
  - Manager staff index branch scoping.
  - Manager denial for cross-branch staff view/allot/reminder.
  - Manager staff creation limits.
  - Manager branch-scoped task allotment.
  - Partner cross-branch staff management.

### Billing Queue Fix

Files:

```text
app/Http/Controllers/BillingController.php
resources/views/billing/index.blade.php
```

Change:

- Fixed the billing queue to query `ClientService` records through `Client::services()` instead of asking `Service` for a missing `dues()` relation.
- Updated the billing view to loop through `client.services` as client-service records.
- The manager-level security smoke test now opens `/billing` successfully, which covers this bug.

### Finance, Compliance, And Report Policy Layer

Files:

```text
app/Http/Controllers/DscController.php
app/Http/Controllers/ExpenseController.php
app/Http/Controllers/ReportController.php
app/Http/Controllers/SubscriptionController.php
app/Http/Controllers/TdsController.php
app/Policies/DscPolicy.php
app/Policies/ExpensePolicy.php
app/Policies/ReportPolicy.php
app/Policies/SubscriptionPolicy.php
app/Policies/TdsEntryPolicy.php
app/Providers/AppServiceProvider.php
resources/views/reports/index.blade.php
resources/views/reports/partials/filters.blade.php
routes/modules/compliance.php
routes/modules/finance.php
tests/Feature/FinanceCompliancePolicyTest.php
```

Change:

- Added explicit policies for expenses, subscriptions, DSCs, TDS entries, and reports.
- Registered the new policies and report gates in `AppServiceProvider`.
- Added controller-level authorization to the newer finance/compliance/report modules.
- Scoped manager list screens and summaries by branch:
  - Expenses through the recording user's branch.
  - Subscriptions and DSCs through client branch.
  - TDS through invoice/client branch.
  - Reports through invoice, client, task, and service-due branch relationships.
- Scoped report client filters so managers do not see clients from other branches.
- Fixed the financial report's monthly grouping expression for SQLite test compatibility while preserving MySQL behavior.
- Fixed TDS resource route binding so `{tdsEntry}` binds to `TdsEntry $tdsEntry`.
- Trimmed subscription resource routes to implemented controller actions.
- Added finance/compliance/report policy regression tests for:
  - Staff denial at policy/gate level.
  - Manager branch limits for expenses, subscriptions, DSCs, TDS, and reports.
  - Partner cross-branch access.

### Newer Module Flow Coverage

Files:

```text
tests/Feature/NewerModuleFlowTest.php
```

Change:

- Added HTTP flow regression tests for newer modules and sensitive actions:
  - Credential create/delete plus cross-branch store/delete denial.
  - Payment create/delete, overpayment rejection, invoice status syncing, and cross-branch store/delete denial.
  - Expense create/update/delete plus cross-branch update denial.
  - DSC create/update/delete plus cross-branch create/reassignment denial.
  - TDS create/update/delete plus cross-branch create/delete denial.
  - Subscription create/toggle/delete plus cross-branch create/toggle denial.
  - Financial and compliance CSV exports scoped to the manager's branch.
  - Partner-only system actions call the intended Artisan commands, while managers are blocked.

### Status Constants Normalization

Files:

```text
app/Models/Client.php
app/Models/ClientService.php
app/Models/Dsc.php
app/Models/Invoice.php
app/Models/PersonalRenewal.php
app/Models/ServiceDue.php
app/Models/Subscription.php
app/Models/Task.php
app/Http/Controllers/BillingController.php
app/Http/Controllers/ClientController.php
app/Http/Controllers/ComplianceController.php
app/Http/Controllers/DashboardController.php
app/Http/Controllers/DscController.php
app/Http/Controllers/InvoiceController.php
app/Http/Controllers/LedgerController.php
app/Http/Controllers/PaymentController.php
app/Http/Controllers/PersonalRenewalController.php
app/Http/Controllers/ReportController.php
app/Http/Controllers/ServiceDueController.php
app/Http/Controllers/StaffController.php
app/Http/Controllers/SubscriptionController.php
app/Http/Controllers/TaskController.php
app/Http/Controllers/WhatsAppController.php
app/Console/Commands/AssignNileshServices.php
app/Console/Commands/ImportClientsNilesh.php
app/Console/Commands/ProcessSubscriptions.php
app/Console/Commands/SendDailyNotifications.php
app/Console/Commands/SendDailyTaskReminders.php
app/Console/Commands/SendRenewalReminders.php
app/Console/Commands/SendUpcomingDuesNotifications.php
app/Imports/ClientsImport.php
app/Services/ServiceDueGenerator.php
tests/Feature/FinanceCompliancePolicyTest.php
tests/Feature/NewerModuleFlowTest.php
tests/Feature/PolicyAccessTest.php
```

Change:

- Added status constants to the main status-bearing models without changing stored database values.
- Added grouped constants for common logic, including:
  - `Task::STATUSES` and `Task::TERMINAL_STATUSES`.
  - `Invoice::OPEN_STATUSES` and `Invoice::PAYABLE_STATUSES`.
  - `ServiceDue` billing status constants.
  - `Subscription` status and frequency constants.
- Replaced behavior-driving status string comparisons in controllers, commands, imports, and `ServiceDueGenerator`.
- Updated newer regression tests to assert against model constants.
- Left database migrations and existing persisted values unchanged.

### Credential Vault Audit Layer

Files:

```text
app/Models/ClientCredential.php
app/Http/Controllers/ClientCredentialController.php
routes/modules/clients.php
resources/views/credentials/index.blade.php
resources/views/credentials/partials/vault-audit-script.blade.php
resources/views/clients/edit.blade.php
resources/views/activity/index.blade.php
resources/views/layouts/app.blade.php
tests/Feature/CredentialVaultAuditTest.php
```

Change:

- Added Spatie activity logging on `ClientCredential` for create, update, and delete with `credential_vault` log name.
- Logged only non-secret fields (`client_id`, `portal_name`, `username`, `notes`); passwords are never written to activity properties.
- Added `POST /credentials/{credential}/audit` for reveal/copy actions:
  - `revealed_password`
  - `copied_password`
  - `copied_username`
- Wired audit calls from credential list and client edit credential tabs when users reveal or copy values.
- Added CSRF meta tag to the app layout for JSON audit requests.
- Improved activity feed rendering for credential vault events.
- Added `CredentialVaultAuditTest` for create/delete logging, reveal/copy auditing, branch denial, and invalid action rejection.

### Client Visibility Layer

Files:

```text
app/Models/Client.php
app/Policies/ClientPolicy.php
app/Http/Controllers/ClientController.php
app/Http/Controllers/SearchController.php
app/Http/Controllers/SmartDocumentController.php
app/Http/Controllers/OnboardingController.php
app/Http/Controllers/ClientWorksheetController.php
app/Exports/ClientsExport.php
resources/views/clients/index.blade.php
tests/Feature/ClientVisibilityTest.php
```

Change:

- Added `Client::visibleTo()` query scope aligned with policy rules:
  - Partners see all clients.
  - Managers see clients in their branch (or clients without a branch).
  - Staff/intern users see clients they manage or clients linked through tasks they created or are assigned to.
- Expanded `ClientPolicy` with `viewAny`, `view`, `create`, and `update` checks.
- Scoped client index queries and added controller authorization on show, edit, update, create, and store.
- Assigned `branch_id` automatically when managers create clients.
- Scoped global search client results, smart-document client pickers, and export collection queries.
- Protected onboarding show and worksheet mutations with client `view`/`update` authorization.
- Hid create/import/export/bulk-delete client actions from staff/intern users in the clients index UI.
- Added `ClientVisibilityTest` for manager branch scoping, staff assignment scoping, page denial, create denial, and search scoping.

## Remaining Risk Notes

- `/install-db` has now been removed from the route file.
- Sensitive modules now have route-level role middleware.
- Policies now protect client import/export/delete, credential access, invoice access, payment access, branch management, profile settings, firm settings, user-management settings, task access, staff work-management actions, expenses, subscriptions, DSCs, TDS entries, and reports. Manager list screens for credentials, invoices, payments, staff, expenses, subscriptions, DSCs, TDS, and reports are branch-scoped. Task list screens are scoped for staff/intern users.
- Client credentials use encrypted casting, branch-aware access policy checks, and vault audit events for create, update, delete, reveal, and copy actions.
- Client index, show, edit, update, search, smart documents, and export paths are now visibility-scoped for managers and staff/intern users.
- Core controller, command, import, and service status logic now uses model constants. Blade display templates and historical migrations still contain literal labels where they are presentation or schema declarations.
- Routes are now split by domain under `routes/modules`; keep future routes in those files instead of growing `routes/web.php`.

## Next Action Log

- Created this handoff plan.
- Ran route discovery: passed with 155 routes.
- Ran frontend build: passed.
- Ran backend tests: initially failed due duplicate `branches.code` migration.
- Fixed the duplicate-column migration issue.
- Applied the pending migration with `--force`.
- Reran backend tests: passed with 35 tests and 97 assertions.
- Removed the public `/install-db` route.
- Added role middleware around sensitive admin, finance, reporting, credential, and staff-management routes.
- Added security access regression tests.
- Fixed a billing queue query/view mismatch uncovered by the security tests.
- Reran backend tests: passed with 40 tests and 126 assertions.
- Reran frontend build: passed.
- Reran route discovery: passed with 154 routes.
- Reran migration status: all migrations are marked as run.
- Split routes into module files by domain.
- Reran route discovery after the split: passed with 154 routes.
- Reran backend tests after the split: passed with 40 tests and 126 assertions.
- Reran frontend build after the split: passed.
- Added explicit Laravel policies for clients, credentials, invoices, and payments.
- Wired policies into client, credential, invoice, and payment controllers.
- Added branch-aware policy regression tests.
- Reran backend tests after policy layer: passed with 45 tests and 137 assertions.
- Reran frontend build after policy layer: passed.
- Reran route discovery after policy layer: passed with 154 routes.
- Reran migration status after policy layer: all migrations are marked as run.
- Added branch scoping for credential, invoice, and payment list screens.
- Added branch scoping for manager client exports and manager client imports.
- Added branch-scoped index regression tests.
- Reran backend tests after branch-scope tightening: passed with 48 tests and 146 assertions.
- Reran frontend build after branch-scope tightening: passed.
- Reran route discovery after branch-scope tightening: passed with 154 routes.
- Reran migration status after branch-scope tightening: all migrations are marked as run.
- Added guarded `users.theme` migration and applied it locally.
- Added branch and settings policies.
- Separated profile settings updates from partner-only firm settings updates.
- Hid firm settings from non-partner settings pages.
- Trimmed unused branch resource routes.
- Added operations policy regression tests.
- Reran backend tests after operations policy layer: passed with 53 tests and 168 assertions.
- Reran frontend build after operations policy layer: passed.
- Reran route discovery after operations policy layer: passed with 150 routes.
- Reran migration status after operations policy layer: all migrations are marked as run.
- Added task policy and registered it.
- Scoped staff/intern task list visibility and assignee dropdowns.
- Changed task creation to set `created_by` from the authenticated user instead of request input.
- Restricted task deletion and mark-FOC to managers/partners.
- Removed the unused task show route.
- Added task policy regression tests.
- Ran task-focused tests: passed with 9 tests and 27 assertions.
- Reran backend tests after task policy layer: passed with 59 tests and 187 assertions.
- Reran frontend build after task policy layer: passed.
- Reran route discovery after task policy layer: passed with 149 routes.
- Reran migration status after task policy layer: all migrations are marked as run.
- Added staff policy and registered it for the `User` model.
- Scoped manager staff lists, staff creation, staff detail access, reminders, and work allotment by branch.
- Added staff policy regression tests.
- Ran staff/security focused tests: passed with 10 tests and 51 assertions.
- Reran backend tests after staff policy layer: passed with 64 tests and 209 assertions.
- Reran frontend build after staff policy layer: passed.
- Reran route discovery after staff policy layer: passed with 149 routes.
- Reran migration status after staff policy layer: all migrations are marked as run.
- Added policies and controller authorization for reports, expenses, subscriptions, DSCs, and TDS.
- Scoped manager-facing list screens and report filters/summaries for those modules by branch.
- Fixed financial report month grouping for SQLite test compatibility.
- Fixed TDS resource route binding and trimmed unused subscription resource routes.
- Added finance/compliance/report policy regression tests.
- Ran finance/compliance focused tests: passed with 5 tests and 34 assertions.
- Reran route discovery after finance/compliance/report policy layer: passed with 146 routes.
- Reran backend tests after finance/compliance/report policy layer: passed with 69 tests and 243 assertions.
- Reran frontend build after finance/compliance/report policy layer: passed.
- Reran migration status after finance/compliance/report policy layer: all migrations are marked as run.
- Added newer module flow regression tests for credentials, payments, expenses, DSCs, TDS, subscriptions, reports, and system actions.
- Ran newer module focused tests: passed with 8 tests and 85 assertions.
- Reran backend tests after newer module flow coverage: passed with 77 tests and 328 assertions.
- Reran route discovery after newer module flow coverage: passed with 146 routes.
- Reran frontend build after newer module flow coverage: passed.
- Reran migration status after newer module flow coverage: all migrations are marked as run.
- Added model constants for task, service due, invoice, subscription, DSC, client, client-service, and personal-renewal statuses.
- Replaced behavior-driving status strings in controllers, commands, imports, services, and newer tests.
- Reran backend tests after status constants normalization: passed with 77 tests and 328 assertions.
- Reran route discovery after status constants normalization: passed with 146 routes.
- Reran frontend build after status constants normalization: passed.
- Reran migration status after status constants normalization: all migrations are marked as run.
- Added credential vault audit logging for create, update, delete, reveal, and copy actions.
- Added `POST /credentials/{credential}/audit` for reveal/copy audit events.
- Wired audit calls from the credentials index and client edit credential tabs.
- Added `CredentialVaultAuditTest` regression coverage.
- Reran backend tests after credential vault audit layer: passed with 81 tests and 347 assertions.
- Scoped client index/show/edit/update visibility by role:
  - Managers: branch-scoped client lists and pages.
  - Staff/intern: only clients they manage or clients linked through assigned/created tasks.
  - Partners: full client access unchanged.
- Restricted client create/import/export/bulk delete UI and routes to partners/managers.
- Scoped global search, smart documents, and client export queries to visible clients.
- Added client visibility regression tests.
- Reran backend tests after client visibility layer: passed with 87 tests and 365 assertions.
- QA alignment (2026-05-29): reconciled feature test register; added `FirmLiveQATest`, `DeepPostFlowTest`; fixed `Client` mobile accessor and compliance report test date window.
- Reran backend tests after QA alignment: passed with 111 tests and 525 assertions.
- Added `SystemBackupTest` to verify that the `backup:run` console command operates correctly (including `:memory:` SQLite databases during tests) and that partner-only endpoints for backup execution, download, and deletion are properly secured.
- Trimmed unused resource routes (e.g. `show` on `personal-renewals`, `dscs`, `expenses`; `create`/`edit`/`show` on `services`; `show`/`edit`/`update`/`destroy` on `leaves`), reducing total routes from 147 to 142.
- Intelligence Phases 1–4 implemented; doc reconciliation pass (2026-05-29): handoff status updated to **178 tests**; see `FUTURE_INTELLIGENCE_ROADMAP.md` and `FEATURE_SUGGESTIONS.md` status sections.

## Current Verification Status

- Route discovery: Passed, ~150+ routes (includes `/webhooks/whatsapp`, portal, intelligence modules).
- Laravel tests: Passed, **272 tests and 1008 assertions** (2026-05-30, go-live readiness pass).
- Live browser QA: `npm run test:browser` — **72 checks passed** (Playwright).
- Go-live summary: [GO_LIVE_QA_REPORT.md](./GO_LIVE_QA_REPORT.md).
- Frontend build: Passed (last verified during handoff pass).
- Migration status: Passed, all migrations run (including intelligence + `whatsapp_message_logs`).

### Intelligence modules (Phases 1–4)

| Phase | Delivered | Tests |
| --- | --- | --- |
| 1 | Anomaly alerts (`anomaly:scan`), AI assistant on client show | `IntelligencePhaseOneTest`, `AnomalyScannerTest` |
| 2 | Collections center, compliance risk scores, client timeline | `IntelligencePhaseTwoTest` |
| 3 | UPI payment links/QR, document review queue, client portal | `IntelligencePhaseThreeTest` |
| 4 | WhatsApp inbound webhook bot, compliance risk v2 | `IntelligencePhaseFourTest` |

See `docs/FUTURE_INTELLIGENCE_ROADMAP.md` for config (`AI_*`, `WHATSAPP_INBOUND_*`, `PAYMENTS_UPI_ENABLED`). E-invoice / IRN remains out of scope.

## Live Dashboard QA (2026-05-29)

- Reconciled `docs/DASHBOARD_FEATURE_TEST.md` columns to **Article | Associate | Partner** per `FIRM_ROLES_AND_ACCESS.md`.
- Associate finance/admin cells corrected (read-only invoices; no payments/billing/staff/reports).
- Added `tests/Feature/FirmLiveQATest.php` — HTTP matrix for partner, associate, and article roles.
- Added `tests/Feature/DeepPostFlowTest.php` — billing process, invoice create, WhatsApp sends (mocked), client approval covered in `FirmRolesTest`.
- Article browser UX fixes retained from 2026-05-25 pass (sidebar, finance KPI hiding, welcome modal).
- Seeded logins: `php artisan users:ensure-firm-logins` (`rajat@rlassociates.in`, `associate@rlassociates.in`, `article@rlassociates.in`).

### Production smoke (2026-05-30)

- **Live URL:** `https://kuhu.org.in` (login works; legacy user dropdown, only partner seeded).
- **`app.kuhu.org.in`:** 404 — do not use for `APP_URL`.
- **Failures:** `/billing`, `/invoices` return **500** until latest code + `migrate --force` deployed.
- **Tool:** `npm run test:production` — details in [PRODUCTION_SMOKE_LOG.md](./PRODUCTION_SMOKE_LOG.md).

## Code fixes during QA alignment (2026-05-29)

- `Client::getMobileNumberAttribute()` — maps `primary_contact_phone` for WhatsApp controllers.
- Compliance report tests: service due dates set within current month filter window.

## Recommended Next Work

1. Production smoke at `https://app.kuhu.org.in` when the host is reachable (see [GO_LIVE_QA_REPORT.md](./GO_LIVE_QA_REPORT.md)).
2. Change default `password` for all firm logins after deploy.
3. Configure Meta WhatsApp + inbound webhook on production `.env`.
4. Optional: intelligence roadmap items in `FUTURE_INTELLIGENCE_ROADMAP.md`.

## Polish applied (2026-05-29)

- Header user menu with real name, role, optional settings link, and **Sign out** button.
- Leaves already linked in sidebar for partner/manager (`staff` module + `managesFirmModules`).
