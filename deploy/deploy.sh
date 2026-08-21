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

echo "==> Verifying electrical homepage assets"
for asset in \
  public/images/home/hero-electrical.svg \
  public/images/home/facility-power.svg \
  public/images/home/generator.svg \
  public/images/home/ats.svg \
  public/images/home/transformer.svg \
  public/images/home/ups.svg \
  public/images/home/mv-switchgear.svg \
  public/images/home/sector-commercial.svg \
  public/images/home/sector-industrial.svg \
  public/images/home/sector-healthcare.svg \
  public/images/home/sector-education.svg \
  public/images/home/sector-government.svg \
  public/images/home/sector-warehouse.svg \
  public/images/home/sector-datacenter.svg; do
  test -s "$asset" || { echo "ERROR: missing homepage asset $asset"; exit 1; }
done

grep -q 'home-electrical-20260821-11' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: homepage release marker missing"; exit 1; }
grep -q '/images/home/hero-electrical.svg' resources/views/public/home.blade.php || { echo "ERROR: electrical hero is not referenced"; exit 1; }
if grep -q 'الضيافة' resources/views/public/home.blade.php; then
  echo "ERROR: hospitality sector is still present"
  exit 1
fi

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
    if curl -fsSI http://127.0.0.1:8081/ | grep -qi 'X-UNIFCO-Release: home-electrical-20260821-11'; then
        verified=1
        break
    fi
    sleep 2
done

if [ "$verified" -ne 1 ]; then
    echo "ERROR: deployed homepage did not expose expected release header"
    exit 1
fi

curl -fsS http://127.0.0.1:8081/ | grep -q '/images/home/generator.svg' || { echo "ERROR: deployed homepage missing electrical service imagery"; exit 1; }

echo "==> Electrical homepage assets verified"
echo "==> Deploy complete at $(git rev-parse HEAD)"
