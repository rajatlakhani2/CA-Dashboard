# Deploy Checklist — CA Dashboard

Use this before and after pushing to production (e.g. cPanel or VPS).

## 1. Pre-deploy (local)

```powershell
cd "D:\New folder\Dashboard\CA Dashboard"
.\.tools\php\php.exe artisan test
npm run build
```

Optional with app running:

```powershell
.\.tools\php\php.exe artisan serve
npm run test:browser
```

## 2. Environment (`.env` on server)

Full template: [`.env.production.example`](../.env.production.example) · Steps: [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md)

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` = **`https://app.kuhu.org.in`**
- Document root = **`public_html/app.kuhu.org.in/public`**
- Database credentials (MySQL on cPanel)
- `APP_ALLOW_DANGEROUS_SYSTEM=false` (blocks migrate from UI in production)
- WhatsApp / mail keys if using notifications

## 3. Server commands

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan users:ensure-firm-logins
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Upload `public/build` from `npm run build` (or build on server if Node is available).

**Web root** must point to the `public/` folder (not project root).

## 4. Post-deploy smoke

| Check | URL / action |
|-------|----------------|
| Login | `/login` — partner `rajat@rlassociates.in` / `password` |
| Dashboard | `/dashboard` |
| Partner overview | `/partner-dashboard` |
| Clients | `/clients` |
| Billing | `/billing` |
| Sign out | Header menu → **Sign out** → back to login |
| Article | `article@rlassociates.in` → lands on `/my-day` |

```bash
npm run test:browser   # against local/staging URL via BASE_URL=
```

## 5. Scheduled jobs (cron)

On production, run Laravel scheduler every minute:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Includes: backups, service dues, reminders, daily task digest (`tasks:send-daily-digest`).

## 6. Firm setup (one-time)

```bash
php artisan users:ensure-firm-logins
php artisan clients:assign-portfolios   # if using portfolio assignment
```

Default passwords are `password` — change in **Settings → Users** after first login.

## 7. Do not deploy

- `.env` (secrets) — configure on server only
- `.tools/php/` — local dev only unless you rely on it on server
- `node_modules/`, `tests/`, scratch files, `*.zip` patches

## 8. Rollback

- Restore DB backup from **System Health** or hosting panel
- Redeploy previous `public/build` and code snapshot

---

**Verification baseline (2026-05-30):** 272 PHPUnit tests, 72 Playwright browser checks (`browser-live-qa.cjs`). See [GO_LIVE_QA_REPORT.md](./GO_LIVE_QA_REPORT.md).
