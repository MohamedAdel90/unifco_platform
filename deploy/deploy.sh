#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/unifco_platform"
APP_NAME="unifco-app"
EXPECTED_SHA="${1:-}"

if [[ ! "$EXPECTED_SHA" =~ ^[0-9a-f]{40}$ ]]; then
  echo "ERROR: deploy requires an explicit 40-character qualified SHA" >&2
  exit 1
fi

cd "$APP_DIR"

echo "==> Fetching main and checking out qualified SHA: $EXPECTED_SHA"
git fetch origin main
if ! git cat-file -e "${EXPECTED_SHA}^{commit}" 2>/dev/null; then
  echo "ERROR: qualified SHA is not available after fetching main: $EXPECTED_SHA" >&2
  exit 1
fi
REMOTE_MAIN_SHA="$(git rev-parse origin/main)"
if [[ "$EXPECTED_SHA" != "$REMOTE_MAIN_SHA" ]]; then
  echo "ERROR: refusing stale release: qualified SHA $EXPECTED_SHA is not current main $REMOTE_MAIN_SHA" >&2
  exit 1
fi
git reset --hard "$EXPECTED_SHA"
DEPLOY_SHA="$(git rev-parse HEAD)"
[[ "$DEPLOY_SHA" == "$EXPECTED_SHA" ]] || { echo "ERROR: server checkout mismatch: expected $EXPECTED_SHA got $DEPLOY_SHA" >&2; exit 1; }
echo "==> Server checkout: $DEPLOY_SHA"

echo "==> Validating current release foundation"
for file in \
  resources/views/public/request.blade.php \
  resources/views/workflow/workspace.blade.php \
  resources/views/workflow/customer-actions.blade.php \
  resources/views/customer/users-access.blade.php \
  resources/views/customer/action-center.blade.php \
  resources/views/crm/acquisition.blade.php \
  resources/views/maintenance/asset-master/index.blade.php \
  resources/views/maintenance/asset-master/show.blade.php \
  routes/public.php routes/customer-phase2.php routes/customer-acquisition.php routes/asset-master.php routes/public-asset-qr.php routes/parts.php \
  database/migrations/2026_08_27_000058_build_professional_asset_master.php \
  database/migrations/2026_08_27_000059_normalize_user_status_semantics.php \
  database/migrations/2026_08_27_000060_build_asset_lifecycle_commissioning.php \
  database/migrations/2026_08_27_000061_normalize_asset_documents_for_professional_master.php \
  database/seeders/WorkflowTestUsersSeeder.php \
  app/Http/Controllers/Maintenance/AssetMasterController.php \
  app/Models/Asset.php app/Models/AssetCategoryTemplate.php app/Models/AssetDocument.php \
  app/Models/AssetLocation.php app/Models/AssetLifecycleEvent.php app/Models/AssetCommissioningRecord.php \
  app/Services/AssetMasterService.php app/Services/CustomerAcquisitionService.php app/Services/CustomerPortalAccessService.php; do
  test -s "$file" || { echo "ERROR: required release file missing: $file"; exit 1; }
done

grep -q 'home-electrical-20260821-12' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: homepage release marker missing"; exit 1; }
grep -q 'customer-portal-rbac-phase1-20260827' app/Http/Controllers/CustomerPortalController.php || { echo "ERROR: Customer Portal marker missing"; exit 1; }
grep -q "name('transition')" routes/asset-master.php || { echo "ERROR: asset lifecycle transition route missing"; exit 1; }
grep -q "name('locations.store')" routes/asset-master.php || { echo "ERROR: asset location hierarchy route missing"; exit 1; }
grep -q "name('assign-location')" routes/asset-master.php || { echo "ERROR: asset location assignment route missing"; exit 1; }
grep -q "name('commissioning.request')" routes/asset-master.php || { echo "ERROR: commissioning request route missing"; exit 1; }
grep -q "name('commissioning.review')" routes/asset-master.php || { echo "ERROR: commissioning review route missing"; exit 1; }
grep -q 'Maker/checker control' app/Services/AssetMasterService.php || { echo "ERROR: commissioning maker/checker control missing"; exit 1; }
grep -q 'Asset Timeline — Append Only' resources/views/maintenance/asset-master/show.blade.php || { echo "ERROR: Asset 360 append-only timeline missing"; exit 1; }

