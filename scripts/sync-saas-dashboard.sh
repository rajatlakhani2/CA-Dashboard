#!/bin/bash
# Sync SaaS dashboard + multi-tenant PHP after git pull (or when git did not update).
set -e

APP="${APP:-$HOME/public_html/app.kuhu.org.in}"
BASE="https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master"

if [ ! -f "$APP/artisan" ]; then
  echo "ERROR: artisan not found in $APP"
  exit 1
fi

cd "$APP"
echo "==> Syncing SaaS dashboard from GitHub: $(pwd)"

mkdir -p resources/views/dashboard/partials
mkdir -p app/Services app/Http/Middleware app/Models/Concerns app/Models/Scopes app/Support

for path in \
  resources/views/dashboard.blade.php \
  resources/views/dashboard/partials/workspace-header.blade.php \
  app/Http/Controllers/DashboardController.php \
  app/Services/OrganizationWorkspaceService.php \
  app/Http/Middleware/SetOrganizationContext.php \
  app/Models/Organization.php \
  app/Models/Concerns/BelongsToOrganization.php \
  app/Models/Scopes/OrganizationScope.php \
  app/Support/OrganizationContext.php \
  app/Providers/AppServiceProvider.php \
  routes/web.php
do
  curl -fsSL -o "$path" "$BASE/$path"
  echo "  ok $path"
done

echo "==> Migrate + clear cache"
php artisan migrate --force 2>/dev/null || true
php artisan optimize:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo \"OPcache reset\n\"; }"

if grep -q "Dashboard SaaS v1" resources/views/dashboard/partials/workspace-header.blade.php 2>/dev/null; then
  echo ""
  echo "SUCCESS: SaaS dashboard files on disk."
  echo "Open /dashboard in Incognito + Ctrl+F5 — look for 'SaaS Workspace' banner."
else
  echo "ERROR: workspace-header not updated — push master to GitHub first."
  exit 1
fi
