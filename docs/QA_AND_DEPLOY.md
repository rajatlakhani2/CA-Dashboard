# QA staging and production deploy

Partial `curl` sync scripts only update a **file list** and often leave production broken (missing Blade partials, stale route cache). Use **full deploy from GitHub** instead.

## Recommended workflow

```mermaid
flowchart LR
  dev[Edit code locally] --> pushQa[Push to qa branch]
  pushQa --> qaSite[QA site test]
  qaSite --> merge[Merge qa into master]
  merge --> prod[Production deploy]
```

| Environment | URL (example) | Branch | How to deploy |
|-------------|---------------|--------|----------------|
| **QA** | `https://qa.kuhu.org.in` or subdomain you create | `qa` | `scripts/deploy-qa.sh` or GitHub Action |
| **Production** | `https://app.kuhu.org.in` | `master` | `scripts/deploy-production-safe.sh` or GitHub Action |

## One-time QA setup (cPanel)

1. Create folder: `~/public_html/qa.kuhu.org.in` (or subdomain in cPanel → document root).
2. Copy production env:
   ```bash
   cp ~/public_html/app.kuhu.org.in/.env ~/public_html/qa.kuhu.org.in/.env
   ```
3. Edit QA `.env`:
   - `APP_URL=https://qa.kuhu.org.in` (your real QA URL)
   - `APP_ENV=staging`
   - `DB_DATABASE=..._qa` (separate database recommended)
4. Point subdomain `qa.kuhu.org.in` to `public_html/qa.kuhu.org.in/public` (same layout as production).

## Deploy to QA (manual, full repo from Git)

```bash
export QA_DIR="$HOME/public_html/qa.kuhu.org.in"
export BRANCH=qa
cd "$QA_DIR"
curl -fsSL -o scripts/deploy-qa.sh \
  https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master/scripts/deploy-qa.sh
bash scripts/deploy-qa.sh
```

Test login, dashboard tabs, subscriptions, passwords. When OK:

```bash
git checkout master
git merge qa
git push origin master
```

## Deploy to production (safe, full repo)

```bash
cd ~/public_html/app.kuhu.org.in
curl -fsSL -o scripts/deploy-production-safe.sh \
  https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master/scripts/deploy-production-safe.sh
bash scripts/deploy-production-safe.sh
```

This keeps `.env` / `APP_KEY` and runs `migrate` (not `migrate:fresh`).

## GitHub Actions (optional, best for “git → live”)

Repo already has:

- `.github/workflows/deploy-production.yml` — push to `master`
- `.github/workflows/deploy-qa.yml` — push to `qa`

Add secrets under **GitHub → Settings → Secrets → Actions**:

| Secret | Example |
|--------|---------|
| `SSH_HOST` | cPanel server hostname |
| `SSH_USER` | `kuhuorgi` |
| `SSH_KEY` | Private SSH key (PEM) |
| `DEPLOY_PATH` | `/home/kuhuorgi/public_html/app.kuhu.org.in` |
| `QA_DEPLOY_PATH` | `/home/kuhuorgi/public_html/qa.kuhu.org.in` |

Then: push to `qa` → auto QA deploy; merge to `master` → auto production deploy.

## Verify dashboard after deploy

1. `https://app.kuhu.org.in/dashboard` — must show **Build: tabs-v2-20260604**
2. `https://app.kuhu.org.in/dashboard/deploy-probe` — JSON with `tabs_v2_marker: true`
3. `https://app.kuhu.org.in/ping.php` — static JSON

## Stop using for production

- `scripts/sync-saas-full.sh` (incomplete file list)
- `scripts/sync-all-pending-fixes.sh`
- Per-file `curl` unless emergency

Use **`deploy-production-safe.sh`** or GitHub Actions instead.
