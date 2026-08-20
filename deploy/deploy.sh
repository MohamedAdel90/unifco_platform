#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/unifco_platform"
APP_NAME="unifco-app"

cd "$APP_DIR"

echo "==> Fetching latest main from origin"
git fetch origin main

echo "==> Checking out origin/main"
git reset --hard origin/main

echo "==> Server checkout: $(git rev-parse HEAD)"

test -f public/images/unifco-facility-hero.jpg || { echo "ERROR: approved hero image is missing from deployed checkout"; exit 1; }
grep -q 'unifco-facility-hero.jpg' resources/views/public/home.blade.php || { echo "ERROR: homepage does not reference approved hero image"; exit 1; }

echo "==> Installing dependencies"
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "==> Materializing official UNIFCO logo"
php artisan brand:materialize

echo "==> Applying migrations"
php artisan migrate --force

echo "==> Clearing caches"
php artisan optimize:clear

echo "==> Restarting $APP_NAME"
supervisorctl restart "$APP_NAME"

echo "==> Verifying homepage release"
verified=0
for attempt in $(seq 1 20); do
    if curl -fsSI http://127.0.0.1:8081/ | grep -qi 'X-UNIFCO-Release: hero-20260821-4'; then
        verified=1
        break
    fi
    sleep 2
done

if [ "$verified" -ne 1 ]; then
    echo "ERROR: deployed homepage did not expose expected X-UNIFCO-Release header"
    exit 1
fi

echo "==> Hero asset present: $(stat -c '%s bytes' public/images/unifco-facility-hero.jpg)"
echo "==> Deploy complete at $(git rev-parse HEAD)"
