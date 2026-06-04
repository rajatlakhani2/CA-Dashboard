#!/bin/bash
# Run on server: cd ~/public_html/app.kuhu.org.in && bash scripts/check-saas-deploy.sh
set -e
APP="${APP:-$(pwd)}"
cd "$APP"

echo "=== SaaS deploy check ==="
echo "Path: $(pwd)"
echo ""

echo "--- Git (last commit) ---"
git log -1 --oneline 2>/dev/null || echo "(not a git repo)"

echo ""
echo "--- Migration file on disk ---"
ls -la database/migrations/*organizations* 2>/dev/null || echo "MISSING: organizations migration file — run: git pull origin master"

echo ""
echo "--- Migration status ---"
php artisan migrate:status 2>/dev/null | grep -i organization || echo "(no organization migration in status — file missing or artisan failed)"

echo ""
echo "--- Database tables ---"
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
echo 'organizations table: ' . (Schema::hasTable('organizations') ? 'YES' : 'NO') . PHP_EOL;
echo 'users.organization_id: ' . (Schema::hasColumn('users', 'organization_id') ? 'YES' : 'NO') . PHP_EOL;
if (Schema::hasTable('organizations')) {
    echo 'organizations count: ' . \App\Models\Organization::count() . PHP_EOL;
}
if (Schema::hasColumn('users', 'organization_id')) {
    echo 'users with org_id: ' . \App\Models\User::whereNotNull('organization_id')->count() . PHP_EOL;
}
" 2>/dev/null || echo "tinker check failed"

echo ""
echo "--- Dashboard view on disk ---"
if grep -q "dashboard-tabs-v2" resources/views/dashboard.blade.php 2>/dev/null; then
  echo "dashboard.blade.php: OK (tabs v2 + deploy marker)"
else
  echo "dashboard.blade.php: OLD — run: bash scripts/sync-saas-full.sh"
fi

if grep -q "workspace-header" resources/views/dashboard.blade.php 2>/dev/null; then
  echo "workspace-header strip: STILL INCLUDED (old dashboard) — re-sync dashboard.blade.php"
else
  echo "workspace-header strip: removed from dashboard OK"
fi

echo ""
echo "=== Done ==="
echo "If dashboard is OLD, run: bash scripts/sync-saas-full.sh && php artisan view:clear"
