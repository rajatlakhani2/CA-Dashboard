# Production smoke log

## 2026-05-30 — https://kuhu.org.in

**Host:** LiteSpeed (cPanel-style). **Subdomain** `app.kuhu.org.in` → **404** (not configured).

### Config prepared (repo)

| Artifact | Purpose |
|----------|---------|
| `.env.production.example` | Server `.env` template (MySQL, `APP_DEBUG=false`, `APP_URL=https://kuhu.org.in`) |
| `docs/PRODUCTION_SETUP.md` | Deploy + cron + smoke steps |
| `npm run test:production` | Short Playwright smoke (`production-smoke.cjs`) |

### Automated smoke results

| Check | Result |
|-------|--------|
| Guest `/` → login | ✓ |
| Partner login (legacy dropdown) | ✓ — only **Rajat** in list; email/password login not deployed yet |
| `/dashboard` | ✓ |
| `/partner-dashboard` | ✓ |
| `/clients` | ✓ |
| `/billing` | ✗ **500 Server Error** |
| `/invoices` | ✗ **500 Server Error** |
| Associate / Article | Skipped (users not in legacy dropdown) |

### Required server actions

1. **Deploy latest code** from this repo (email login, billing fixes, migrations).
2. On server `.env`: copy from `.env.production.example`; set MySQL credentials; `APP_URL=https://kuhu.org.in`.
3. Run:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan users:ensure-firm-logins
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
4. Upload `public/build/` from local `npm run build`.
5. Re-run: `BASE_URL=https://kuhu.org.in npm run test:production`
6. Change passwords from default `password`.

### Manual smoke (after redeploy)

Partner: login → dashboard → clients → **billing** (no 500) → invoices → sign out.  
Article: `article@rlassociates.in` → **My Day**.  
Associate: clients OK, billing **403**.
