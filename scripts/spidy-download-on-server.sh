#!/bin/bash
# Install WITHOUT uploading zip from PC — run in cPanel Terminal only.
set -e

APP="$HOME/public_html/app.kuhu.org.in"
ZIP_URL="https://github.com/rajatlakhani2/CA-Dashboard/archive/refs/heads/master.zip"

echo "==> Installing into $APP"
mkdir -p "$APP"
cd "$APP"

if [ -f .env ]; then
  cp .env /tmp/ca-dashboard.env.backup
  echo "Backed up .env"
fi

echo "==> Download from GitHub (~15 MB)"
curl -fsSL -o /tmp/ca-master.zip "$ZIP_URL"
rm -rf /tmp/ca-master
unzip -q -o /tmp/ca-master.zip -d /tmp
rm -f /tmp/ca-master.zip

shopt -s dotglob
for item in /tmp/CA-Dashboard-master/*; do
  name="$(basename "$item")"
  [ "$name" = ".env" ] && continue
  rm -rf "./$name"
  cp -a "$item" "./$name"
done
shopt -u dotglob

if [ -f /tmp/ca-dashboard.env.backup ]; then
  cp -f /tmp/ca-dashboard.env.backup .env
else
  cp -f .env.spidy.example .env
  echo "EDIT .env with MySQL credentials, then run: bash scripts/spidy-install.sh"
  exit 0
fi

echo "==> composer install (required — vendor not in GitHub zip)"
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction
else
  echo "ERROR: Install Composer in cPanel or upload vendor/ via FTP"
  exit 1
fi

echo "==> Build assets missing from Git — download build zip or upload public/build from PC"
if [ ! -f public/build/manifest.json ]; then
  echo "WARN: Upload cpanel-build-upload.zip to public/ and extract build/ folder"
fi

bash scripts/spidy-install.sh
