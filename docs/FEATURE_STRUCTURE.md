# Feature Structure

## Existing Features

| Feature | Main Files | Current Shape | Suggested Owner |
| --- | --- | --- | --- |
| Authentication | `LoginController`, `resources/views/auth` | Login/logout and authenticated route group | Operations |
| Dashboard | `DashboardController`, `dashboard.blade.php` | KPI cards, calendar, alerts, pending tasks, charts | Operations |
| Client Management | `ClientController`, `Client`, `clients/*` | CRUD, import/export, service mapping, worksheets, documents | Client 360 |
| Client Credentials | `ClientCredentialController`, `ClientCredential`, `credentials/*` | Encrypted portal credentials per client | Client 360 |
| Client Worksheets | `ClientWorksheetController`, `ClientWorksheet` | Client work items that can become billable | Client 360 |
| Service Master | `ServiceController`, `Service` | Manage recurring service definitions | Compliance |
| Service Dues | `ServiceDueController`, `ServiceDue`, `ServiceDueGenerator` | Generate and complete recurring compliance dues | Compliance |
| Personal Renewals | `PersonalRenewalController`, `PersonalRenewal` | Personal renewal tracking and WhatsApp reminders | Compliance |
| Tasks | `TaskController`, `Task`, `TaskPolicy`, `tasks/*` | Task CRUD, status updates, billing linkage, assignment, staff-scoped visibility | Work |
| Workload Planner | `WorkloadPlannerController`, `WorkloadPlannerBuilder`, `workload/*` | Team kanban, load metrics, drag reassignment (partner/manager) | Work |
| Staff | `StaffController`, `User`, `StaffPolicy`, `staff/*` | Staff directory, branch-scoped work allotment, WhatsApp reminders | Work |
| Leaves | `LeaveController`, `Leave` | Leave CRUD and approval status | Work |
| Time Entries | `TimeEntryController`, `TimeEntry` | Time capture linked to users/tasks | Work |
| Invoices | `InvoiceController`, `Invoice`, `InvoiceItem` | Invoice CRUD, PDF, email, WhatsApp | Finance |
| Payments | `PaymentController`, `Payment` | Payment recording and receipt download | Finance |
| Billing Queue | `BillingController` | Process unbilled dues/tasks/worksheets | Finance |
| Expenses | `ExpenseController`, `Expense` | Expense management | Finance |
| Ledger | `LedgerController` | Client ledger and statement of account | Finance |
| Subscriptions | `SubscriptionController`, `Subscription` | Retainer/subscription billing | Finance |
| DSC Tracker | `DscController`, `Dsc` | Digital signature certificate expiry tracking | Compliance |
| TDS Management | `TdsController`, `TdsEntry` | TDS entries and management | Compliance |
| Reports | `ReportController`, `reports/*` | Financial, compliance, service, client, task, staff productivity, client profitability | Reporting |
| Compliance 360 | `ComplianceController`, `compliance/*` | Consolidated compliance view | Reporting |
| Smart Archive | `SmartDocumentController`, `ClientDocument` | Client document browsing | Client 360 |
| Activity Pulse | `ActivityController`, Spatie activity log | Audit trail / recent activity | Operations |
| Branches | `BranchController`, `Branch` | Branch master data | Operations |
| Settings and Users | `SettingsController`, `Setting`, `User` | App settings, profile, user roles | Operations |
| Recycle Bin | `RecycleBinController` | Restore and permanent delete | Operations |
| System Health | `SystemController`, `system/*` | Cache, optimize, migrate, logs | Operations |
| WhatsApp | `WhatsAppController`, `WhatsAppService` | Settings, test sends, inbound webhook setup UI | Operations |
| Partner Dashboard | `PartnerDashboardController`, `dashboard/partner.blade.php` | Partner KPIs, firm alerts, at-risk compliance | Operations |
| Firm Alerts | `FirmAlert`, `AnomalyScanner`, `anomaly:scan` | Rule-based anomaly detection | Operations |
| AI Assistant | `ClientAiController`, `AiAssistantService`, `ClientContextBuilder` | Summarize / explain overdue / draft WhatsApp (client show) | Client 360 |
| Collections | `CollectionsController`, `CollectionsCallListBuilder` | Aging, call list, follow-ups | Finance |
| Compliance Risk | `ComplianceRiskScorer`, `compliance:score-risk` | Per client×service risk scores | Compliance |
| Client Timeline | `ClientTimelineBuilder` | Unified history on client show | Client 360 |
| Document Ingestions | `DocumentIngestionController`, `DocumentFieldGuesser` | Upload review queue → task | Client 360 |
| Client Portal | `ClientPortalController`, `routes/portal.php` | Magic-link client view (dues, pay, upload) | Client 360 |
| WhatsApp Inbound | `WhatsAppWebhookController`, `WhatsAppInboundBot` | Meta webhook auto-reply | Operations |

