#!/bin/bash
# Pull latest UI files from GitHub when git pull did not update views.
# Run on cPanel:  cd ~/public_html/app.kuhu.org.in && bash scripts/sync-ui-now.sh
set -e

APP="${APP:-$HOME/public_html/app.kuhu.org.in}"
BASE="https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master"

if [ ! -f "$APP/artisan" ]; then
  echo "ERROR: artisan not found in $APP"
  echo "Set APP= to your Laravel folder, e.g.:"
  echo "  APP=\$HOME/public_html/app.kuhu.org.in bash scripts/sync-ui-now.sh"
  exit 1
fi

cd "$APP"
echo "==> Syncing UI from GitHub into: $(pwd)"

mkdir -p resources/views/tasks/partials

curl -fsSL -o resources/views/tasks/create.blade.php \
  "$BASE/resources/views/tasks/create.blade.php"

curl -fsSL -o resources/views/tasks/partials/searchable-picker.blade.php \
  "$BASE/resources/views/tasks/partials/searchable-picker.blade.php"

curl -fsSL -o app/Http/Controllers/TaskController.php \
  "$BASE/app/Http/Controllers/TaskController.php"

curl -fsSL -o resources/views/layouts/app.blade.php \
  "$BASE/resources/views/layouts/app.blade.php"

curl -fsSL -o resources/views/invoices/index.blade.php \
  "$BASE/resources/views/invoices/index.blade.php"

if [ -f artisan ]; then
  echo "==> Clearing Laravel caches"
  php artisan optimize:clear 2>/dev/null || true
  php artisan view:clear 2>/dev/null || true
fi

php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo \"OPcache reset\n\"; }"

if grep -q "Task UI v4" resources/views/tasks/create.blade.php && grep -q "task-form-table" resources/views/tasks/create.blade.php; then
  echo ""
  echo "SUCCESS: Create Task UI v4 (single table) is on disk."
  echo "Open https://app.kuhu.org.in/tasks/create in Incognito + Ctrl+F5"
else
  echo "ERROR: Downloaded file still looks old — check network or GitHub URL."
  exit 1
fi
