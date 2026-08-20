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

echo "==> Materializing sharpened UNIFCO hero v10"
mkdir -p public/images
cat resources/hero/v10.part0 \
    resources/hero/v10.part1 \
    resources/hero/v10.part2 \
    resources/hero/v10.part3 \
    resources/hero/v10.part4 \
  | tr -d '\n\r ' \
  | base64 -d > public/images/unifco-hero-approved-v10.jpg

test -s public/images/unifco-hero-approved-v10.jpg || { echo "ERROR: hero v10 image was not materialized"; exit 1; }
hero_size="$(stat -c '%s' public/images/unifco-hero-approved-v10.jpg)"
if [ "$hero_size" -lt 50000 ]; then
    echo "ERROR: hero v10 image is unexpectedly small: ${hero_size} bytes"
    exit 1
fi

# Apply the v10 asset and cache-busting release marker to the deployed checkout.
sed -i 's#unifco-hero-workers-v7.jpg?v=20260821-9#unifco-hero-approved-v10.jpg?v=20260821-10#g' app/Http/Controllers/PublicSiteController.php
sed -i 's#hero-20260821-v9#hero-20260821-v10#g' app/Http/Controllers/PublicSiteController.php
sed -i 's#hero-20260821-9#hero-20260821-10#g' app/Http/Controllers/PublicSiteController.php

grep -q 'unifco-hero-approved-v10.jpg' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: homepage does not reference hero v10"; exit 1; }
grep -q 'hero-20260821-v10' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: homepage release does not contain hero v10"; exit 1; }

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
    if curl -fsSI http://127.0.0.1:8081/ | grep -qi 'X-UNIFCO-Release: hero-20260821-10'; then
        verified=1
        break
    fi
    sleep 2
done

if [ "$verified" -ne 1 ]; then
    echo "ERROR: deployed homepage did not expose expected X-UNIFCO-Release header"
    exit 1
fi

echo "==> Hero v10 asset present: ${hero_size} bytes"
echo "==> Deploy complete at $(git rev-parse HEAD)"
