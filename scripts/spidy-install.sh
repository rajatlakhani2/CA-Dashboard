#!/bin/bash
# Run once after uploading the app to Spidy/cPanel (from app root).
set -e

cd "$(dirname "$0")/.."
if [ ! -f artisan ]; then
  echo "ERROR: Run from project root (folder containing artisan)."
  exit 1
fi

echo "==> Spidy install: $(pwd)"

if [ ! -f .env ]; then
  if [ -f .env.spidy.example ]; then
    cp .env.spidy.example .env
    echo "Created .env from .env.spidy.example — EDIT DB_* before continuing."
    exit 1
  fi
  echo "ERROR: No .env file. Copy .env.spidy.example to .env and set MySQL credentials."
  exit 1
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  echo "==> Generating APP_KEY"
  php artisan key:generate --force
else
  echo "==> Keeping existing APP_KEY"
fi

if command -v composer >/dev/null 2>&1; then
  echo "==> composer install"
  composer install --no-dev --optimize-autoloader --no-interaction
else
  echo "WARN: composer not in PATH — upload vendor/ or install Composer in cPanel"
fi

if [ ! -f public/build/manifest.json ]; then
  echo "WARN: public/build/manifest.json missing — run npm run build on PC and re-upload public/build"
fi

echo "==> Migrate + firm logins + cache"
php artisan app:production-bootstrap

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "Done. Open: https://app.kuhu.org.in/login"
echo "  rajat@rlassociates.in / password"
echo "Clear old PWA cache: https://app.kuhu.org.in/clear-app-cache"
