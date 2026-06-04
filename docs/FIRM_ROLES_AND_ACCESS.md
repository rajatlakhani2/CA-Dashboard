# Firm roles and access (multi-tenant SaaS)

Each **workspace** (organization) has its own users and data. Roles below apply **inside one firm** only.

## Default seeded users (RLA workspace example)

| Role | Email | Password (default) |
|------|-------|-------------------|
| Partner | `rajat@rlassociates.in` | `password` |
| Associate | `associate@rlassociates.in` | `password` |
| Article clerk | `article@rlassociates.in` | `password` |

Login: **Workspace ID** (e.g. `rla`) + email + password.

Setup:

```bash
php artisan migrate --force
php artisan users:ensure-firm-logins
php artisan organization:slug --set=rla
```

New firms register at `/register` and create their own users via **Staff**.

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

## Associate — invoices

- Sidebar: **My Client Invoices** → scoped list (raised / received tabs only).
- Client profile: YTD billed, collected, outstanding (no ledger link); recent invoices read-only.
- Cannot create, edit, delete, or email invoices; PDF download allowed for own clients.

## Article — client onboarding

1. Article submits client from **Add Client** (no client list).
2. Client stored as `approval_status = pending`.
3. Partner receives optional email and sees queue on **Clients** and dashboard banner.
4. Partner approves via **Approve** on Clients index.

## Production QA checklist

1. Log in with workspace ID + each role; confirm sidebar matches matrix.
2. As associate: open own client → finance summary; other manager’s client → 403.
3. As article: submit client → partner sees pending queue.
4. Run `php artisan test` before deploy.
