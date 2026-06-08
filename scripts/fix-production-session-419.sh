#!/bin/bash
# Fix Laravel 419 "Page Expired" on cPanel HTTPS (app.kuhu.org.in).
# Run on the server after deploy:
#   cd ~/public_html/app.kuhu.org.in && bash scripts/fix-production-session-419.sh
set -e

APP_DIR="${APP_DIR:-$HOME/public_html/app.kuhu.org.in}"
cd "$APP_DIR"

echo "==> Fixing session / CSRF for production (419)"

if [ ! -f .env ]; then
  echo "ERROR: .env missing"
  exit 1
fi

# Ensure HTTPS app URL (session secure cookie depends on this).
if ! grep -q '^APP_URL=https://app.kuhu.org.in' .env; then
  if grep -q '^APP_URL=' .env; then
    sed -i.bak 's|^APP_URL=.*|APP_URL=https://app.kuhu.org.in|' .env
  else
    echo 'APP_URL=https://app.kuhu.org.in' >> .env
  fi
  echo "    Set APP_URL=https://app.kuhu.org.in"
fi

# File sessions on cPanel (database driver needs sessions table + correct DB).
if grep -q '^SESSION_DRIVER=database' .env; then
  sed -i.bak 's|^SESSION_DRIVER=database|SESSION_DRIVER=file|' .env
  echo "    Changed SESSION_DRIVER to file"
fi

# Empty domain — never the literal string "null".
sed -i.bak 's|^SESSION_DOMAIN=null$|SESSION_DOMAIN=|' .env

if ! grep -q '^SESSION_SECURE_COOKIE=' .env; then
  echo 'SESSION_SECURE_COOKIE=true' >> .env
  echo "    Added SESSION_SECURE_COOKIE=true"
fi

if ! grep -q '^SESSION_SAME_SITE=' .env; then
  echo 'SESSION_SAME_SITE=lax' >> .env
fi

# Writable session + cache dirs.
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
mkdir -p storage/framework/sessions storage/framework/cache storage/framework/views storage/logs
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

php artisan optimize:clear
php artisan config:clear
php artisan view:clear
php -r "if (function_exists('opcache_reset')) { opcache_reset(); }"

# Quick write test
php -r "
\$dir = __DIR__ . '/storage/framework/sessions';
\$f = \$dir . '/.write-test-' . getmypid();
if (!is_writable(\$dir)) { fwrite(STDERR, 'ERROR: storage/framework/sessions not writable\n'); exit(1); }
file_put_contents(\$f, 'ok');
unlink(\$f);
echo 'OK: session directory writable\n';
"

echo ""
echo "SUCCESS: Session fix applied."
echo "Test: open https://app.kuhu.org.in/login — sign in should work (no 419)."
echo "If still failing, confirm APP_KEY in .env was NOT regenerated (would invalidate cookies)."
