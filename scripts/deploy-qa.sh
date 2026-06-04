#!/bin/bash
# Deploy full app from GitHub to QA/staging (complete copy — no missing Blade partials).
# First time: copy production .env, set APP_URL and a separate DB, then run this script.
#
# Usage on cPanel:
#   export QA_DIR="$HOME/public_html/qa.kuhu.org.in"
#   export BRANCH=qa
#   bash scripts/deploy-qa.sh
set -e

BRANCH="${BRANCH:-qa}"
QA_DIR="${QA_DIR:-$HOME/public_html/qa.kuhu.org.in}"
REPO_ZIP="https://github.com/rajatlakhani2/CA-Dashboard/archive/refs/heads/${BRANCH}.zip"
ARCHIVE_ROOT="CA-Dashboard-${BRANCH}"

mkdir -p "$QA_DIR"
cd "$QA_DIR"

if [ ! -f .env ]; then
  echo "ERROR: $QA_DIR/.env missing."
  echo "  cp ~/public_html/app.kuhu.org.in/.env .env"
  echo "  Edit APP_URL=https://qa.kuhu.org.in (or your QA subdomain)"
  echo "  Use a separate DB_DATABASE for QA (recommended)"
  exit 1
fi

echo "==> QA deploy: $QA_DIR (branch: $BRANCH)"
cp -f .env /tmp/ca-dashboard-qa.env.backup

echo "==> Download GitHub zip"
curl -fsSL -o /tmp/ca-dashboard-qa.zip "$REPO_ZIP"
rm -rf "/tmp/${ARCHIVE_ROOT}"
unzip -q -o /tmp/ca-dashboard-qa.zip -d /tmp
rm -f /tmp/ca-dashboard-qa.zip

echo "==> Sync all files (keep .env)"
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

cp -f /tmp/ca-dashboard-qa.env.backup .env
rm -rf "/tmp/${ARCHIVE_ROOT}"

if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction
fi

php artisan migrate --force
php artisan optimize:clear
php artisan view:clear
rm -f bootstrap/cache/routes-v7.php bootstrap/cache/routes*.php 2>/dev/null || true
php -r "if (function_exists('opcache_reset')) { opcache_reset(); }"

echo "tabs-v2-qa-$(date -u +%Y%m%d-%H%M%S)" > public/dashboard-build.txt 2>/dev/null || true

echo ""
echo "SUCCESS: QA deployed from branch ${BRANCH}."
echo "Open your QA URL (APP_URL in .env), log in, test dashboard tabs, then promote to production."
