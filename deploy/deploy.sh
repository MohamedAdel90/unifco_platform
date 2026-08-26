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
  resources/views/workflow/workspace.blade.php \
  routes/public.php \
  routes/public-asset-qr.php \
  routes/parts.php \
  database/migrations/2026_08_26_000050_link_public_requests_to_asset_registry.php \
  database/migrations/2026_08_26_000051_build_service_request_workflow_foundation.php \
  database/seeders/WorkflowTestUsersSeeder.php \
  app/Http/Controllers/Workflow/WorkflowWorkspaceController.php \
  app/Services/ServiceRequestWorkflowService.php \
  app/Services/CustomerLifecycleService.php; do
  test -s "$file" || { echo "ERROR: required release file missing: $file"; exit 1; }
done
grep -q 'home-electrical-20260821-12' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: homepage release marker missing"; exit 1; }
grep -q 'customer-portal-20260826-workflow-1' app/Http/Controllers/CustomerPortalController.php || { echo "ERROR: Customer 360 workflow release marker missing"; exit 1; }
grep -q 'WorkflowWorkspaceController' routes/web.php || { echo "ERROR: workflow workspace route missing"; exit 1; }

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

echo "==> Applying migrations and workflow test accounts"
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\WorkflowTestUsersSeeder' --force
php artisan unifco:bootstrap-warehouse-access
php artisan brand:materialize
php artisan storage:link || true

echo "==> Verifying workflow test identities and password"
php -r '
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$expected=[
"engineer@unifco.local"=>"MAINTENANCE_ENGINEER",
"maintenance.manager@unifco.local"=>"MAINTENANCE_MANAGER",
"procurement@unifco.local"=>"PROCUREMENT",
"tenders@unifco.local"=>"TENDERS_CONTRACTS",
"finance@unifco.local"=>"FINANCE",
"projects.manager@unifco.local"=>"PROJECT_MANAGER",
"ceo@unifco.local"=>"CEO",
"workflow.customer@unifco.local"=>"CUSTOMER",
];
foreach($expected as $email=>$role){
 $u=App\Models\User::where("email",$email)->first();
 if(!$u||$u->role!==$role||!Illuminate\Support\Facades\Hash::check("UnifcoWorkflow!2026",$u->password)){
   fwrite(STDERR,"ERROR: workflow test user invalid: $email\n"); exit(1);
 }
}
echo "Workflow test identities and passwords verified\n";
'

echo "==> Verifying critical runtime routes and commands"
php artisan route:list --name=workflow.workspace >/dev/null
php artisan route:list --name=workflow.approvals.index >/dev/null
php artisan route:list --name=customer.portal >/dev/null
php artisan route:list --name=public.request.store >/dev/null
php artisan route:list --name=public.asset.lookup >/dev/null
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
