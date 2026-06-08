#!/bin/bash
# Deploy FULL repo from GitHub master to production — replaces partial curl sync scripts.
# Keeps .env and APP_KEY. Does NOT run migrate:fresh.
#
# Usage:
#   cd ~/public_html/app.kuhu.org.in
#   bash scripts/deploy-production-safe.sh
set -e

APP_DIR="${APP_DIR:-$HOME/public_html/app.kuhu.org.in}"
BRANCH="${BRANCH:-master}"
REPO_ZIP="https://github.com/rajatlakhani2/CA-Dashboard/archive/refs/heads/${BRANCH}.zip"
ARCHIVE_ROOT="CA-Dashboard-${BRANCH}"

cd "$APP_DIR"

if [ ! -f .env ]; then
  echo "ERROR: .env missing in $APP_DIR"
  exit 1
fi

echo "==> Production deploy: $APP_DIR (branch: $BRANCH)"
cp -f .env /tmp/ca-dashboard-prod.env.backup

curl -fsSL -o /tmp/ca-dashboard-prod.zip "$REPO_ZIP"
rm -rf "/tmp/${ARCHIVE_ROOT}"
unzip -q -o /tmp/ca-dashboard-prod.zip -d /tmp
rm -f /tmp/ca-dashboard-prod.zip

shopt -s dotglob
for item in "/tmp/${ARCHIVE_ROOT}"/*; do
  name="$(basename "$item")"
  if [ "$name" = ".env" ]; then
    continue
  fi
  rm -rf "./$name"
  cp -a "$item" "./$name"
done
shopt -u dotglob

cp -f /tmp/ca-dashboard-prod.env.backup .env
rm -rf "/tmp/${ARCHIVE_ROOT}"

if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction
fi

if command -v npm >/dev/null 2>&1 && [ -f package.json ]; then
  echo "==> Building frontend assets (npm run build)..."
  npm ci --omit=dev 2>/dev/null || npm install --omit=dev 2>/dev/null || true
  npm run build 2>/dev/null || echo "WARN: npm build skipped — using committed public/build if present"
elif [ ! -f public/build/manifest.json ]; then
  echo "WARN: public/build/manifest.json missing — deploy committed build or install Node on server"
fi

php artisan migrate --force
php artisan optimize:clear
php artisan view:clear
rm -f bootstrap/cache/routes-v7.php bootstrap/cache/routes*.php 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php -r "if (function_exists('opcache_reset')) { opcache_reset(); }"

STAMP="tabs-v2-$(date -u +%Y%m%d-%H%M%S)"
echo "$STAMP" > public/dashboard-build.txt
printf '%s\n' "{\"build\":\"tabs-v2-20260604\",\"deploy_stamp\":\"$STAMP\",\"ok\":true}" > public/build-status.json

echo ""
echo "SUCCESS: Production synced from GitHub ${BRANCH}."
echo "Verify: https://app.kuhu.org.in/dashboard and /dashboard/deploy-probe"
