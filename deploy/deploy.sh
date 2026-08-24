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
  database/migrations/2026_08_22_000032_add_work_order_part_requests.php \
  database/migrations/2026_08_22_000033_add_asset_part_consumption_and_returns.php; do
  test -s "$migration" || { echo "ERROR: required migration missing: $migration"; exit 1; }
done
grep -q 'home-electrical-20260821-12' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: homepage release marker missing"; exit 1; }
grep -q 'customer-portal-20260821-5' app/Http/Controllers/CustomerPortalController.php || { echo "ERROR: customer portal v5 release missing"; exit 1; }
grep -q 'CustomerAssetReadController' routes/public.php || { echo "ERROR: customer asset read-only routes missing"; exit 1; }
grep -q 'AssetHealthDashboardController' routes/public.php || { echo "ERROR: portfolio health dashboard routes missing"; exit 1; }
grep -q 'WarehouseFieldInventoryController' routes/web.php || { echo "ERROR: warehouse control routes missing"; exit 1; }
grep -q 'InventoryTransferOrderController' routes/web.php || { echo "ERROR: inventory transfer routes missing"; exit 1; }
grep -q 'WorkOrderPartRequestController' routes/parts.php || { echo "ERROR: work order part request routes missing"; exit 1; }
grep -q 'WorkOrderPartConsumptionController' routes/parts.php || { echo "ERROR: part consumption routes missing"; exit 1; }
grep -q 'parts.php' bootstrap/app.php || { echo "ERROR: part request route file is not registered"; exit 1; }
grep -q 'Consume on Asset' resources/views/maintenance/work-orders/part-requests.blade.php || { echo "ERROR: consume on asset workspace missing"; exit 1; }
grep -q 'Installed Parts & Components' resources/views/eam/assets/part-history.blade.php || { echo "ERROR: internal asset part history missing"; exit 1; }
grep -q 'Installed Parts & Components' resources/views/customer/asset-part-history.blade.php || { echo "ERROR: customer asset part history missing"; exit 1; }
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

echo "==> Clearing cached configuration"
php artisan optimize:clear

echo "==> Waiting for the database"
database_ready=0
for attempt in $(seq 1 20); do
    if php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); Illuminate\Support\Facades\DB::select("SELECT 1");' >/dev/null 2>&1; then
        database_ready=1
        break
    fi
    sleep 2
done
if [ "$database_ready" -ne 1 ]; then
    echo "ERROR: database did not become ready"
    exit 1
fi

database_driver=$(php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo Illuminate\Support\Facades\DB::connection()->getDriverName();')
if [ "$database_driver" != "mysql" ]; then
    echo "ERROR: deployment requires MySQL; configured driver is $database_driver"
    exit 1
fi

echo "==> Repairing customer messaging schema first"
php artisan migrate --force --path=database/migrations/2026_08_21_000023_create_customer_messaging.php

echo "==> Applying all remaining migrations"
php artisan migrate --force

echo "==> Bootstrapping warehouse access"
php artisan unifco:bootstrap-warehouse-access

echo "==> Verifying EAM, warehouse and part lifecycle routes"
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
php artisan route:list --name=inventory.part-requests.consume >/dev/null
php artisan route:list --name=inventory.part-requests.return >/dev/null
php artisan list | grep -q 'unifco:generate-pm-work-orders'
php artisan list | grep -q 'unifco:recalculate-asset-health'
php artisan list | grep -q 'unifco:check-spare-reorder-alerts'
php artisan list | grep -q 'unifco:bootstrap-warehouse-access'

echo "==> Calculating initial asset health intelligence"
php artisan unifco:recalculate-asset-health

echo "==> Synchronizing spare part reorder states"
php artisan unifco:check-spare-reorder-alerts

echo "==> Verifying operational, warehouse and part lifecycle tables"
php -r '
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$required=["customer_conversations","customer_messages","asset_failures","work_order_checklist_results","asset_spare_parts","stock_balances","warehouses","warehouse_bins","inventory_transfer_orders","inventory_transfer_order_lines","warehouse_user_assignments","work_order_part_requests","work_order_part_request_lines","asset_part_installations","work_order_part_returns"];
foreach($required as $table){if(!Illuminate\Support\Facades\Schema::hasTable($table)){fwrite(STDERR,"ERROR: required table missing: $table\n");exit(1);}}
foreach(["preferred_warehouse_code","last_reorder_notified_at","reorder_alert_status"] as $col){if(!Illuminate\Support\Facades\Schema::hasColumn("asset_spare_parts",$col)){fwrite(STDERR,"ERROR: reorder column missing: $col\n");exit(1);}}
foreach(["consumed_quantity","returned_quantity"] as $col){if(!Illuminate\Support\Facades\Schema::hasColumn("work_order_part_request_lines",$col)){fwrite(STDERR,"ERROR: part lifecycle column missing: $col\n");exit(1);}}
$wh=Illuminate\Support\Facades\DB::table("warehouses")->where("code","MAIN-WH")->first();
if(!$wh || $wh->location_type!=="CENTRAL"){fwrite(STDERR,"ERROR: MAIN-WH bootstrap missing\n");exit(1);}
$user=Illuminate\Support\Facades\DB::table("users")->where("email","storekeeper@unifco.local")->first();
if(!$user || $user->role!=="STOREKEEPER"){fwrite(STDERR,"ERROR: storekeeper bootstrap missing\n");exit(1);}
$issuePermission=Illuminate\Support\Facades\DB::table("role_permissions")->where("role_code","STOREKEEPER")->where("permission_code","parts.request.issue")->count();
if($issuePermission<1){fwrite(STDERR,"ERROR: storekeeper part request permission missing\n");exit(1);}
$consumePermission=Illuminate\Support\Facades\DB::table("role_permissions")->where("role_code","TECHNICIAN")->where("permission_code","parts.consume")->count();
if($consumePermission<1){fwrite(STDERR,"ERROR: technician part consume permission missing\n");exit(1);}
echo "Operational warehouse, technician request, consume and return lifecycle ready\n";
'

echo "==> Ensuring public upload storage link"
php artisan storage:link || true

echo "==> Clearing caches after deployment"
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
