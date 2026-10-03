<?php

namespace Database\Seeders;

use App\Models\{ChartAccount,Customer,CustomerContact,CustomerSite,Employee,FinancialDocument,Organization,ServiceRequest,Tenant,User,WorkOrderAssignment};
use App\Services\{RequestStageOwnerService,ServiceRequestWorkflowService};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB,Hash,Schema};

class WorkflowTestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $tenant=Tenant::firstOrCreate(['code'=>'UNIFCO'],['name'=>'UNIFCO','status'=>'ACTIVE']);
        $org=Organization::firstOrCreate(['tenant_id'=>$tenant->id,'code'=>'HQ'],['name'=>'UNIFCO HQ','status'=>'ACTIVE']);
        $password=(string)env('WORKFLOW_TEST_PASSWORD','UnifcoWorkflow!2026');
        $roles=[
            'MAINTENANCE_ENGINEER'=>['name'=>'Workflow Maintenance Engineer','email'=>'engineer@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','maintenance.work_order.read','maintenance.work_order.manage','eam.asset.read','crm.customer.read']],
            'MAINTENANCE_MANAGER'=>['name'=>'Workflow Maintenance Manager','email'=>'maintenance.manager@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','maintenance.work_order.read','maintenance.work_order.manage','eam.asset.read','crm.customer.read','reporting.executive.read']],
            'OPERATIONS_MANAGER'=>['name'=>'Workflow Operations Manager','email'=>'operations.manager@unifco.local','permissions'=>['operations.dashboard.view','service_requests.read','service_requests.assign','service_requests.escalate','maintenance.work_order.read','maintenance.work_order.manage','maintenance.work_order.assign','field.operations.read','eam.asset.read','projects.project.read','crm.customer.read','reporting.executive.read']],
            'PROJECT_MANAGER'=>['name'=>'Workflow Project Manager','email'=>'projects.manager@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','projects.project.read','projects.project.manage','maintenance.work_order.read','crm.customer.read','reporting.executive.read']],
            'TECHNICAL_SUPERVISOR'=>['name'=>'Workflow Technical Supervisor','email'=>'technical.supervisor@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','service_requests.read','service_requests.assign','maintenance.work_order.read','maintenance.work_order.manage','maintenance.work_order.assign','field.operations.read','eam.asset.read']],
            'TECHNICIAN'=>['name'=>'Workflow Technician','email'=>'technician@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','maintenance.work_order.read','maintenance.work_order.manage','eam.asset.read']],
            'QUALITY'=>['name'=>'Workflow Quality','email'=>'quality@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','maintenance.work_order.read','eam.asset.read']],
            'HSE'=>['name'=>'Workflow HSE','email'=>'hse@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','maintenance.work_order.read','eam.asset.read']],
            'CUSTOMER_SERVICE'=>['name'=>'Workflow Customer Service','email'=>'customer.service@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','crm.customer.read','crm.customer.manage']],
            'PROCUREMENT'=>['name'=>'Workflow Procurement','email'=>'procurement@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','procurement.po.read','procurement.po.approve','inventory.stock.read','crm.customer.read']],
            'TENDERS_CONTRACTS'=>['name'=>'Workflow Tenders & Contracts','email'=>'tenders@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','crm.customer.read','crm.customer.manage','reporting.executive.read']],
            'SALES'=>['name'=>'Workflow Sales','email'=>'sales@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','crm.customer.read','crm.customer.manage']],
            'FINANCE_MANAGER'=>['name'=>'Workflow Finance Manager','email'=>'finance@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','finance.journal.read','finance.journal.create','finance.journal.post','reporting.executive.read','crm.customer.read']],
            'ACCOUNTANT'=>['name'=>'Workflow Accountant','email'=>'accountant@unifco.local','permissions'=>['dashboard.view','finance.journal.read','finance.journal.create','crm.customer.read']],
            'CEO'=>['name'=>'Workflow Chief Executive Officer','email'=>'ceo@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','reporting.executive.read','crm.customer.read','finance.journal.read','projects.project.read','procurement.po.read','maintenance.work_order.read']],
        ];
        foreach($roles as $role=>$config){User::updateOrCreate(['email'=>$config['email']],['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>$config['name'],'password'=>Hash::make($password),'role'=>$role,'status'=>'ACTIVE','force_password_change'=>false]);foreach($config['permissions'] as $permission)DB::table('role_permissions')->updateOrInsert(['tenant_id'=>$tenant->id,'role_code'=>$role,'permission_code'=>$permission],['created_at'=>now(),'updated_at'=>now()]);}

        // Older finance test users can retain a structured role assignment while
        // their seeder-created permission rows still have only a legacy code.
        // Bind the existing grants to that assigned role without replacing DENY.
        $financeUser=User::where('tenant_id',$tenant->id)->where('email','finance@unifco.local')->firstOrFail();
        $financeRoleId=DB::table('user_roles')->join('roles','roles.id','=','user_roles.role_id')
            ->where('user_roles.user_id',$financeUser->id)->whereNull('user_roles.revoked_at')
            ->where('roles.code','FINANCE_MANAGER')->where('roles.is_active',true)
            ->value('roles.id');
        if ($financeRoleId) {
            DB::table('role_permissions')->where('tenant_id',$tenant->id)
                ->where('role_code','FINANCE_MANAGER')
                ->whereIn('permission_code',$roles['FINANCE_MANAGER']['permissions'])
                ->whereNull('role_id')->update(['role_id'=>$financeRoleId]);
        }

        // The workflow test technician also needs a field-service employee identity.
        // Reuse an existing identity and never replace a deliberate user link.
        $testTechnician=User::where('tenant_id',$tenant->id)->where('email','technician@unifco.local')->firstOrFail();
        if (Schema::hasTable('employees') && ! $testTechnician->employee_id) {
            $employee=Employee::firstOrCreate(
                ['tenant_id'=>$tenant->id,'employee_no'=>'WF-TECH-001'],
                ['organization_id'=>$org->id,'name'=>'Workflow Technician','email'=>'technician@unifco.local','hire_date'=>today(),'status'=>'ACTIVE']
            );
            $testTechnician->update(['employee_id'=>$employee->id]);
        }

        // The dedicated maintenance UAT project must have a responsible manager
        // before the agreed request matrix can leave Operations triage.
        if (Schema::hasTable('project_user_assignments')) {
            $uatCustomerId=Customer::where('tenant_id',$tenant->id)->where('customer_code','100')->value('id');
            $uatProject=$uatCustomerId ? DB::table('projects')
                ->where('tenant_id',$tenant->id)->where('customer_id',$uatCustomerId)
                ->where('project_no','PRJ-TEST-001')->where('status','ACTIVE')->first() : null;
            if ($uatProject) {
                // Only the dedicated request-matrix project receives test team members.
                // Preserve an existing active owner for each role rather than replacing
                // a deliberate project assignment during a repeat deployment.
                $uatTeam=[
                    'PROJECT_MANAGER'=>'projects.manager@unifco.local',
                    'MAINTENANCE_MANAGER'=>'maintenance.manager@unifco.local',
                    'MAINTENANCE_ENGINEER'=>'engineer@unifco.local',
                    'TECHNICAL_SUPERVISOR'=>'technical.supervisor@unifco.local',
                    'TECHNICIAN'=>'technician@unifco.local',
                    'QUALITY'=>'quality@unifco.local',
                    'HSE'=>'hse@unifco.local',
                ];
                foreach ($uatTeam as $projectRole=>$email) {
                    $hasActiveOwner=DB::table('project_user_assignments')
                        ->where('tenant_id',$tenant->id)->where('project_id',$uatProject->id)
                        ->where('project_role',$projectRole)->where('status','ACTIVE')
                        ->where(fn($q)=>$q->whereNull('starts_on')->orWhere('starts_on','<=',today()))
                        ->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',today()))
                        ->exists();
                    if ($hasActiveOwner) continue;

                    $owner=User::where('tenant_id',$tenant->id)->where('email',$email)
                        ->where('status','ACTIVE')->first();
                    if (! $owner) continue;

                    DB::table('project_user_assignments')->updateOrInsert(
                        ['project_id'=>$uatProject->id,'user_id'=>$owner->id],
                        ['tenant_id'=>$tenant->id,'project_role'=>$projectRole,
                         'access_level'=>'PROJECT','status'=>'ACTIVE','starts_on'=>null,'ends_on'=>null,
                         'reason'=>'Dedicated UAT request-matrix routing','updated_at'=>now(),'created_at'=>now()]
                    );
                }

                // Requests already at an open stage retain the owner calculated
                // before the project team was populated. Reconcile those
                // approval rows after seeding without advancing the workflow.
                ServiceRequest::query()
                    ->where('tenant_id',$tenant->id)
                    ->where('project_id',$uatProject->id)
                    ->where('status','OPEN')
                    ->chunkById(100,function ($requests) {
                        foreach ($requests as $request) {
                            app(RequestStageOwnerService::class)->refresh($request);
                        }
                    });

                // Reconcile existing UAT execution orders so the assigned workflow
                // technician can see them in Technician Mobile as well.
                if ($testTechnician->employee_id && Schema::hasTable('work_order_assignments')) {
                    ServiceRequest::query()->where('tenant_id',$tenant->id)
                        ->where('project_id',$uatProject->id)->where('workflow_stage','EXECUTION')
                        ->where('assigned_engineer_id',$testTechnician->id)->whereNotNull('work_order_id')
                        ->chunkById(100,function ($requests) use ($tenant,$org,$testTechnician) {
                            foreach ($requests as $request) {
                                if (WorkOrderAssignment::where('work_order_id',$request->work_order_id)->exists()) continue;
                                WorkOrderAssignment::firstOrCreate(
                                    ['work_order_id'=>$request->work_order_id,'employee_id'=>$testTechnician->employee_id],
                                    ['tenant_id'=>$tenant->id,'organization_id'=>$org->id,
                                     'scheduled_start'=>now(),'dispatch_status'=>'DISPATCHED','dispatched_at'=>now(),
                                     'dispatcher_notes'=>'Dedicated UAT workflow assignment']
                                );
                            }
                        });
                }


                // Existing matrix requests were started before closure and CSAT
                // were added to the maintenance template. Preserve prior decisions
                // and append the missing tail; a previously accepted order is
                // resumed at its next stage instead of remaining falsely complete.
                if (Schema::hasTable('approval_requests')) {
                    $uatOperationsManager=User::where('tenant_id',$tenant->id)
                        ->where('email','operations.manager@unifco.local')->where('status','ACTIVE')->first();
                    ServiceRequest::query()->where('tenant_id',$tenant->id)
                        ->where('project_id',$uatProject->id)
                        ->whereIn('request_no',[
                            'SR-UNRM-926000017','SR-UNUM-926000018',
                            'SR-UNRM-926000023','SR-UNUM-926000024',
                        ])->get()->each(function (ServiceRequest $request) use ($uatOperationsManager) {
                            if (! $request->operations_manager_id && $uatOperationsManager) {
                                $request->update(['operations_manager_id'=>$uatOperationsManager->id]);
                            }
                            app(ServiceRequestWorkflowService::class)
                                ->repairMissingMaintenanceClosureStages($request);
                        });
                }

                // Older auto-generated UAT drafts used symbolic account codes
                // while the demo chart uses 1200/4100. Repair only these two
                // unposted request-linked drafts; never alter posted journals.
                if (Schema::hasTable('financial_documents')
                    && ChartAccount::where('tenant_id',$tenant->id)->where('code','1200')->where('status','ACTIVE')->exists()
                    && ChartAccount::where('tenant_id',$tenant->id)->where('code','4100')->where('status','ACTIVE')->exists()) {
                    $uatRequestIds=ServiceRequest::where('tenant_id',$tenant->id)->where('project_id',$uatProject->id)
                        ->whereIn('request_no',['SR-UNRM-926000023','SR-UNUM-926000024'])->pluck('id');
                    FinancialDocument::where('tenant_id',$tenant->id)->whereIn('service_request_id',$uatRequestIds)
                        ->where('document_type','AR_INVOICE')->where('status','DRAFT')
                        ->where('control_account_code','AR')->where('offset_account_code','REV')
                        ->update(['control_account_code'=>'1200','offset_account_code'=>'4100']);
                }
            }
        }

        $customer=Customer::updateOrCreate(['tenant_id'=>$tenant->id,'customer_code'=>'WF-TEST-001'],['organization_id'=>$org->id,'name'=>'UNIFCO Workflow Test Customer','commercial_registration'=>'WF-TEST-CR-001','email'=>'workflow.customer@unifco.local','contact_name'=>'Workflow Customer Admin','phone'=>'0500000001','city'=>'Riyadh','country'=>'Saudi Arabia','address'=>'Riyadh Test Facility','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        CustomerContact::updateOrCreate(['customer_id'=>$customer->id,'email'=>'workflow.customer@unifco.local'],['name'=>'Workflow Customer Admin','job_title'=>'Facility Manager','contact_type'=>'PRIMARY','mobile'=>'0500000001','is_primary'=>true]);
        $site=CustomerSite::updateOrCreate(['customer_id'=>$customer->id,'site_code'=>'WF-RUH-01'],['name'=>'Workflow Riyadh Test Site','city'=>'Riyadh','address'=>'Riyadh Test Facility','contact_name'=>'Workflow Customer Admin','contact_mobile'=>'0500000001','status'=>'ACTIVE']);

        $portalUsers=[
            ['email'=>'workflow.customer@unifco.local','name'=>'Workflow Customer Admin','portal_role'=>'CUSTOMER'],
            ['email'=>'workflow.site.manager@unifco.local','name'=>'Workflow Site Manager','portal_role'=>'CUSTOMER'],
            ['email'=>'workflow.finance@unifco.local','name'=>'Workflow Customer Finance','portal_role'=>'CUSTOMER'],
            ['email'=>'workflow.viewer@unifco.local','name'=>'Workflow Customer Viewer','portal_role'=>'CUSTOMER'],
        ];
        foreach($portalUsers as $config){
            $user=User::updateOrCreate(['email'=>$config['email']],['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'name'=>$config['name'],'password'=>Hash::make($password),'role'=>'CUSTOMER','customer_portal_role'=>$config['portal_role'],'status'=>'ACTIVE','force_password_change'=>false]);
            DB::table('customer_portal_user_scopes')->where('user_id',$user->id)->delete();
        }
    }
}
