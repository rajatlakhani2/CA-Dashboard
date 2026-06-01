# Deploy with GitHub

**Repository:** https://github.com/rajatlakhani2/CA-Dashboard  
**Production URL:** https://app.kuhu.org.in  
**Server path:** `~/public_html/app.kuhu.org.in`  
**Document root:** `public_html/app.kuhu.org.in/public`

---

## Option A — Git pull on cPanel (simplest)

### One-time

1. cPanel → **Git Version Control** → **Create**
2. Clone URL: `https://github.com/rajatlakhani2/CA-Dashboard.git`
3. Repository path: `public_html/app.kuhu.org.in`
4. Keep **`.env` on the server only** (create from `.env.production.example`)
5. Subdomain document root: `public_html/app.kuhu.org.in/public`

### Each update

**Terminal:**

```bash
cd ~/public_html/app.kuhu.org.in
git pull origin master
composer install --no-dev --optimize-autoloader
npm ci && npm run build   # skip if Node not on server — build on PC and upload public/build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Or run: `bash deploy-cpanel.sh` after `git pull`.

---

## Option B — GitHub Actions (automatic on push)

Workflow: `.github/workflows/deploy-production.yml`

### One-time: add GitHub Secrets

Repo → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

| Secret | Example |
|--------|---------|
| `SSH_HOST` | hostname from cPanel SSH (or server IP) |
| `SSH_USER` | `kuhuorgi` |
| `SSH_KEY` | private SSH key (PEM) authorized in cPanel |
| `DEPLOY_PATH` | `/home/kuhuorgi/public_html/app.kuhu.org.in` |

Enable **SSH** in cPanel and add the matching **public** key.

### Deploy

Push to **`master`**:

```bash
git push origin master
```

Actions tab → **Deploy Production** → watch logs.

**Note:** `.env` is never in GitHub — keep it only on the server.

---

## First-time server setup (after clone)

```bash
cd ~/public_html/app.kuhu.org.in
cp .env.production.example .env   # then edit DB_* and APP_URL
php artisan key:generate --force
php artisan migrate:fresh --force
php artisan users:ensure-firm-logins
php artisan config:cache
```

Default logins: `rajat@rlassociates.in` / `password` — change after login.

---

## What stays out of Git

- `.env` (secrets)
- `vendor/` (built on CI or `composer install` on server)
- `node_modules/`
- `*.zip`, `.tools/`
