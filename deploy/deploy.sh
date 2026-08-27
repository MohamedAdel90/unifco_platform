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
  resources/views/workflow/customer-actions.blade.php \
  resources/views/customer/users-access.blade.php \
  resources/views/customer/action-center.blade.php \
  resources/views/crm/acquisition.blade.php \
  routes/public.php \
  routes/customer-phase2.php \
  routes/customer-acquisition.php \
  routes/public-asset-qr.php \
  routes/parts.php \
  database/migrations/2026_08_26_000050_link_public_requests_to_asset_registry.php \
  database/migrations/2026_08_26_000051_build_service_request_workflow_foundation.php \
  database/migrations/2026_08_27_000052_add_customer_portal_rbac_scopes.php \
  database/migrations/2026_08_27_000053_add_customer_portal_action_requests.php \
  database/migrations/2026_08_27_000054_add_customer_action_attachments.php \
  database/migrations/2026_08_27_000055_add_assignment_sla_to_customer_actions.php \
  database/migrations/2026_08_27_000056_add_customer_acquisition_lifecycle.php \
  database/migrations/2026_08_27_000057_add_acquisition_governance_fields.php \
  database/seeders/WorkflowTestUsersSeeder.php \
  app/Http/Controllers/CRM/CustomerAcquisitionController.php \
  app/Services/CustomerAcquisitionService.php \
  app/Http/Controllers/Workflow/WorkflowWorkspaceController.php \
  app/Http/Controllers/Workflow/CustomerActionInboxController.php \
  app/Http/Controllers/CustomerPortalAccessAdminController.php \
  app/Http/Controllers/CustomerActionCenterController.php \
  app/Http/Controllers/CustomerPortalPhase2ActionController.php \
  app/Services/CustomerPortalAccessService.php \
  app/Services/ServiceRequestWorkflowService.php \
  app/Services/CustomerLifecycleService.php; do
  test -s "$file" || { echo "ERROR: required release file missing: $file"; exit 1; }
done
grep -q 'home-electrical-20260821-12' app/Http/Controllers/PublicSiteController.php || { echo "ERROR: homepage release marker missing"; exit 1; }
grep -q 'customer-portal-rbac-phase1-20260827' app/Http/Controllers/CustomerPortalController.php || { echo "ERROR: Customer Portal Phase 1 marker missing"; exit 1; }
grep -q 'customer.actions' routes/customer-phase2.php || { echo "ERROR: Customer Portal Phase 2 action route missing"; exit 1; }
grep -q 'workflow.customer-actions.index' routes/customer-phase2.php || { echo "ERROR: internal customer action inbox route missing"; exit 1; }
grep -q 'assigned_role' app/Http/Controllers/CustomerPortalPhase2ActionController.php || { echo "ERROR: customer action role assignment missing"; exit 1; }
grep -q 'CustomerPortalAccessAdminController' routes/public.php || { echo "ERROR: Customer Users & Access routes missing"; exit 1; }
grep -q 'WorkflowWorkspaceController' routes/web.php || { echo "ERROR: workflow workspace route missing"; exit 1; }
grep -q "name('request-conversion')" routes/customer-acquisition.php || { echo "ERROR: acquisition conversion request route missing"; exit 1; }
grep -q "name('review-onboarding')" routes/customer-acquisition.php || { echo "ERROR: acquisition onboarding review route missing"; exit 1; }
grep -q 'conversion_approval_status' app/Services/CustomerAcquisitionService.php || { echo "ERROR: acquisition conversion governance missing"; exit 1; }

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

echo "==> Verifying workflow and customer portal test identities"
php -r '
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$expected=[
"engineer@unifco.local"=>["MAINTENANCE_ENGINEER",null],
"maintenance.manager@unifco.local"=>["MAINTENANCE_MANAGER",null],
"procurement@unifco.local"=>["PROCUREMENT",null],
"tenders@unifco.local"=>["TENDERS_CONTRACTS",null],
"finance@unifco.local"=>["FINANCE",null],
"projects.manager@unifco.local"=>["PROJECT_MANAGER",null],
"ceo@unifco.local"=>["CEO",null],
"workflow.customer@unifco.local"=>["CUSTOMER","CUSTOMER_ADMIN"],
"workflow.site.manager@unifco.local"=>["CUSTOMER","SITE_MANAGER"],
"workflow.finance@unifco.local"=>["CUSTOMER","FINANCE"],
"workflow.viewer@unifco.local"=>["CUSTOMER","VIEWER"],
];
foreach($expected as $email=>$expectedRole){
 $u=App\Models\User::where("email",$email)->first();
 if(!$u||$u->role!==$expectedRole[0]||($expectedRole[1]!==null&&$u->customer_portal_role!==$expectedRole[1])||!Illuminate\Support\Facades\Hash::check("UnifcoWorkflow!2026",$u->password)){
   fwrite(STDERR,"ERROR: test user invalid: $email\n"); exit(1);
 }
}
foreach(["customer_portal_user_scopes","customer_portal_action_requests","crm_leads","customers"] as $table){if(!Illuminate\Support\Facades\Schema::hasTable($table)){fwrite(STDERR,"ERROR: required table missing: $table\n");exit(1);}}
foreach(["assigned_role","priority","due_at"] as $column){if(!Illuminate\Support\Facades\Schema::hasColumn("customer_portal_action_requests",$column)){fwrite(STDERR,"ERROR: customer action SLA column missing: $column\n");exit(1);}}
foreach(["source_channel","lifecycle_stage","assigned_to","next_follow_up_at","conversion_approval_status"] as $column){if(!Illuminate\Support\Facades\Schema::hasColumn("crm_leads",$column)){fwrite(STDERR,"ERROR: acquisition lead column missing: $column\n");exit(1);}}
foreach(["acquisition_source","origin_lead_id","onboarding_review_status"] as $column){if(!Illuminate\Support\Facades\Schema::hasColumn("customers",$column)){fwrite(STDERR,"ERROR: acquisition customer column missing: $column\n");exit(1);}}
echo "Workflow, Customer Portal and Acquisition foundation verified\n";
'

echo "==> Verifying critical runtime routes and commands"
php artisan route:list --name=workflow.workspace >/dev/null
php artisan route:list --name=workflow.approvals.index >/dev/null
php artisan route:list --name=workflow.customer-actions.index >/dev/null
php artisan route:list --name=customer.portal >/dev/null
php artisan route:list --name=customer.actions >/dev/null
php artisan route:list --name=customer.access.index >/dev/null
php artisan route:list --name=customer.access.store >/dev/null
php artisan route:list --name=customer.requests.store >/dev/null
php artisan route:list --name=customer.invoices.payment-proof >/dev/null
php artisan route:list --name=customer.work-orders.revisit >/dev/null
php artisan route:list --name=crm.acquisition.index >/dev/null
php artisan route:list --name=crm.acquisition.follow-up >/dev/null
php artisan route:list --name=crm.acquisition.request-conversion >/dev/null
php artisan route:list --name=crm.acquisition.review-conversion >/dev/null
php artisan route:list --name=crm.acquisition.review-onboarding >/dev/null
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