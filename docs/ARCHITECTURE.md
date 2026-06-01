# CA Dashboard Architecture

## Purpose

CA Dashboard is a Laravel 12 practice-management application for a chartered accountancy office. It combines client records, recurring compliance dues, task tracking, billing, collections, staff work allocation, document tracking, and operational reports into one authenticated web app.

## Runtime Stack

- Backend: Laravel 12, PHP 8.2
- UI: Blade templates, Vite, Tailwind CSS, Alpine.js
- Database: SQLite in the current local setup
- Exports: Maatwebsite Excel
- PDFs: barryvdh/laravel-dompdf
- Audit log: spatie/laravel-activitylog
- Notifications: WhatsApp service integration and Laravel mail classes

## Application Layers

```text
routes/web.php
  -> routes/modules/operations.php
  -> routes/modules/clients.php
  -> routes/modules/work.php
  -> routes/modules/compliance.php
  -> routes/modules/finance.php
  -> routes/modules/reports.php
  -> app/Http/Controllers
      -> app/Models
      -> app/Services
      -> app/Exports / app/Imports / app/Mail
  -> resources/views
  -> database/migrations
```

## Main Directories

- `app/Http/Controllers`: Web request handlers. Each major feature has a controller, for example `ClientController`, `TaskController`, `InvoiceController`, `StaffController`, and `ReportController`.
- `app/Models`: Eloquent models for clients, services, dues, tasks, invoices, payments, staff, credentials, branches, and other feature data.
- `app/Services`: Shared service logic. Current important services include service-due generation and WhatsApp delivery.
- `app/Console/Commands`: Scheduled or manually executed operational jobs such as service due generation, subscription processing, and daily reminders.
- `resources/views`: Blade screens organized mostly by feature folder.
- `database/migrations`: Schema history for the core app and newer feature tables.
- `tests/Feature` and `tests/Unit`: Focused coverage for clients, invoices, tasks, service dues, and personal renewals.

## Request Flow

1. User logs in through `LoginController`.
2. `routes/web.php` handles public entry points and loads authenticated route modules from `routes/modules`.
3. Controllers validate request data, query/update Eloquent models, and return Blade views.
4. Views use the shared layout in `resources/views/layouts/app.blade.php`.
5. Background or operator-triggered commands handle recurring generation and reminders.

## Current Route Organization

`routes/web.php` now stays small and loads domain route modules:

```text
routes/web.php
routes/modules/operations.php
routes/modules/clients.php
routes/modules/work.php
routes/modules/compliance.php
routes/modules/finance.php
routes/modules/reports.php
```

The app currently discovers 149 routes after removing the public installer route, trimming unused branch resource routes, and removing the unused task show route.

## Domain Areas

### Client 360

Owns client master data, contacts, service assignments, worksheets, documents, credentials, onboarding, and client ledger.

### Work Management

Owns tasks, staff assignment, time entries, leaves, daily reminders, and staff 360 views.

### Compliance

Owns services, client services, generated service dues, compliance dashboard, overdue tracking, DSC tracking, TDS entries, and due-date reports.

### Finance

Owns invoices, invoice items, payments, expenses, billing queue, subscriptions, receipts, statement of account, and collections metrics.

### Operations

Owns settings, users, roles, branches, activity log, system health, cache/migration tools, recycle bin, and notifications.

## Security Notes

- `RoleMiddleware` exists and is registered as `role`.
- Sensitive route groups now use role middleware for partner-only and partner-or-manager access.
- `SystemController` still performs internal partner checks in addition to route-level middleware.
- The public `/install-db` route has been removed.
- Client credentials use Laravel encrypted casting for passwords, policy checks for manager/partner branch access, and vault audit events for create, update, delete, reveal, and copy actions.
- Explicit Laravel policies are now registered for clients, client credentials, invoices, payments, branches, settings, tasks, and staff/user work-management actions.
- Invoice, payment, and credential policies apply branch-aware record checks for managers; partners retain full access.
- Invoice, payment, and credential list screens are branch-scoped for managers.
- Manager client exports are branch-scoped, and manager client imports assign new clients to that manager's branch.
- Client index/show/edit/update visibility is branch-scoped for managers and assignment-scoped for staff/intern users (managed clients or task-linked clients).
- Branch and setting policies are registered. Firm-wide settings are partner-only, while users can still update their own profile settings.
- Task policy checks are registered. Partners and managers can manage team tasks; staff and interns can only see/update tasks assigned to them or created by them, cannot assign work to other users, and cannot delete or mark tasks FOC.
- Staff policy checks are registered. Partners can manage staff across branches; managers can view/remind/allot work only within their own branch and cannot create partner/manager accounts.
- Additional policies are still recommended for reports, expenses, subscriptions, DSC, and TDS.

## Data Integrity Notes

- Several modules are linked through conventional Eloquent relationships, especially clients, tasks, invoices, service dues, payments, and users.
- More database constraints would improve safety, especially for status fields, branch/user ownership, invoice/payment consistency, and credential ownership.
- Status values currently appear as strings across controllers. Consider enums or constants to avoid drift between `Completed`, `Done`, `Closed`, `Pending`, and `Overdue`.

## Testing Notes

Current tests cover selected core workflows. Suggested test expansion:

- More authorization tests for remaining modules: reports, expenses, subscriptions, DSC, and TDS
- Credential access and encryption behavior
- Billing queue and payment allocation
- Subscription generation
- Dashboard metric correctness
- Import/export validation
- System routes restricted to partners
