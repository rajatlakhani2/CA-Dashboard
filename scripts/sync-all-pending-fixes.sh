#!/bin/bash
# Deploy ALL pending user-requested fixes (Jun 2026) from GitHub — run on cPanel after dashboard-only sync.
set -e

BASE="https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master"

if [ -z "$APP" ]; then
  for candidate in "$HOME/app.kuhu.org.in" "$HOME/public_html/app.kuhu.org.in" "$(pwd)"; do
    if [ -f "$candidate/artisan" ]; then APP="$candidate"; break; fi
  done
fi

if [ ! -f "$APP/artisan" ]; then
  echo "ERROR: artisan not found. export APP=\$HOME/app.kuhu.org.in"
  exit 1
fi

cd "$APP"
echo "==> Pending fixes sync: $(pwd)"

mkdir -p scripts app/Http/{Controllers,Middleware} app/Models app/Console/Commands bootstrap \
  resources/views/{dashboard,credentials/partials,clients,personal-renewals,settings,compliance,layouts} routes

FILES=(
  scripts/sync-all-pending-fixes.sh
  scripts/check-saas-deploy.sh
  resources/views/dashboard.blade.php
  resources/views/dashboard/partials/tabs-script.blade.php
  resources/views/dashboard/partials/error-reporter.blade.php
  app/Http/Controllers/DashboardController.php
  routes/modules/operations.php
  routes/web.php
  scripts/force-dashboard-deploy.sh
  resources/views/layouts/app.blade.php
  app/Models/Subscription.php
  app/Models/ClientCredential.php
  app/Http/Middleware/EnforceSessionIdle.php
  bootstrap/app.php
  app/Http/Controllers/SettingsController.php
  app/Http/Controllers/ComplianceController.php
  resources/views/settings/profile.blade.php
  resources/views/credentials/index.blade.php
  resources/views/credentials/partials/vault-audit-script.blade.php
  resources/views/credentials/partials/vault-password-field.blade.php
  resources/views/clients/edit.blade.php
  resources/views/personal-renewals/index.blade.php
  resources/views/compliance/index.blade.php
  app/Console/Commands/RunBackup.php
  routes/console.php
)

for path in "${FILES[@]}"; do
  curl -fsSL -o "$path" "$BASE/$path"
  echo "  ok $path"
done

echo "==> Cache + autoload"
if command -v composer >/dev/null 2>&1; then
  composer dump-autoload --no-interaction 2>/dev/null || composer dump-autoload
fi
echo "tabs-v2-$(date -u +%Y%m%d-%H%M%S)" > public/dashboard-build.txt
rm -rf storage/framework/views/* 2>/dev/null || true
php artisan route:clear
php artisan optimize:clear
php artisan view:clear
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo \"OPcache reset\n\"; }"

if ! grep -q "dashboard-tabs-v2" resources/views/dashboard.blade.php; then
  echo "ERROR: dashboard.blade.php missing tabs-v2 after sync."
  exit 1
fi

echo ""
echo "==> Verify on disk"
bash scripts/check-saas-deploy.sh 2>/dev/null || true

echo ""
echo "SUCCESS: Pending fixes bundle applied."
echo "Check: Dashboard tabs | Passwords | Subscriptions | Personal renewals UI | TDS hidden | Settings security"
