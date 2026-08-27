<?php

namespace Database\Seeders;

use App\Models\{Customer,CustomerContact,CustomerSite,Organization,Tenant,User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB,Hash};

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
            'PROCUREMENT'=>['name'=>'Workflow Procurement','email'=>'procurement@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','procurement.po.read','procurement.po.approve','inventory.stock.read','crm.customer.read']],
            'TENDERS_CONTRACTS'=>['name'=>'Workflow Tenders & Contracts','email'=>'tenders@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','crm.customer.read','crm.customer.manage','reporting.executive.read']],
            'FINANCE'=>['name'=>'Workflow Finance','email'=>'finance@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','finance.journal.read','finance.journal.create','reporting.executive.read','crm.customer.read']],
            'PROJECT_MANAGER'=>['name'=>'Workflow Project Manager','email'=>'projects.manager@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','projects.project.read','projects.project.manage','maintenance.work_order.read','crm.customer.read','reporting.executive.read']],
            'CEO'=>['name'=>'Workflow Chief Executive Officer','email'=>'ceo@unifco.local','permissions'=>['dashboard.view','workflow.approval.read','workflow.approval.decide','reporting.executive.read','crm.customer.read','finance.journal.read','projects.project.read','procurement.po.read','maintenance.work_order.read']],
        ];
        foreach($roles as $role=>$config){User::updateOrCreate(['email'=>$config['email']],['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>$config['name'],'password'=>Hash::make($password),'role'=>$role,'status'=>'ACTIVE','force_password_change'=>false]);foreach($config['permissions'] as $permission)DB::table('role_permissions')->updateOrInsert(['tenant_id'=>$tenant->id,'role_code'=>$role,'permission_code'=>$permission],['created_at'=>now(),'updated_at'=>now()]);}

        $customer=Customer::updateOrCreate(['tenant_id'=>$tenant->id,'customer_code'=>'WF-TEST-001'],['organization_id'=>$org->id,'name'=>'UNIFCO Workflow Test Customer','commercial_registration'=>'WF-TEST-CR-001','email'=>'workflow.customer@unifco.local','contact_name'=>'Workflow Customer Admin','phone'=>'0500000001','city'=>'Riyadh','country'=>'Saudi Arabia','address'=>'Riyadh Test Facility','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        CustomerContact::updateOrCreate(['customer_id'=>$customer->id,'email'=>'workflow.customer@unifco.local'],['name'=>'Workflow Customer Admin','job_title'=>'Facility Manager','contact_type'=>'PRIMARY','mobile'=>'0500000001','is_primary'=>true]);
        $site=CustomerSite::updateOrCreate(['customer_id'=>$customer->id,'site_code'=>'WF-RUH-01'],['name'=>'Workflow Riyadh Test Site','city'=>'Riyadh','address'=>'Riyadh Test Facility','contact_name'=>'Workflow Customer Admin','contact_mobile'=>'0500000001','status'=>'ACTIVE']);

        $portalUsers=[
            ['email'=>'workflow.customer@unifco.local','name'=>'Workflow Customer Admin','portal_role'=>'CUSTOMER_ADMIN'],
            ['email'=>'workflow.site.manager@unifco.local','name'=>'Workflow Site Manager','portal_role'=>'SITE_MANAGER'],
            ['email'=>'workflow.finance@unifco.local','name'=>'Workflow Customer Finance','portal_role'=>'FINANCE'],
            ['email'=>'workflow.viewer@unifco.local','name'=>'Workflow Customer Viewer','portal_role'=>'VIEWER'],
        ];
        foreach($portalUsers as $config){
            $user=User::updateOrCreate(['email'=>$config['email']],['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'name'=>$config['name'],'password'=>Hash::make($password),'role'=>'CUSTOMER','customer_portal_role'=>$config['portal_role'],'status'=>'ACTIVE','force_password_change'=>false]);
            if($config['portal_role']!=='CUSTOMER_ADMIN') DB::table('customer_portal_user_scopes')->updateOrInsert(['user_id'=>$user->id,'scope_type'=>'SITE','scope_id'=>$site->id],['created_at'=>now(),'updated_at'=>now()]);
        }
    }
}
