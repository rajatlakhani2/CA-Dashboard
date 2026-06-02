#!/bin/bash
# Install from GitHub — run in cPanel Terminal (no PC zip upload).
set -e

APP="${APP:-$HOME/public_html/app.kuhu.org.in}"
ZIP_URL="https://github.com/rajatlakhani2/CA-Dashboard/archive/refs/heads/master.zip"

echo "==> RLA Dashboard — GitHub install"
echo "    Target: $APP"
mkdir -p "$APP"
cd "$APP"

ENV_BACKUP=""
if [ -f .env ]; then
  ENV_BACKUP="/tmp/ca-dashboard.env.backup"
  cp .env "$ENV_BACKUP"
  echo "==> Backed up existing .env"
fi

echo "==> Download code from GitHub"
curl -fsSL -o /tmp/ca-master.zip "$ZIP_URL"
rm -rf /tmp/CA-Dashboard-master
unzip -q -o /tmp/ca-master.zip -d /tmp
rm -f /tmp/ca-master.zip

echo "==> Copy files"
shopt -s dotglob
for item in /tmp/CA-Dashboard-master/*; do
  name="$(basename "$item")"
  [ "$name" = ".env" ] && continue
  rm -rf "./$name"
  cp -a "$item" "./$name"
done
shopt -u dotglob

if [ -n "$ENV_BACKUP" ] && [ -f "$ENV_BACKUP" ]; then
  cp -f "$ENV_BACKUP" .env
else
  cp -f .env.spidy.example .env
  echo ""
  echo ">>> Created .env — edit MySQL settings before continuing:"
  echo "    cPanel File Manager → app.kuhu.org.in → .env"
  echo "    Set: DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL"
  echo ""
  echo "    Then run:  bash scripts/spidy-install.sh"
  exit 0
fi

install_composer_deps() {
  if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction
    return
  fi
  if [ -f "$HOME/composer.phar" ]; then
    php "$HOME/composer.phar" install --no-dev --optimize-autoloader --no-interaction
    return
  fi
  echo "==> Installing composer.phar locally"
  curl -fsSL https://getcomposer.org/installer | php -- --install-dir="$HOME" --filename=composer.phar
  php "$HOME/composer.phar" install --no-dev --optimize-autoloader --no-interaction
}

echo "==> composer install"
install_composer_deps

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

if [ ! -f public/build/manifest.json ]; then
  echo ""
  echo ">>> UI build missing (not in GitHub)."
  echo "    Upload cpanel-build-upload.zip to public/ and Extract,"
  echo "    OR from PC upload folder public/build only via File Manager."
  echo "    Then run: bash scripts/spidy-install.sh"
  exit 0
fi

bash scripts/spidy-install.sh
