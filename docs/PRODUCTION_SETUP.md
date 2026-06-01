# Production setup — app.kuhu.org.in

**Live app URL:** https://app.kuhu.org.in

Use the **subdomain** so cPanel can set document root to `public/` (avoids main-domain `public_html` restrictions).

---

## 1. cPanel subdomain

**Domains** → **Subdomains** (or **Create Subdomain**):

| Field | Value |
|--------|--------|
| Subdomain | `app` |
| Domain | `kuhu.org.in` |
| Document Root | `public_html/app.kuhu.org.in/public` |

**File layout:**

```text
public_html/app.kuhu.org.in/
  artisan
  app/
  .env
  vendor/
  public/          ← document root points here
    index.php
    .htaccess
    build/
```

Upload/extract the Laravel zip **into** `app.kuhu.org.in/` (not only into `public_html` root).

Enable **AutoSSL** for `app.kuhu.org.in`.

---

## 2. Server `.env`

Copy [`.env.production.example`](../.env.production.example) → `app.kuhu.org.in/.env`:

```ini
APP_URL=https://app.kuhu.org.in
APP_ENV=production
APP_DEBUG=false
```

Fill MySQL `DB_*` from cPanel.

---

## 3. Terminal

```bash
cd ~/public_html/app.kuhu.org.in

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan users:ensure-firm-logins
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

---

## 4. Cron

```bash
* * * * * cd ~/public_html/app.kuhu.org.in && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. Verify

```text
https://app.kuhu.org.in/login
```

From your PC:

```powershell
$env:BASE_URL='https://app.kuhu.org.in'
npm run test:production
```

---

## Main domain kuhu.org.in

Optional: leave as marketing site or redirect to `https://app.kuhu.org.in` via cPanel **Redirects**.
