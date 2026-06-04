#!/bin/bash
# Fix "Field password doesn't have a default value" when running users:ensure-firm-logins.
# Run on cPanel from Laravel root (folder containing artisan):
#
#   curl -fsSL -o /tmp/fix-firm-users-seed.sh \
#     "https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master/scripts/fix-firm-users-seed.sh"
#   bash /tmp/fix-firm-users-seed.sh

set -e
BASE="https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master"

APP=""
for dir in "$HOME/public_html/app.kuhu.org.in" "$HOME/app.kuhu.org.in" "$(pwd)"; do
  if [ -f "$dir/artisan" ]; then
    APP="$dir"
    break
  fi
done

if [ -z "$APP" ]; then
  echo "ERROR: artisan not found. cd to Laravel root and run again."
  exit 1
fi

cd "$APP"
echo "Laravel root: $(pwd)"

mkdir -p database/seeders
curl -fsSL -o database/seeders/FirmTeamSeeder.php \
  "$BASE/database/seeders/FirmTeamSeeder.php"
echo "ok FirmTeamSeeder.php"

php artisan users:ensure-firm-logins
php artisan organization:slug --set=rla 2>/dev/null || true
php artisan optimize:clear

echo ""
echo "Done. Login: Workspace rla + rajat@rlassociates.in / password"
