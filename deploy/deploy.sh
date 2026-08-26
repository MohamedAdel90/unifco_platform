#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/unifco_platform"
APP_NAME="unifco-app"
cd "$APP_DIR"

echo "==> Fetching and checking out latest main"
git fetch origin main
git reset --hard origin/main
DEPLOY_SHA="$(git rev-parse HEAD)"
echo "==> Server checkout: $DEPLOY_SHA"

echo "==> Validating current release foundation"
for file in \
  resources/views/public/request.blade.php \
  routes/public.php \
  routes/public-asset-qr.php \
  routes/parts.php \
  database/migrations/2026_08_26_000050_link_public_requests_to_asset_registry.php \
  database/migrations/2026_08_26_000051_build_service_request_workflow_foundation.php \
  app/Services/ServiceRequestWorkflowService.php \
  app/Services/CustomerLifecycleService.php; do
  test -s "$file" || { echo "ERROR: required release file missing: $file"; exit 1; }
done
grep -q 'home-electrical-20260821-12' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: homepage release marker missing"; exit 1; }
grep -q 'customer-portal-20260826-workflow-1' app/Http/Controllers/CustomerPortalController.php || { echo "ERROR: Customer 360 workflow release marker missing"; exit 1; }
grep -q 'unifco:check-approval-sla' routes/console.php || { echo "ERROR: approval SLA scheduler missing"; exit 1; }
grep -q 'WorkOrderPartConsumptionController' routes/parts.php || { echo "ERROR: work-order part lifecycle routes missing"; exit 1; }

echo "==> Installing dependencies"
composer install --no-interaction --prefer-dist --optimize-autoloader
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

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
DB_DRIVER="$(php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo Illuminate\Support\Facades\DB::connection()->getDriverName();')"
[ "$DB_DRIVER" = "mysql" ] || { echo "ERROR: deployment requires MySQL; got $DB_DRIVER"; exit 1; }

echo "==> Applying migrations and operational bootstraps"
php artisan migrate --force
php artisan unifco:bootstrap-warehouse-access
php artisan brand:materialize
php artisan storage:link || true

echo "==> Verifying critical runtime routes and commands"
php artisan route:list --name=public.request.store >/dev/null
php artisan route:list --name=public.asset.lookup >/dev/null
php artisan route:list --name=customer.portal >/dev/null
php artisan route:list --name=customer.asset.show >/dev/null
php artisan route:list --name=maintenance.work-orders.show >/dev/null
php artisan route:list --name=inventory.part-requests.consume >/dev/null
php artisan route:list --name=inventory.part-requests.return >/dev/null
php artisan route:list --name=workflow.approvals.index >/dev/null
php artisan list | grep -q 'unifco:check-approval-sla'
php artisan list | grep -q 'unifco:recalculate-asset-health'
php artisan list | grep -q 'unifco:check-spare-reorder-alerts'

php -r '
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$tables=["customer_activity_events","approval_requests","service_requests","work_orders","work_order_part_requests","asset_part_installations","work_order_part_returns"];
foreach($tables as $table){if(!Illuminate\Support\Facades\Schema::hasTable($table)){fwrite(STDERR,"ERROR: required table missing: $table\n");exit(1);}}
foreach(["request_type","workflow_stage","eligibility","current_stage_due_at"] as $col){if(!Illuminate\Support\Facades\Schema::hasColumn("service_requests",$col)){fwrite(STDERR,"ERROR: service workflow column missing: $col\n");exit(1);}}
foreach(["workflow_key","approval_role","sla_minutes","due_at"] as $col){if(!Illuminate\Support\Facades\Schema::hasColumn("approval_requests",$col)){fwrite(STDERR,"ERROR: approval workflow column missing: $col\n");exit(1);}}
echo "Database release foundation verified\n";
'

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
curl -fsS http://127.0.0.1:8081/brand/unifco-logo-v2.webp -o /tmp/unifco-logo.webp
php -r '$b=file_get_contents("/tmp/unifco-logo.webp"); if(substr($b,0,4)!=="RIFF"||substr($b,8,4)!=="WEBP") exit(1);' || { echo "ERROR: brand logo endpoint invalid"; exit 1; }

echo "==> Verifying login CSRF/session round trip"
rm -f /tmp/unifco-login.cookies /tmp/unifco-login.html /tmp/unifco-login-post.headers
curl -fsS -c /tmp/unifco-login.cookies http://127.0.0.1:8081/login -o /tmp/unifco-login.html
csrf_token="$(grep -o 'name="_token" value="[^"]*"' /tmp/unifco-login.html | head -n1 | sed 's/^.*value="//;s/"$//')"
[ -n "$csrf_token" ] || { echo "ERROR: could not extract CSRF token"; exit 1; }
login_status="$(curl -sS -o /dev/null -D /tmp/unifco-login-post.headers -w '%{http_code}' -b /tmp/unifco-login.cookies -c /tmp/unifco-login.cookies -X POST http://127.0.0.1:8081/login --data-urlencode "_token=${csrf_token}" --data-urlencode 'email=csrf-check@unifco.invalid' --data-urlencode 'password=invalid-password')"
[ "$login_status" = "302" ] || { echo "ERROR: login validation returned HTTP $login_status"; exit 1; }

echo "==> Deploy complete at $DEPLOY_SHA"
