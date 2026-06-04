#!/bin/bash
# Find Laravel root on cPanel and deploy latest dashboard calendar.
# Paste in Terminal (one block):

set -e
BASE="https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master"

echo "=== Looking for artisan (Laravel root) ==="
FOUND=""
for dir in \
  "$HOME/app.kuhu.org.in" \
  "$HOME/public_html/app.kuhu.org.in" \
  "$HOME/public_html" \
  "$HOME/domains/app.kuhu.org.in/public_html" \
  "$HOME/domains/kuhu.org.in/public_html/app.kuhu.org.in"
do
  if [ -f "$dir/artisan" ]; then
    FOUND="$dir"
    break
  fi
done

if [ -z "$FOUND" ]; then
  echo "ERROR: artisan not found. Search manually:"
  echo "  find \$HOME -name artisan -type f 2>/dev/null | head -5"
  exit 1
fi

cd "$FOUND"
echo "Laravel root: $(pwd)"
echo ""

mkdir -p resources/views/dashboard/partials scripts

echo "=== Download dashboard (calendar grid) ==="
curl -fsSL -o resources/views/dashboard.blade.php \
  "$BASE/resources/views/dashboard.blade.php"
echo "  ok dashboard.blade.php"

curl -fsSL -o /tmp/sync-saas-full.sh "$BASE/scripts/sync-saas-full.sh"
bash /tmp/sync-saas-full.sh

echo ""
echo "=== Done ==="
echo "Open Dashboard -> Schedule tab (Incognito + Ctrl+F5)"