**Out of scope:** E-invoice / IRN (see `FUTURE_INTELLIGENCE_ROADMAP.md`).

## Recommended Module Grouping

```text
Client 360
  Clients
  Contacts
  Client services
  Worksheets
  Documents
  Credentials
  Onboarding
  Ledger

Work
  Tasks
  Staff
  Time entries
  Leaves
  Daily reminders

Compliance
  Services
  Service dues
  Personal renewals
  DSC tracker
  TDS
  Compliance 360

Finance
  Billing queue
  Invoices
  Payments
  Expenses
  Subscriptions
  Receipts
  Statement of account

Reporting
  Financial reports
  Compliance reports
  Client reports
  Task reports
  Due-date reports

Operations
  Settings
  Users and roles
  Branches
  Activity log
  System health
  Recycle bin
  Notifications
```

## Suggested File Organization For New Work

Keep the current Laravel style, but make each new feature follow the same checklist:

```text
app/Models/{FeatureModel}.php
app/Http/Controllers/{FeatureController}.php
app/Http/Requests/{Feature}/{StoreFeatureRequest}.php
app/Http/Requests/{Feature}/{UpdateFeatureRequest}.php
app/Policies/{FeaturePolicy}.php
app/Services/{FeatureService}.php
resources/views/{feature}/index.blade.php
resources/views/{feature}/create.blade.php
resources/views/{feature}/edit.blade.php
resources/views/{feature}/show.blade.php
database/migrations/{timestamp}_create_{feature}_table.php
tests/Feature/{Feature}Test.php
```

Use request classes for validation once a controller action grows beyond simple CRUD. Use services for workflows that are triggered from multiple places, such as invoice generation, reminder sending, subscription processing, and compliance due generation.

Policy convention now in use:

- Register policies in `App\Providers\AppServiceProvider`.
- Add `authorize()` calls in controller actions for sensitive workflows.
- Keep route-level `role` middleware for broad module access and use policies for action-level or record-level checks.
- Use branch-aware policy checks for records tied to a client, invoice, or branch.
- When adding branch-aware policies, also scope list queries and dropdown options so blocked records do not appear in indexes or selectors.
- For task-style work queues, scope index queries and assignee dropdowns to the current user unless the actor is a manager or partner.
- For staff/work-management screens, partners can operate firm-wide and managers should be scoped to their assigned branch.

## Route Structure

Public routes are intentionally tiny:

```php
Route::get('/login', ...);
Route::post('/login', ...);
Route::get('/', fn () => redirect()->route('login'));
```

Authenticated routes are loaded from domain modules:

```php
Route::middleware('auth')->group(function () {
    require __DIR__.'/modules/operations.php';
    require __DIR__.'/modules/clients.php';
    require __DIR__.'/modules/work.php';
    require __DIR__.'/modules/compliance.php';
    require __DIR__.'/modules/finance.php';
    require __DIR__.'/modules/reports.php';
});
```

The current module files are:

```text
routes/modules/operations.php
routes/modules/clients.php
routes/modules/work.php
routes/modules/compliance.php
routes/modules/finance.php
routes/modules/reports.php
```

Sensitive modules use role middleware, for example:

```php
Route::middleware('role:partner,manager')->group(function () {
    // billing, reports, credentials, finance operations
});

Route::middleware('role:partner')->group(function () {
    // system, users, branch master
});
```

Branch routes currently expose only the implemented branch actions: `index`, `store`, and `destroy`.

Task routes intentionally omit the unused `show` action. Task visibility is policy-scoped: managers and partners see team-wide work, while staff and interns see assigned or self-created work only.

Staff routes stay under `role:partner,manager` and now also use policy checks. Partners see all staff; managers are branch-scoped and can only create staff/intern users in their own branch.

Settings are split by policy:

- Any authenticated user can update their own profile settings.
- Only partners can update firm/company/GST/reminder settings.

## Navigation Structure Suggestion

The sidebar should mirror the domain groups:

- Dashboard
- Client 360: Clients, Passwords, Smart Archive, Onboarding
- Work: Tasks, Staff Directory, Time Tracking, Leaves
- Compliance: Reminders, Personal Renewals, DSC Tracker, TDS, Compliance 360
- Finance: Billing, Invoices, Payments, Expenses, Subscriptions, Ledger
- Reports: Financial, Compliance, Service, Client, Task, Due Date
- Administration: Users, Branches, Service Master, Settings, Activity, Recycle Bin, System

## Status Normalization

Create shared constants or enums for repeated statuses:

- Task: `pending`, `in_progress`, `completed`, `closed`
- Service due: `pending`, `completed`, `overdue`
- Invoice: `draft`, `sent`, `partially_paid`, `paid`, `overdue`, `cancelled`
- Payment: `received`, `reversed`, `failed`
- Leave: `pending`, `approved`, `rejected`

This will reduce controller condition drift and make reports more reliable.
