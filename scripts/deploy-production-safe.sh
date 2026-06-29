#!/bin/bash
# Deploy FULL repo from GitHub master to production — replaces partial curl sync scripts.
# Keeps .env and APP_KEY. Does NOT run migrate:fresh.
#
# IMPORTANT: This downloads GitHub's ${BRANCH} zip — NOT your local PC.
# Push to GitHub first:  git push origin master
#
# Usage:
#   cd ~/public_html/app.kuhu.org.in
#   bash scripts/deploy-production-safe.sh
set -e

APP_DIR="${APP_DIR:-$HOME/public_html/app.kuhu.org.in}"
BRANCH="${BRANCH:-master}"
REPO_ZIP="https://github.com/rajatlakhani2/CA-Dashboard/archive/refs/heads/${BRANCH}.zip"
ARCHIVE_ROOT="CA-Dashboard-${BRANCH}"
GITHUB_API="https://api.github.com/repos/rajatlakhani2/CA-Dashboard/commits/${BRANCH}"

read_env() {
  grep -E "^$1=" .env 2>/dev/null | head -1 | cut -d= -f2- | sed 's/^["'\''"]//;s/["'\''"]$//' | tr -d '\r'
}

flush_opcache_via_web() {
  local app_url
  app_url="$(read_env APP_URL)"
  if [ -z "$app_url" ]; then
    echo "WARN: APP_URL not set — skipping web OPcache flush (CLI reset may not affect PHP-FPM)"
    return 0
  fi

  cat > public/.opcache-flush.php <<'PHP'
<?php
header('Content-Type: text/plain; charset=utf-8');
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reset via web\n";
} else {
    echo "OPcache not available\n";
}
PHP

  if curl -fsSL "${app_url%/}/.opcache-flush.php" 2>/dev/null; then
    echo "==> Web OPcache flush OK (${app_url})"
  else
    echo "WARN: Could not reach ${app_url}/.opcache-flush.php — clear LiteSpeed cache in cPanel or visit /clear-app-cache"
  fi
  rm -f public/.opcache-flush.php
}

verify_on_disk() {
  local blade="resources/views/dashboard.blade.php"
  local manifest="public/build/manifest.json"
  local ok=1

  if [ ! -f "$blade" ]; then
    echo "ERROR: $blade missing after deploy"
    ok=0
  elif ! grep -q "dashboard-tab-root" "$blade" 2>/dev/null; then
    echo "WARN: $blade may be an old version (no dashboard-tab-root marker)"
  else
    echo "OK: dashboard blade has tab markers"
  fi

  if [ ! -f "$manifest" ]; then
    echo "ERROR: $manifest missing — run npm run build locally, commit public/build, push, redeploy"
    ok=0
  else
    echo "OK: Vite manifest present ($(grep -o '"file": "[^"]*"' "$manifest" | head -1))"
  fi

  if [ -f public/dashboard-build.txt ]; then
    echo "Deploy stamp: $(cat public/dashboard-build.txt)"
  fi

  return "$ok"
}

cd "$APP_DIR"

if [ ! -f .env ]; then
  echo "ERROR: .env missing in $APP_DIR"
  exit 1
fi

echo "==> Production deploy: $APP_DIR (branch: $BRANCH)"
echo "==> Source: GitHub zip (push local commits to origin/${BRANCH} before deploying)"
if command -v curl >/dev/null 2>&1; then
  GITHUB_SHA="$(curl -fsSL "$GITHUB_API" 2>/dev/null | grep -m1 '"sha"' | sed 's/.*"sha": "\([^"]*\)".*/\1/' || true)"
  if [ -n "$GITHUB_SHA" ]; then
    echo "==> GitHub ${BRANCH} tip: ${GITHUB_SHA:0:7}"
  fi
fi

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
  if npm ci --omit=dev || npm install --omit=dev; then
    npm run build
  else
    echo "WARN: npm install failed — using committed public/build from GitHub zip"
  fi
elif [ ! -f public/build/manifest.json ]; then
  echo "ERROR: public/build/manifest.json missing and npm not available."
  echo "       On your PC: npm run build && git add public/build && git push origin ${BRANCH}"
  exit 1
fi

php artisan migrate --force
php artisan demo:ensure-dashboard --no-interaction 2>/dev/null || true

if [ -f scripts/fix-production-mysql-connections.sh ]; then
  bash scripts/fix-production-mysql-connections.sh
elif [ -f scripts/fix-production-session-419.sh ]; then
  bash scripts/fix-production-session-419.sh
fi

echo "==> Clearing Laravel + compiled view caches"
php artisan optimize:clear
php artisan view:clear
php artisan route:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
rm -rf storage/framework/views/* 2>/dev/null || true
rm -f bootstrap/cache/routes-v7.php bootstrap/cache/routes*.php bootstrap/cache/config.php bootstrap/cache/packages.php bootstrap/cache/services.php 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo \"CLI OPcache reset\n\"; }"

STAMP="deploy-$(date -u +%Y%m%d-%H%M%S)"
echo "$STAMP" > public/dashboard-build.txt
printf '%s\n' "{\"build\":\"${STAMP}\",\"deploy_stamp\":\"$STAMP\",\"github_branch\":\"${BRANCH}\",\"github_sha\":\"${GITHUB_SHA:-unknown}\",\"ok\":true}" > public/build-status.json

flush_opcache_via_web

echo ""
echo "==> Post-deploy verification (on disk)"
verify_on_disk || {
  echo ""
  echo "Deploy finished with warnings — check GitHub push and public/build."
}

echo ""
echo "SUCCESS: Production synced from GitHub ${BRANCH}."
echo "Browser checks:"
echo "  1. https://app.kuhu.org.in/clear-app-cache  (clears PWA/service-worker cache)"
echo "  2. Incognito → https://app.kuhu.org.in/dashboard  (footer Build: should match deploy stamp above)"
echo "  3. https://app.kuhu.org.in/ping.php  (static JSON, no Laravel routes)"
echo "  4. https://app.kuhu.org.in/dashboard/deploy-probe  (partner login, JSON markers)"
