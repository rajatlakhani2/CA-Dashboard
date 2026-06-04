#!/bin/bash
# Turn cPanel app folder into a git clone of GitHub (first-time setup).
# Usage (cPanel Terminal):
#   curl -fsSL -o /tmp/setup-git.sh \
#     https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master/scripts/setup-git-clone-on-cpanel.sh
#   bash /tmp/setup-git.sh
#
# Optional: export APP=$HOME/app.kuhu.org.in
# Optional: export CONFIRM=1   (skip "type YES" prompt)

set -e

REPO_URL="https://github.com/rajatlakhani2/CA-Dashboard.git"
REPO_SSH="git@github.com:rajatlakhani2/CA-Dashboard.git"

if [ -z "$APP" ]; then
  for candidate in \
    "$HOME/app.kuhu.org.in" \
    "$HOME/public_html/app.kuhu.org.in"
  do
    if [ -f "$candidate/artisan" ]; then
      APP="$candidate"
      break
    fi
  done
fi

if [ -z "$APP" ] || [ ! -f "$APP/artisan" ]; then
  echo "ERROR: Laravel app not found. Set APP= to folder containing artisan"
  exit 1
fi

APP="$(cd "$APP" && pwd)"
PARENT="$(dirname "$APP")"
BASENAME="$(basename "$APP")"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="${PARENT}/${BASENAME}.backup-${STAMP}"
CLONE_TMP="${PARENT}/${BASENAME}.git-clone-${STAMP}"

echo "GitHub repo: $REPO_URL"
echo "App folder:  $APP"
echo "Backup will be: $BACKUP"
echo ""

if [ -d "$APP/.git" ]; then
  echo "This folder is already a git repository."
  cd "$APP"
  git remote -v || true
  echo ""
  echo "Pull latest:"
  echo "  cd $APP && git pull origin master"
  exit 0
fi

if [ "${CONFIRM}" != "1" ]; then
  echo "This will:"
  echo "  1) Backup $APP to $BACKUP"
  echo "  2) Clone fresh code from GitHub"
  echo "  3) Restore your .env and storage/ from backup"
  echo ""
  read -r -p "Type YES to continue: " answer
  if [ "$answer" != "YES" ]; then
    echo "Cancelled."
    exit 1
  fi
fi

echo "==> Backup current site"
cp -a "$APP" "$BACKUP"
echo "    Backup done: $BACKUP"

echo "==> Clone from GitHub"
rm -rf "$CLONE_TMP"
git clone "$REPO_URL" "$CLONE_TMP"
cd "$CLONE_TMP"
git checkout master 2>/dev/null || git checkout main 2>/dev/null || true

echo "==> Replace app files (keep your data)"
rsync -a --delete \
  --exclude='.env' \
  --exclude='storage/' \
  --exclude='vendor/' \
  --exclude='node_modules/' \
  "$CLONE_TMP/" "$APP/"

cp -a "$BACKUP/.env" "$APP/.env" 2>/dev/null || echo "WARN: no .env in backup — copy manually"
if [ -d "$BACKUP/storage" ]; then
  rsync -a "$BACKUP/storage/" "$APP/storage/"
fi

echo "==> Attach git metadata to $APP"
rm -rf "$APP/.git"
mv "$CLONE_TMP/.git" "$APP/.git"
rm -rf "$CLONE_TMP"

cd "$APP"
git remote -v
git status -sb | head -5

echo ""
echo "==> Composer + Laravel (if composer exists)"
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader 2>/dev/null || composer install --no-dev || true
fi
php artisan migrate --force 2>/dev/null || true
php artisan optimize:clear 2>/dev/null || true

echo ""
echo "SUCCESS: $APP is now a git clone of $REPO_URL"
echo ""
echo "Next deploys:"
echo "  cd $APP"
echo "  git pull origin master"
echo "  composer install --no-dev"
echo "  php artisan migrate --force"
echo "  php artisan optimize:clear"
echo ""
echo "Backup kept at: $BACKUP"
