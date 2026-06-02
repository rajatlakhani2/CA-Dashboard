#!/bin/bash
# Install from GitHub — run in cPanel Terminal (no PC zip upload).
set -e

APP="${APP:-$HOME/public_html/app.kuhu.org.in}"
ZIP_URL="https://github.com/rajatlakhani2/CA-Dashboard/archive/refs/heads/master.zip"

fix_script_line_endings() {
  if [ -d scripts ]; then
    sed -i 's/\r$//' scripts/*.sh 2>/dev/null || true
    chmod +x scripts/*.sh 2>/dev/null || true
  fi
}

echo "==> RLA Dashboard — GitHub install"
echo "    Target: $APP"

ENV_BACKUP=""
if [ -f "$APP/.env" ]; then
  ENV_BACKUP="/tmp/ca-dashboard.env.backup"
  cp "$APP/.env" "$ENV_BACKUP"
  echo "==> Backed up existing .env"
fi

echo "==> Download code from GitHub"
curl -fsSL -o /tmp/ca-master.zip "$ZIP_URL"
rm -rf /tmp/CA-Dashboard-master
unzip -q -o /tmp/ca-master.zip -d /tmp
rm -f /tmp/ca-master.zip

STAGE="/tmp/CA-Dashboard-master"

# Fresh folder avoids "Permission denied" on old files
if [ -d "$APP" ] && [ -n "$(ls -A "$APP" 2>/dev/null)" ]; then
  echo "==> Moving old folder to app.kuhu.org.in.old (no data loss in .old)"
  parent="$(dirname "$APP")"
  base="$(basename "$APP")"
  cd "$parent"
  rm -rf "${base}.old"
  mv "$base" "${base}.old"
fi

mkdir -p "$APP"
cd "$APP"

echo "==> Copy files into empty folder"
cp -a "$STAGE"/. .
fix_script_line_endings

if [ -n "$ENV_BACKUP" ] && [ -f "$ENV_BACKUP" ]; then
  cp -f "$ENV_BACKUP" .env
else
  cp -f .env.spidy.example .env
  echo ""
  echo ">>> Created .env — edit MySQL in File Manager, then run:"
  echo "    cd ~/public_html/app.kuhu.org.in"
  echo "    bash scripts/spidy-install.sh"
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
  echo "==> Installing composer.phar"
  curl -fsSL https://getcomposer.org/installer | php -- --install-dir="$HOME" --filename=composer.phar
  php "$HOME/composer.phar" install --no-dev --optimize-autoloader --no-interaction
}

echo "==> composer install"
install_composer_deps

chmod -R 775 storage bootstrap/cache 2>/dev/null || true
fix_script_line_endings

if [ ! -f public/build/manifest.json ]; then
  echo ""
  echo ">>> Upload cpanel-build-upload.zip to public/ and Extract (build folder)."
  echo "    Then: bash scripts/spidy-install.sh"
  exit 0
fi

bash scripts/spidy-install.sh
