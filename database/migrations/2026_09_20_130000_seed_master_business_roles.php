<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const ROLES = [
        'CEO' => ['Chief Executive Officer','الرئيس التنفيذي','Executive company-wide oversight with governed approvals.'],
        'OPERATIONS_MANAGER' => ['Operations Manager','مدير التشغيل','Scoped operational command across assigned departments, projects and sites.'],
        'MAINTENANCE_MANAGER' => ['Maintenance Manager','مدير الصيانة','Scoped maintenance planning, work-order control and technical closure.'],
        'PROJECT_MANAGER' => ['Project Manager','مدير المشروع','Management of assigned projects only.'],
        'MAINTENANCE_ENGINEER' => ['Maintenance Engineer','مهندس الصيانة','Technical assessment, diagnosis and maintenance engineering.'],
        'TECHNICAL_SUPERVISOR' => ['Technical Supervisor','المشرف الفني','Daily technical supervision and technician assignment within scope.'],
        'TECHNICIAN' => ['Technician','الفني','Execution of assigned field work and technical checklists.'],
        'QUALITY' => ['Quality','الجودة','Quality review, inspection and non-conformance control.'],
        'HSE' => ['HSE','السلامة والصحة المهنية','Safety permits, inspections and HSE controls.'],
        'FINANCE_MANAGER' => ['Finance Manager','المدير المالي','Financial oversight and governed financial approvals.'],
        'ACCOUNTANT' => ['Accountant','المحاسب','Accounting entries, invoices, receipts and reconciliation.'],
        'CUSTOMER_SERVICE' => ['Customer Service','خدمة العملاء','Customer intake, verification, communication and request classification.'],
        'SALES' => ['Sales','المبيعات','Commercial opportunities, customer follow-up and quotation initiation.'],
        'PROCUREMENT' => ['Procurement','المشتريات','Purchasing workflow, RFQ/PO processing and supplier coordination.'],
        'TENDERS_CONTRACTS' => ['Tenders & Contracts','المناقصات والعقود','Tender, quotation and contract commercial administration.'],
        'CUSTOMER' => ['Customer','العميل','Single unified customer portal account for the current phase.'],
    ];

    private const ALLOW = [
        'CEO' => [
            'reporting.executive.read','crm.customer.read','projects.project.read','service_requests.read',
            'maintenance.work_order.read','eam.asset.read','inventory.stock.read','workflow.approval.read',
            'finance.journal.read',
        ],
        'OPERATIONS_MANAGER' => [
            'operations.dashboard.view','crm.customer.read','projects.project.read','service_requests.read',
            'service_requests.assign','service_requests.escalate','maintenance.work_order.read',
            'maintenance.work_order.manage','maintenance.work_order.assign','eam.asset.read','inventory.stock.read',
            'field.operations.read','reporting.executive.read',
        ],
        'MAINTENANCE_MANAGER' => [
            'service_requests.read','service_requests.assign','service_requests.escalate','maintenance.work_order.read',
            'maintenance.work_order.manage','maintenance.work_order.assign','maintenance.work_order.complete',
            'eam.asset.read','eam.asset.manage','inventory.stock.read','field.operations.read','projects.project.read',
        ],
        'PROJECT_MANAGER' => [
            'projects.project.read','service_requests.read','service_requests.assign','service_requests.escalate',
            'maintenance.work_order.read','maintenance.work_order.manage','eam.asset.read','inventory.stock.read',
            'crm.customer.read','reporting.executive.read',
        ],
        'MAINTENANCE_ENGINEER' => [
            'service_requests.read','maintenance.work_order.read','maintenance.work_order.manage',
            'eam.asset.read','eam.asset.manage','inventory.stock.read','inventory.transfer.request','field.operations.read',
        ],
        'TECHNICAL_SUPERVISOR' => [
            'service_requests.read','service_requests.assign','maintenance.work_order.read','maintenance.work_order.manage',
            'maintenance.work_order.assign','maintenance.work_order.complete','eam.asset.read','inventory.stock.read',
            'field.operations.read',
        ],
        'TECHNICIAN' => [
            'service_requests.read','maintenance.work_order.read','maintenance.work_order.execute',
            'maintenance.work_order.complete','eam.asset.read','inventory.stock.read','inventory.transfer.request',
            'field.operations.read',
        ],
        'QUALITY' => [
            'service_requests.read','maintenance.work_order.read','eam.asset.read','reporting.executive.read',
            'quality.inspection.read','quality.inspection.decide',
        ],
        'HSE' => [
            'service_requests.read','maintenance.work_order.read','eam.asset.read','hse.permit.read',
            'hse.permit.decide','hse.incident.manage',
        ],
        'FINANCE_MANAGER' => [
            'finance.journal.read','finance.journal.create','finance.journal.post','invoice.read','invoice.approve',
            'workflow.approval.read','workflow.approval.decide','crm.customer.read','projects.project.read',
            'procurement.po.read',
        ],
        'ACCOUNTANT' => [
            'finance.journal.read','finance.journal.create','invoice.read','invoice.create','payment.record',
            'crm.customer.read','projects.project.read',
        ],
        'CUSTOMER_SERVICE' => [
            'crm.customer.read','crm.customer.manage','service_requests.read','service_requests.create',
            'service_requests.classify','service_requests.route','projects.project.read','eam.asset.read',
        ],
        'SALES' => [
            'crm.customer.read','crm.customer.manage','projects.project.read','quotation.read','quotation.create',
            'service_requests.read','contract.read',
        ],
        'PROCUREMENT' => [
            'procurement.po.read','procurement.po.create','inventory.stock.read','inventory.transfer.request',
            'projects.project.read','service_requests.read',
        ],
        'TENDERS_CONTRACTS' => [
            'crm.customer.read','projects.project.read','quotation.read','quotation.create','contract.read',
            'contract.create','contract.manage','service_requests.read',
        ],
        'CUSTOMER' => [
            'customer.dashboard.view','customer.requests.view','customer.requests.create','customer.work_orders.view',
            'customer.visits.view','customer.maintenance_plan.view','customer.spare_parts.view','customer.spare_parts.request',
            'customer.sites.view','customer.assets.view','customer.quotations.view','customer.quotations.decide',
            'customer.contracts.view','customer.sla.view','customer.invoices.view','customer.reports.view',
            'customer.documents.view','customer.notifications.view','customer.inbox.manage','customer.profile.view',
        ],
    ];

    private const HARD_DENY = [
        'users.create','users.edit','roles.manage','scopes.manage','audit.edit','audit.delete',
    ];

    public function up(): void
    {
        if(!Schema::hasTable('roles') || !Schema::hasTable('permissions') || !Schema::hasTable('role_permissions')) return;

        foreach(self::ROLES as $code=>$meta){
            [$nameEn,$nameAr,$description]=$meta;
            DB::table('roles')->updateOrInsert(
                ['tenant_id'=>null,'code'=>$code],
                [
                    'name_en'=>$nameEn,'name_ar'=>$nameAr,'description'=>$description,
                    'is_system_role'=>false,'grants_business_authority'=>true,
                    'requires_approval'=>in_array($code,['CEO','FINANCE_MANAGER'],true),
                    'is_active'=>true,'created_at'=>now(),'updated_at'=>now(),
                ]
            );
            foreach(self::ALLOW[$code] as $permission) $this->set($code,$permission,'ALLOW');
            if($code!=='CEO'){
                foreach(self::HARD_DENY as $permission) $this->set($code,$permission,'DENY');
            }
        }

        // Keep historical customer role rows for migrated accounts, but retire them
        // from new assignments. The unified CUSTOMER role is authoritative going forward.
        DB::table('roles')->whereNull('tenant_id')->whereIn('code',[
            'CUSTOMER_ADMIN','CUSTOMER_SITE_MANAGER','CUSTOMER_FINANCE','CUSTOMER_VIEWER'
        ])->update(['is_active'=>false,'updated_at'=>now()]);
    }

    private function set(string $roleCode,string $permissionCode,string $effect): void
    {
        [$module,$action]=array_pad(explode('.',$permissionCode,2),2,'access');
        $business=(bool) preg_match('/(approve|decide|post|pay|execute|complete|manage|create)/i',$permissionCode);

        DB::table('permissions')->updateOrInsert(
            ['code'=>$permissionCode],
            [
                'module'=>$module,'action'=>$action,
                'risk_level'=>$business?'HIGH':'NORMAL',
                'is_business_authority'=>$business,
                'is_scope_aware'=>!in_array($module,['users','roles','scopes','audit','security','system'],true),
                'description'=>null,'created_at'=>now(),'updated_at'=>now(),
            ]
        );

        $roleId=DB::table('roles')->whereNull('tenant_id')->where('code',$roleCode)->value('id');
        $permissionId=DB::table('permissions')->where('code',$permissionCode)->value('id');
        DB::table('role_permissions')->updateOrInsert(
            ['tenant_id'=>null,'role_code'=>$roleCode,'permission_code'=>$permissionCode],
            [
                'role_id'=>$roleId,'permission_id'=>$permissionId,'effect'=>$effect,
                'updated_at'=>now(),'created_at'=>now(),
            ]
        );
    }

    public function down(): void
    {
        if(!Schema::hasTable('roles')) return;
        $codes=array_keys(self::ROLES);
        DB::table('role_permissions')->whereIn('role_code',$codes)->delete();
        DB::table('roles')->whereNull('tenant_id')->whereIn('code',$codes)->delete();
        DB::table('roles')->whereNull('tenant_id')->whereIn('code',[
            'CUSTOMER_ADMIN','CUSTOMER_SITE_MANAGER','CUSTOMER_FINANCE','CUSTOMER_VIEWER'
        ])->update(['is_active'=>true,'updated_at'=>now()]);
    }
};