echo "==> Installing dependencies"
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
composer install --no-interaction --prefer-dist --optimize-autoloader

upsert_env() {
  local key="$1" value="$2"
  if grep -qE "^${key}=" .env; then sed -i "s#^${key}=.*#${key}=${value}#" .env; else printf '%s=%s\n' "$key" "$value" >> .env; fi
}
upsert_env SESSION_DRIVER file
upsert_env SESSION_SECURE_COOKIE false
upsert_env SESSION_SAME_SITE lax
upsert_env SESSION_DOMAIN ""

php artisan optimize:clear

echo "==> Waiting for MySQL"
ready=0
for attempt in $(seq 1 20); do
  if php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); Illuminate\Support\Facades\DB::select("SELECT 1");' >/dev/null 2>&1; then ready=1; break; fi
  sleep 2
done
[ "$ready" -eq 1 ] || { echo "ERROR: database did not become ready"; exit 1; }

echo "==> Applying migrations and workflow identities"
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\WorkflowTestUsersSeeder' --force
php artisan unifco:bootstrap-warehouse-access
php artisan brand:materialize
if [[ ! -e public/storage && ! -L public/storage ]]; then
  php artisan storage:link
fi

echo "==> Verifying Phase B database foundation"
php -r '
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach(["asset_locations","asset_lifecycle_events","asset_commissioning_records","asset_documents"] as $table){if(!Illuminate\Support\Facades\Schema::hasTable($table)){fwrite(STDERR,"ERROR: Phase B table missing: $table\n");exit(1);}}
foreach(["asset_location_id","commissioning_status","commissioning_requested_by","commissioning_requested_at","commissioning_approved_by","commissioning_approved_at","commissioning_notes"] as $column){if(!Illuminate\Support\Facades\Schema::hasColumn("assets",$column)){fwrite(STDERR,"ERROR: Phase B asset column missing: $column\n");exit(1);}}
foreach(["tenant_id","organization_id","path","file_path","mime_type","version","issued_at","expires_at"] as $column){if(!Illuminate\Support\Facades\Schema::hasColumn("asset_documents",$column)){fwrite(STDERR,"ERROR: professional asset document column missing: $column\n");exit(1);}}
$c=Illuminate\Support\Facades\DB::selectOne("SELECT DATA_TYPE data_type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=\"users\" AND COLUMN_NAME=\"status\"");
if(!$c||$c->data_type!=="varchar"){fwrite(STDERR,"ERROR: users.status must use lifecycle strings\n");exit(1);}
echo "Asset Phase B database foundation verified\n";
'

echo "==> Verifying critical runtime routes"
php artisan route:list --name=asset-master.index >/dev/null
php artisan route:list --name=asset-master.show >/dev/null
php artisan route:list --name=asset-master.verify >/dev/null
php artisan route:list --name=asset-master.transition >/dev/null
php artisan route:list --name=asset-master.locations.store >/dev/null
php artisan route:list --name=asset-master.assign-location >/dev/null
php artisan route:list --name=asset-master.commissioning.request >/dev/null
php artisan route:list --name=asset-master.commissioning.review >/dev/null
php artisan route:list --name=asset-master.documents.store >/dev/null
php artisan route:list --name=public.asset.lookup >/dev/null
php artisan route:list --name=customer.portal >/dev/null
php artisan route:list --name=crm.acquisition.index >/dev/null
php artisan list | grep -q 'unifco:check-approval-sla'

php artisan optimize:clear
supervisorctl restart "$APP_NAME"

echo "==> Waiting for application"
app_ready=0
for attempt in $(seq 1 20); do
  if curl -fsSI http://127.0.0.1:8081/login >/dev/null; then app_ready=1; break; fi
  sleep 2
done
[ "$app_ready" -eq 1 ] || { echo "ERROR: application did not become ready"; exit 1; }
curl -fsSI http://127.0.0.1:8081/ | grep -qi 'X-UNIFCO-Release: home-electrical-20260821-12' || { echo "ERROR: homepage release header missing"; exit 1; }

echo "==> Deploy complete at $DEPLOY_SHA"