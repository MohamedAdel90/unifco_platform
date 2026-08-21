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

echo "==> Verifying homepage and emergency request files"
test -s resources/views/public/request.blade.php || { echo "ERROR: emergency request form missing"; exit 1; }
test -s database/migrations/2026_08_21_000020_add_emergency_request_details.php || { echo "ERROR: emergency request migration missing"; exit 1; }
grep -q 'home-electrical-20260821-12' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: homepage release marker missing"; exit 1; }
grep -q 'request-map' resources/views/public/request.blade.php || { echo "ERROR: emergency map picker missing"; exit 1; }
grep -q 'equipment_image' resources/views/public/request.blade.php || { echo "ERROR: equipment image field missing"; exit 1; }
grep -q 'responsible_person' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: responsible person validation missing"; exit 1; }

echo "==> Installing dependencies"
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "==> Materializing official UNIFCO logo"
php artisan brand:materialize

echo "==> Applying migrations"
php artisan migrate --force

echo "==> Ensuring public upload storage link"
php artisan storage:link || true

echo "==> Clearing caches"
php artisan optimize:clear

echo "==> Restarting $APP_NAME"
supervisorctl restart "$APP_NAME"

echo "==> Verifying homepage release"
verified=0
for attempt in $(seq 1 20); do
    if curl -fsSI http://127.0.0.1:8081/ | grep -qi 'X-UNIFCO-Release: home-electrical-20260821-12'; then
        verified=1
        break
    fi
    sleep 2
done

if [ "$verified" -ne 1 ]; then
    echo "ERROR: deployed homepage did not expose expected release header"
    exit 1
fi

curl -fsS http://127.0.0.1:8081/emergency-maintenance | grep -q 'موقع العطل على الخريطة' || { echo "ERROR: deployed emergency form missing map section"; exit 1; }
curl -fsS http://127.0.0.1:8081/emergency-maintenance | grep -q 'صورة المعدة' || { echo "ERROR: deployed emergency form missing equipment image"; exit 1; }
curl -fsS http://127.0.0.1:8081/emergency-maintenance | grep -q 'المسؤول عن الطلب' || { echo "ERROR: deployed emergency form missing responsible person"; exit 1; }

echo "==> Emergency maintenance form verified"
echo "==> Deploy complete at $(git rev-parse HEAD)"
