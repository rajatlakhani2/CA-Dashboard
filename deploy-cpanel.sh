#!/bin/bash
# Run on the server after upload + .env edit (cPanel Terminal).
# Usage: cd /path/to/app && bash deploy-cpanel.sh

set -e
cd "$(dirname "$0")"

if [ ! -f artisan ]; then
  echo "ERROR: Run this from the Laravel root (folder containing artisan)."
  exit 1
fi

if [ ! -f .env ]; then
  echo "ERROR: Create .env first (copy from .env.production.example)."
  exit 1
fi

if [ ! -d public/build ]; then
  echo "WARN: public/build missing — run 'npm run build' on your PC and upload public/build/"
fi

echo "==> composer install"
composer install --no-dev --optimize-autoloader

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null && ! grep -q '^APP_KEY=.' .env | grep -v 'GENERATE'; then
  echo "==> generating APP_KEY"
  php artisan key:generate --force
fi

echo "==> migrate + firm logins"
php artisan migrate --force
php artisan users:ensure-firm-logins

echo "==> cache"
php artisan config:cache
php artisan route:cache
php artisan view:cache

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "==> done. Open https://app.kuhu.org.in/login"
