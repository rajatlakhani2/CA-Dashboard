#!/bin/bash
# Fix missing public/ and misplaced build/ folder on Spidy.
set -e

APP="${APP:-$HOME/public_html/app.kuhu.org.in}"
cd "$APP"

echo "==> Fixing Laravel public folder in $APP"

if [ ! -f artisan ]; then
  echo "ERROR: artisan missing — run GitHub install first (see docs/GITHUB_SPIDY_DEPLOY.md)"
  exit 1
fi

if [ ! -d public ]; then
  echo "==> Download public/ from GitHub"
  curl -fsSL -o /tmp/ca.zip https://github.com/rajatlakhani2/CA-Dashboard/archive/refs/heads/master.zip
  rm -rf /tmp/CA-Dashboard-master
  unzip -q -o /tmp/ca.zip -d /tmp
  cp -a /tmp/CA-Dashboard-master/public .
  rm -f /tmp/ca.zip
  rm -rf /tmp/CA-Dashboard-master
fi

if [ -d build ] && [ ! -d public/build ]; then
  echo "==> Moving build/ into public/build/"
  mkdir -p public/build
  cp -a build/. public/build/
  rm -rf build
fi

if [ ! -f public/index.php ]; then
  echo "ERROR: public/index.php still missing"
  exit 1
fi

if [ ! -f public/build/manifest.json ]; then
  echo "WARN: Upload cpanel-build-upload.zip to public/ and extract as public/build/"
fi

chmod -R 755 public
echo ""
echo "OK. In cPanel set document root to:"
echo "  public_html/app.kuhu.org.in/public"
echo ""
echo "Then open: https://app.kuhu.org.in/login"
