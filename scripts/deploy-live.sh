#!/bin/bash
# One-shot deploy for app.kuhu.org.in (run on cPanel Terminal).
# Requires: .env with valid MySQL, PHP 8.2+, composer on PATH.
set -e

APP_DIR="${APP_DIR:-$HOME/public_html/app.kuhu.org.in}"
REPO_ZIP="https://github.com/rajatlakhani2/CA-Dashboard/archive/refs/heads/master.zip"

cd "$APP_DIR"

if [ ! -f .env ]; then
  echo "ERROR: .env missing. Copy .env.production.example to .env and set DB_* first."
  exit 1
fi

echo "==> Backup .env"
cp -f .env /tmp/ca-dashboard.env.backup

echo "==> Download latest code from GitHub"
curl -fsSL -o /tmp/ca-dashboard.zip "$REPO_ZIP"
rm -rf /tmp/ca-dashboard-master
unzip -q -o /tmp/ca-dashboard.zip -d /tmp
rm -f /tmp/ca-dashboard.zip

echo "==> Sync files (keep .env)"
rsync -a --delete \
  --exclude='.env' \
  --exclude='storage/logs/*' \
  /tmp/CA-Dashboard-master/ ./

cp -f /tmp/ca-dashboard.env.backup .env
rm -rf /tmp/CA-Dashboard-master

if command -v composer >/dev/null 2>&1; then
  echo "==> composer install"
  composer install --no-dev --optimize-autoloader --no-interaction
else
  echo "WARN: composer not found — upload vendor/ or install composer"
fi

echo "==> Laravel setup"
php artisan key:generate --force
php artisan migrate:fresh --force
php artisan users:ensure-firm-logins
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "DONE. Open https://app.kuhu.org.in/login"
echo "Login: rajat@rlassociates.in / password (change in Settings -> Users)"
