# Firm roles and access

This document describes the three login roles used in production QA and how they map to modules.

## Seeded users

| Role | Email | Password (default) |
|------|-------|-------------------|
| Partner (Rajat) | `rajat@rlassociates.in` | `password` |
| Associate (Nilesh Bhai) | `nilesh@rlassociates.in` | `password` |
| Article clerk | `article@rlassociates.in` | `password` |

Setup commands:

```bash
php artisan migrate
php artisan users:ensure-firm-logins
php artisan clients:assign-portfolios
```

## Access matrix

| Module | Partner | Associate | Article |
|--------|---------|-----------|---------|
| Dashboard | Full | Own workload | Redirects to Tasks |
| Clients list | All approved | Own portfolio (`manager_id`) | No (submit only) |
| Client create | Yes | Yes (auto-approved, own portfolio) | Submit → pending approval |
| Client approve | Yes | No | No |
| Tasks | Full | Assigned / created | Assigned only; status updates |
| Invoices | Full CRUD | **Read-only** for own clients | No |
| Payments, billing, ledger | Yes | No | No |
| Staff, reports, credentials, TDS, subscriptions | Yes | No | No |
| Settings / users / branches | Partner only | No | No |

## Nilesh (associate) — invoices

- Sidebar: **My Client Invoices** → scoped list (raised / received tabs only).
- Client profile: YTD billed, collected, outstanding (no ledger link); recent invoices read-only.
- Cannot create, edit, delete, or email invoices; PDF download allowed for own clients.

## Article — client onboarding

1. Article submits client from **Add Client** (no client list).
2. Client stored as `approval_status = pending`.
3. Partner receives optional email (`ClientPendingApprovalMail`) and sees queue on **Clients** and dashboard banner.
4. Partner approves via **Approve** on Clients index.

## Product decisions (implemented)

- Staff billing on client show: hidden for non-managers (unchanged).
- Sidebar: Billing Queue, Subscriptions, Leaves visible to partner/manager only.
- WhatsApp invoice send: partner/manager only (mock/integration unchanged).
- Additional article users: add via Settings → Users or `FirmTeamSeeder`.

## Production QA checklist

1. Log in as each role; confirm sidebar matches matrix above.
2. As Nilesh: open own client → see finance summary and invoices; open Rajat client → 403.
3. As Article: submit client → Rajat sees pending banner and approval queue.
4. As Rajat: approve client → visible to Nilesh if approved firm-wide.
5. Run `php artisan test` before deploy.
