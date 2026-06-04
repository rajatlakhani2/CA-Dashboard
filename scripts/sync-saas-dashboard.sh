#!/bin/bash
# Deploy SaaS dashboard from GitHub (no git required — for cPanel FTP uploads).
set -e

BASE="https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master"

if [ -z "$APP" ]; then
  for candidate in \
    "$HOME/public_html/app.kuhu.org.in" \
    "$HOME/app.kuhu.org.in" \
    "$(pwd)"
  do
    if [ -f "$candidate/artisan" ]; then
      APP="$candidate"
      break
    fi
  done
fi

if [ ! -f "$APP/artisan" ]; then
  echo "ERROR: artisan not found."
  echo "Set your Laravel folder, e.g.:"
  echo "  export APP=\$HOME/app.kuhu.org.in"
  echo "  bash scripts/sync-saas-dashboard.sh"
  exit 1
fi

cd "$APP"
echo "==> SaaS sync into: $(pwd)"
echo "    (git not required)"

mkdir -p scripts \
  resources/views/dashboard/partials \
  app/Services app/Http/Middleware app/Http/Controllers app/Http/Requests \
  app/Models/Concerns app/Models/Scopes app/Support app/Providers \
  database/migrations database/seeders

FILES=(
  resources/views/dashboard.blade.php
  resources/views/dashboard/partials/workspace-header.blade.php
  resources/views/system/index.blade.php
  app/Http/Controllers/DashboardController.php
  app/Http/Controllers/StaffController.php
  app/Http/Controllers/SystemController.php
  app/Http/Middleware/SetOrganizationContext.php
  app/Http/Requests/StoreStaffRequest.php
  app/Services/OrganizationWorkspaceService.php
  app/Support/OrganizationContext.php
  app/Models/Organization.php
  app/Models/Concerns/BelongsToOrganization.php
  app/Models/Scopes/OrganizationScope.php
  app/Models/User.php
  app/Models/Client.php
  app/Models/Task.php
  app/Models/Invoice.php
  app/Models/Branch.php
  app/Models/ServiceDue.php
  app/Models/Payment.php
  app/Models/Setting.php
  app/Models/FirmAlert.php
  app/Providers/AppServiceProvider.php
  routes/web.php
  database/migrations/2026_06_03_120000_create_organizations_multi_tenant.php
  database/seeders/FirmTeamSeeder.php
)

for path in "${FILES[@]}"; do
  curl -fsSL -o "$path" "$BASE/$path"
  echo "  ok $path"
done

echo "==> Migrate + cache"
php artisan migrate --force 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan optimize:clear 2>/dev/null || true
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo \"OPcache reset\n\"; }"

if grep -q "Dashboard SaaS v1" resources/views/dashboard/partials/workspace-header.blade.php 2>/dev/null; then
  echo ""
  echo "SUCCESS: SaaS dashboard is on disk."
  echo "Open https://app.kuhu.org.in/dashboard in Incognito + Ctrl+F5"
else
  echo "ERROR: workspace-header missing SaaS marker."
  exit 1
fi
