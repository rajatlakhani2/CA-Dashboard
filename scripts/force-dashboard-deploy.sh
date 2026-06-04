#!/bin/bash
# Emergency: deploy ONLY dashboard + clear ALL view cache. Run on cPanel when live still looks old.
set -e
BASE="https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master"

if [ -z "$APP" ]; then
  for candidate in "$HOME/app.kuhu.org.in" "$HOME/public_html/app.kuhu.org.in" "$(pwd)"; do
    if [ -f "$candidate/artisan" ]; then APP="$candidate"; break; fi
  done
fi
cd "$APP"
echo "==> Force dashboard deploy: $(pwd)"

mkdir -p resources/views/dashboard/partials scripts public

curl -fsSL -o resources/views/dashboard.blade.php "$BASE/resources/views/dashboard.blade.php"
curl -fsSL -o resources/views/dashboard/partials/tabs-script.blade.php "$BASE/resources/views/dashboard/partials/tabs-script.blade.php"
curl -fsSL -o app/Http/Controllers/DashboardController.php "$BASE/app/Http/Controllers/DashboardController.php"
curl -fsSL -o routes/modules/operations.php "$BASE/routes/modules/operations.php"

if ! grep -q "dashboard-tabs-v2" resources/views/dashboard.blade.php; then
  echo "ERROR: Downloaded dashboard.blade.php is still OLD (no dashboard-tabs-v2 marker)."
  exit 1
fi

if grep -q "workspace-header" resources/views/dashboard.blade.php; then
  echo "ERROR: Downloaded dashboard still includes workspace-header."
  exit 1
fi

echo "tabs-v2-$(date -u +%Y%m%d-%H%M%S)" > public/dashboard-build.txt

rm -rf storage/framework/views/* 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan optimize:clear 2>/dev/null || true
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo \"OPcache reset\n\"; }"

echo "SUCCESS: Dashboard tabs-v2 on disk."
echo "Verify in browser: open /dashboard — footer should say Build: tabs-v2-20260604"
echo "Or JSON: /dashboard/deploy-probe (while logged in as partner)"
