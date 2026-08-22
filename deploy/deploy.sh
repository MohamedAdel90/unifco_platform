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
for migration in \
  database/migrations/2026_08_21_000025_build_asset_master_phase_one.php \
  database/migrations/2026_08_21_000026_asset_templates_and_pm_foundation.php \
  database/migrations/2026_08_21_000027_add_asset_spare_parts_compatibility.php \
  database/migrations/2026_08_21_000028_add_work_order_execution_and_reliability.php \
  database/migrations/2026_08_21_000029_add_asset_health_and_replacement_intelligence.php \
  database/migrations/2026_08_21_000030_add_asset_spare_part_reorder_tracking.php \
  database/migrations/2026_08_22_000031_build_warehouse_field_inventory_foundation.php \
  database/migrations/2026_08_22_000032_add_work_order_part_requests.php; do
  test -s "$migration" || { echo "ERROR: required migration missing: $migration"; exit 1; }
done
grep -q 'home-electrical-20260821-12' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: homepage release marker missing"; exit 1; }
grep -q 'customer-portal-20260821-5' app/Http/Controllers/CustomerPortalController.php || { echo "ERROR: customer portal v5 release missing"; exit 1; }
grep -q 'CustomerAssetReadController' routes/public.php || { echo "ERROR: customer asset read-only routes missing"; exit 1; }
grep -q 'AssetHealthDashboardController' routes/public.php || { echo "ERROR: portfolio health dashboard routes missing"; exit 1; }
grep -q 'WarehouseFieldInventoryController' routes/web.php || { echo "ERROR: warehouse control routes missing"; exit 1; }
grep -q 'InventoryTransferOrderController' routes/web.php || { echo "ERROR: inventory transfer routes missing"; exit 1; }
grep -q 'WorkOrderPartRequestController' routes/parts.php || { echo "ERROR: work order part request routes missing"; exit 1; }
grep -q 'parts.php' bootstrap/app.php || { echo "ERROR: part request route file is not registered"; exit 1; }
grep -q 'Technician Part Request' resources/views/maintenance/work-orders/part-requests.blade.php || { echo "ERROR: technician part request workspace missing"; exit 1; }
grep -q 'Technician Part Requests' resources/views/inventory/warehouse/part-requests.blade.php || { echo "ERROR: warehouse request queue missing"; exit 1; }
grep -q 'unifco:recalculate-asset-health' routes/console.php || { echo "ERROR: asset health command missing"; exit 1; }
grep -q 'unifco:check-spare-reorder-alerts' routes/console.php || { echo "ERROR: spare reorder command missing"; exit 1; }

echo "==> Installing dependencies"
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "==> Repairing Laravel session directories and permissions"
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

