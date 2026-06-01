#!/bin/bash
# Fix login + credentials vault schema on production (safe — does not wipe DB or rotate APP_KEY).
set -e
cd "${APP_DIR:-$HOME/public_html/app.kuhu.org.in}"

echo "==> Migrate (skip errors if already applied)"
php artisan migrate --force || true

echo "==> Firm login accounts + password reset"
php artisan users:ensure-firm-logins

echo "==> Clear caches"
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo ""
echo "Try: https://app.kuhu.org.in/login"
echo "  Email:    rajat@rlassociates.in"
echo "  Password: password"
echo ""
echo "If Credentials page errors, ensure APP_KEY in .env was NOT changed after saving vault passwords."
