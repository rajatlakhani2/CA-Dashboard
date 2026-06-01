#!/bin/bash
# One-time production fix: latest migrations + migrate + login accounts.
# Safe: does NOT run migrate:fresh or key:generate.
set -e

APP_DIR="${APP_DIR:-$HOME/public_html/app.kuhu.org.in}"
ZIP="https://github.com/rajatlakhani2/CA-Dashboard/archive/refs/heads/master.zip"

cd "$APP_DIR"

echo "==> Download latest code (migrations + commands)"
curl -fsSL -o /tmp/ca-dash.zip "$ZIP"
rm -rf /tmp/ca-dash-master
unzip -q -o /tmp/ca-dash.zip -d /tmp
rm -f /tmp/ca-dash.zip

cp -f /tmp/CA-Dashboard-master/database/migrations/*.php database/migrations/
cp -f /tmp/CA-Dashboard-master/app/Support/SafeSchema.php app/Support/SafeSchema.php 2>/dev/null || mkdir -p app/Support && cp -f /tmp/CA-Dashboard-master/app/Support/SafeSchema.php app/Support/
cp -f /tmp/CA-Dashboard-master/app/Console/Commands/EnsureFirmLoginUsers.php app/Console/Commands/
cp -f /tmp/CA-Dashboard-master/app/Console/Commands/ProductionBootstrap.php app/Console/Commands/
cp -f /tmp/CA-Dashboard-master/app/Console/Commands/ResetUserPassword.php app/Console/Commands/ 2>/dev/null || true
cp -f /tmp/CA-Dashboard-master/database/seeders/FirmTeamSeeder.php database/seeders/
cp -f /tmp/CA-Dashboard-master/app/Services/DashboardMetricsService.php app/Services/
cp -rf /tmp/CA-Dashboard-master/resources/views/partials/head-assets.blade.php resources/views/partials/ 2>/dev/null || true
cp -f /tmp/CA-Dashboard-master/resources/views/layouts/app.blade.php resources/views/layouts/
cp -f /tmp/CA-Dashboard-master/resources/views/auth/login.blade.php resources/views/auth/
if [ -d /tmp/CA-Dashboard-master/public/build ]; then
  rm -rf public/build
  cp -r /tmp/CA-Dashboard-master/public/build public/build
  echo "==> Installed public/build assets"
fi
rm -rf /tmp/CA-Dashboard-master

if [ ! -f public/build/manifest.json ]; then
  echo "WARN: public/build missing — pages use CSS fallback. For full UI, upload public/build from your PC (npm run build)."
fi

echo "TIP: In .env use SESSION_DRIVER=file (recommended on cPanel)."

echo "==> Bootstrap app"
php artisan app:production-bootstrap

echo ""
echo "Done. Open https://app.kuhu.org.in/login"