upsert_env() {
    key="$1"
    value="$2"
    if grep -qE "^${key}=" .env; then
        sed -i "s#^${key}=.*#${key}=${value}#" .env
    else
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

echo "==> Enforcing HTTP-safe session cookie settings"
upsert_env SESSION_DRIVER file
upsert_env SESSION_SECURE_COOKIE false
upsert_env SESSION_SAME_SITE lax
upsert_env SESSION_DOMAIN ""

echo "==> Skipping UNIFCO logo materialization by request"

echo "==> Repairing customer messaging schema first"
php artisan migrate --force --path=database/migrations/2026_08_21_000023_create_customer_messaging.php

echo "==> Applying all remaining migrations"
php artisan migrate --force

echo "==> Bootstrapping warehouse access"
php artisan unifco:bootstrap-warehouse-access

echo "==> Verifying EAM, warehouse and part request routes"
php artisan route:list --name=eam.assets.index >/dev/null
php artisan route:list --name=eam.health.index >/dev/null
php artisan route:list --name=maintenance.work-orders.show >/dev/null
php artisan route:list --name=customer.asset.show >/dev/null
php artisan route:list --name=customer.asset-health >/dev/null
php artisan route:list --name=customer.work-orders.show >/dev/null
php artisan route:list --name=inventory.warehouse.index >/dev/null
php artisan route:list --name=inventory.transfers.store >/dev/null
php artisan route:list --name=inventory.transfers.issue >/dev/null
php artisan route:list --name=inventory.transfers.receive >/dev/null
php artisan route:list --name=maintenance.work-orders.part-requests.store >/dev/null
php artisan route:list --name=inventory.part-requests.approve >/dev/null
php artisan route:list --name=inventory.part-requests.pick >/dev/null
php artisan route:list --name=inventory.part-requests.issue >/dev/null
php artisan route:list --name=inventory.part-requests.receive >/dev/null
php artisan list | grep -q 'unifco:generate-pm-work-orders'
php artisan list | grep -q 'unifco:recalculate-asset-health'
php artisan list | grep -q 'unifco:check-spare-reorder-alerts'
php artisan list | grep -q 'unifco:bootstrap-warehouse-access'

echo "==> Calculating initial asset health intelligence"
php artisan unifco:recalculate-asset-health

echo "==> Synchronizing spare part reorder states"
php artisan unifco:check-spare-reorder-alerts

echo "==> Verifying operational, warehouse and request tables"
php -r '
$db=new PDO("sqlite:".getcwd()."/database/database.sqlite");
$required=["customer_conversations","customer_messages","asset_failures","work_order_checklist_results","asset_spare_parts","stock_balances","warehouses","warehouse_bins","inventory_transfer_orders","inventory_transfer_order_lines","warehouse_user_assignments","work_order_part_requests","work_order_part_request_lines"];
$quoted=implode(",",array_map(fn($x)=>"\"$x\"",$required));
$tables=$db->query("SELECT name FROM sqlite_master WHERE type=\"table\" AND name IN ($quoted)")->fetchAll(PDO::FETCH_COLUMN);
if(count($tables)!==count($required)){fwrite(STDERR,"ERROR: one or more required operational/warehouse/request tables are missing after migrate\n");exit(1);}
$cols=$db->query("PRAGMA table_info(asset_spare_parts)")->fetchAll(PDO::FETCH_ASSOC);
$names=array_column($cols,"name");
foreach(["preferred_warehouse_code","last_reorder_notified_at","reorder_alert_status"] as $col){if(!in_array($col,$names,true)){fwrite(STDERR,"ERROR: reorder column missing: $col\n");exit(1);}}
$wh=$db->query("SELECT code,location_type FROM warehouses WHERE code=\"MAIN-WH\" LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if(!$wh || $wh["location_type"]!=="CENTRAL"){fwrite(STDERR,"ERROR: MAIN-WH bootstrap missing\n");exit(1);}
$user=$db->query("SELECT role FROM users WHERE email=\"storekeeper@unifco.local\" LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if(!$user || $user["role"]!=="STOREKEEPER"){fwrite(STDERR,"ERROR: storekeeper bootstrap missing\n");exit(1);}
$permission=$db->query("SELECT COUNT(*) FROM role_permissions WHERE role_code=\"STOREKEEPER\" AND permission_code=\"parts.request.issue\"")->fetchColumn();
if((int)$permission<1){fwrite(STDERR,"ERROR: storekeeper part request permission missing\n");exit(1);}
echo "Operational, warehouse and technician request foundation ready\n";
'

echo "==> Ensuring public upload storage link"
php artisan storage:link || true

echo "==> Clearing caches after environment repair"
php artisan optimize:clear

echo "==> Restarting $APP_NAME"
supervisorctl restart "$APP_NAME"

echo "==> Waiting for application"
ready=0
for attempt in $(seq 1 20); do
    if curl -fsSI http://127.0.0.1:8081/login >/dev/null; then
        ready=1
        break
    fi
    sleep 2
done
if [ "$ready" -ne 1 ]; then
    echo "ERROR: application did not become ready"
    exit 1
fi

echo "==> Verifying homepage release"
if ! curl -fsSI http://127.0.0.1:8081/ | grep -qi 'X-UNIFCO-Release: home-electrical-20260821-12'; then
    echo "ERROR: deployed homepage did not expose expected release header"
    exit 1
fi

echo "==> Verifying login session and CSRF round trip"
rm -f /tmp/unifco-login.cookies /tmp/unifco-login.html /tmp/unifco-login-post.headers
curl -fsS -c /tmp/unifco-login.cookies http://127.0.0.1:8081/login -o /tmp/unifco-login.html
csrf_token="$(grep -o 'name="_token" value="[^"]*"' /tmp/unifco-login.html | head -n1 | sed 's/^.*value="//;s/"$//')"
if [ -z "$csrf_token" ]; then
    echo "ERROR: could not extract CSRF token from login form"
    exit 1
fi
login_status="$(curl -sS -o /dev/null -D /tmp/unifco-login-post.headers -w '%{http_code}' -b /tmp/unifco-login.cookies -c /tmp/unifco-login.cookies -X POST http://127.0.0.1:8081/login \
    --data-urlencode "_token=${csrf_token}" \
    --data-urlencode 'email=csrf-check@unifco.invalid' \
    --data-urlencode 'password=invalid-password')"
if [ "$login_status" = "419" ]; then
    echo "ERROR: login CSRF/session validation still returns 419"
    exit 1
fi
if [ "$login_status" != "302" ]; then
    echo "ERROR: unexpected login validation status: $login_status"
    exit 1
fi
echo "Login CSRF/session round trip verified: HTTP ${login_status}"

echo "==> Deploy complete at $(git rev-parse HEAD)"
