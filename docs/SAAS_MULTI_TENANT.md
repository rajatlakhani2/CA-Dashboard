# Multi-tenant SaaS (multiple CA firms)

Each **organization** = one CA firm. Data is isolated by `organization_id`. Users log in with **Workspace ID + email + password**.

## For RLA (existing firm)

Set a friendly login ID:

```bash
php artisan organization:slug --set=rla
php artisan organization:slug
```

Staff login at `/login`:
- **Workspace ID:** `rla`
- **Email / password:** as before

## New CA firm

1. Open **https://app.kuhu.org.in/register**
2. Enter firm name, workspace ID (e.g. `sharma-ca`), admin details
3. Firm gets its own clients, tasks, invoices — invisible to other firms

## Deploy (cPanel, no git)

```bash
curl -fsSL -o /tmp/find-and-fix.sh "https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master/scripts/find-and-fix-cpanel.sh"
bash /tmp/find-and-fix.sh
php artisan migrate --force
php artisan organization:slug --set=rla
```

## What is isolated

Core tables use `organization_id` + automatic query scoping. Users only see rows for their firm when logged in.

## Still manual / future

- Per-firm billing (Stripe)
- Subdomain per firm
- Audit every report/export path
- Client portal tokens scoped per firm
