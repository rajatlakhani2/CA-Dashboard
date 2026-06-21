#!/bin/bash
# Reduce MySQL connection pressure on cPanel (error 1040: Too many connections).
# Run on the server:
#   cd ~/public_html/app.kuhu.org.in && bash scripts/fix-production-mysql-connections.sh
set -e

APP_DIR="${APP_DIR:-$HOME/public_html/app.kuhu.org.in}"
cd "$APP_DIR"

echo "==> Fixing MySQL connection settings for cPanel"

if [ ! -f .env ]; then
  echo "ERROR: .env missing"
  exit 1
fi

set_env() {
  local key="$1"
  local value="$2"
  if grep -q "^${key}=" .env; then
    sed -i.bak "s|^${key}=.*|${key}=${value}|" .env
  else
    echo "${key}=${value}" >> .env
  fi
  echo "    ${key}=${value}"
}

# File/sync drivers avoid extra MySQL connections per request (critical on shared hosting).
set_env CACHE_STORE file
set_env QUEUE_CONNECTION sync
set_env SESSION_DRIVER file

# Stop orphaned queue workers if any were started manually.
pkill -f "artisan queue:work" 2>/dev/null && echo "    Stopped artisan queue:work process(es)" || true
pkill -f "artisan queue:listen" 2>/dev/null && echo "    Stopped artisan queue:listen process(es)" || true

chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
mkdir -p storage/framework/sessions storage/framework/cache/data storage/framework/views storage/logs

php artisan optimize:clear
php artisan config:clear
php -r "if (function_exists('opcache_reset')) { opcache_reset(); }"

echo ""
echo "SUCCESS: MySQL connection settings applied."
echo "Wait 30–60 seconds for sleeping DB connections to close, then retry login."
echo "If still failing: cPanel → MySQL → Repair, or ask host to restart MySQL."
