#!/bin/bash
# Fix Laravel 404 on /dashboard — clear route cache and sync route files.
set -e
BASE="https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master"

if [ -z "$APP" ]; then
  for candidate in "$HOME/app.kuhu.org.in" "$HOME/public_html/app.kuhu.org.in" "$(pwd)"; do
    if [ -f "$candidate/artisan" ]; then APP="$candidate"; break; fi
  done
fi
cd "$APP"
echo "==> Fix 404 routes: $(pwd)"

curl -fsSL -o routes/web.php "$BASE/routes/web.php"
curl -fsSL -o routes/modules/operations.php "$BASE/routes/modules/operations.php"
curl -fsSL -o public/ping.php "$BASE/public/ping.php"
curl -fsSL -o app/Http/Controllers/DashboardController.php "$BASE/app/Http/Controllers/DashboardController.php"

rm -f bootstrap/cache/routes-v7.php bootstrap/cache/routes*.php 2>/dev/null || true
rm -rf storage/framework/views/* 2>/dev/null || true

php artisan route:clear 2>/dev/null || true
php artisan optimize:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php -r "if (function_exists('opcache_reset')) { opcache_reset(); }"

echo ""
echo "Test these URLs in your browser:"
echo "  1) https://app.kuhu.org.in/ping.php          (static — should NOT be 404)"
echo "  2) https://app.kuhu.org.in/up                (Laravel health)"
echo "  3) https://app.kuhu.org.in/dashboard         (main dashboard — login first)"
echo ""
php artisan route:list --path=dashboard 2>/dev/null | head -20 || echo "(run route:list manually)"
