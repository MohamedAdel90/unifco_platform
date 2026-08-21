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

echo "==> Verifying required release files"
test -s resources/views/public/request.blade.php || { echo "ERROR: emergency request form missing"; exit 1; }
test -s database/migrations/2026_08_21_000020_add_emergency_request_details.php || { echo "ERROR: emergency request migration missing"; exit 1; }
test -s database/migrations/2026_08_21_000023_create_customer_messaging.php || { echo "ERROR: customer messaging migration missing"; exit 1; }
grep -q 'home-electrical-20260821-12' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: homepage release marker missing"; exit 1; }
grep -q 'customer-portal-20260821-4' app/Http/Controllers/CustomerPortalController.php || { echo "ERROR: customer portal v4 release missing"; exit 1; }
grep -q 'customer-logo-plain' resources/views/customer/section.blade.php || { echo "ERROR: plain customer logo layout missing"; exit 1; }
grep -q 'customer.inbox' resources/views/customer/section.blade.php || { echo "ERROR: inbox link missing from customer layout"; exit 1; }

echo "==> Installing dependencies"
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "==> Materializing official UNIFCO logo"
php artisan brand:materialize

echo "==> Repairing customer messaging schema first"
php artisan migrate --force --path=database/migrations/2026_08_21_000023_create_customer_messaging.php

echo "==> Applying all remaining migrations"
php artisan migrate --force

echo "==> Verifying customer messaging tables"
php -r '
$db=new PDO("sqlite:".getcwd()."/database/database.sqlite");
$tables=$db->query("SELECT name FROM sqlite_master WHERE type=\"table\" AND name IN (\"customer_conversations\",\"customer_messages\")")->fetchAll(PDO::FETCH_COLUMN);
if(count($tables)!==2){fwrite(STDERR,"ERROR: customer messaging tables are missing after migrate\n");exit(1);} echo "Customer messaging tables ready\n";
'

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

echo "==> Verifying application is responding"
curl -fsSI http://127.0.0.1:8081/login >/dev/null

echo "==> Deploy complete at $(git rev-parse HEAD)"
