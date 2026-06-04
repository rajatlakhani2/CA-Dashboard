# CA Dashboard

Practice management dashboard for CA firms — clients, tasks, invoices, compliance, and multi-user workspace.

## GitHub repository

**https://github.com/rajatlakhani2/CA-Dashboard**

Clone anywhere:

```bash
git clone https://github.com/rajatlakhani2/CA-Dashboard.git
cd CA-Dashboard
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Production (cPanel) — no git yet?

If `git pull` says *not a git repository*, use the one-time setup script:

```bash
cd ~/app.kuhu.org.in   # or ~/public_html/app.kuhu.org.in
curl -fsSL -o /tmp/setup-git.sh \
  https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master/scripts/setup-git-clone-on-cpanel.sh
export CONFIRM=1
bash /tmp/setup-git.sh
```

After that, deploy with:

```bash
cd ~/app.kuhu.org.in
git pull origin master
composer install --no-dev
php artisan migrate --force
php artisan optimize:clear
```

Without git, sync UI only:

```bash
bash scripts/sync-saas-dashboard.sh
```

See [docs/CPANEL_DEPLOY_STEPS.md](docs/CPANEL_DEPLOY_STEPS.md).

## License

Proprietary — RL Associates / Kuhu.
