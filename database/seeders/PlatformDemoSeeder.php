<?php

namespace Database\Seeders;

use App\Models\{ApprovalRequest,Document,Organization,PurchaseOrder,PlatformNotification,Tenant,User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'UNIFCO')->firstOrFail();
        $org = Organization::where('tenant_id', $tenant->id)->where('code', 'HQ')->firstOrFail();
        $admin = User::where('tenant_id', $tenant->id)->where('email', 'admin@unifco.local')->firstOrFail();

        if (PlatformNotification::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        PlatformNotification::create([
            'tenant_id'=>$tenant->id,'user_id'=>$admin->id,'type'=>'INFO',
            'title'=>'Welcome to UNIFCO Platform','message'=>'Your unified business workspace is ready.',
            'action_url'=>route('dashboard'),
        ]);
        PlatformNotification::create([
            'tenant_id'=>$tenant->id,'user_id'=>$admin->id,'type'=>'WARNING',
            'title'=>'Approval pending','message'=>'A purchase order is awaiting your approval.',
            'action_url'=>route('workflow.approvals.index'),'read_at'=>'2026-08-16 09:00:00',
        ]);

        Document::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'uploaded_by'=>$admin->id,
            'document_no'=>'DOC-0001','title'=>'UNIFCO Operations Manual','original_name'=>'ops-manual.pdf',
            'storage_path'=>'documents/ops-manual.pdf','mime_type'=>'application/pdf',
            'size_bytes'=>524288,'status'=>'ACTIVE',
        ]);

        $po = PurchaseOrder::where('tenant_id',$tenant->id)->where('status','APPROVED')->first();
        ApprovalRequest::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,
            'entity_type'=>$po ? 'PurchaseOrder' : 'Journal',
            'entity_id'=>$po?->id ?? 1,
            'action'=>'APPROVE','requested_by'=>$admin->id,'status'=>'PENDING',
        ]);

        $permissions = [
            'dashboard.view','audit.read','reporting.executive.read','workflow.approval.read','workflow.approval.decide',
            'finance.journal.read','finance.journal.create','finance.journal.post',
            'hr.employee.read','hr.employee.manage',
            'procurement.po.read','procurement.po.approve',
            'inventory.stock.read','inventory.stock.move',
            'crm.customer.read','crm.customer.manage',
            'projects.project.read','projects.project.manage',
            'manufacturing.production.read','manufacturing.production.manage',
            'maintenance.work_order.read','maintenance.work_order.manage',
            'eam.asset.read','eam.asset.manage','eam.asset.capitalize',
            'documents.read','documents.manage','security.permission.manage',
        ];
        foreach ($permissions as $permission) {
            DB::table('role_permissions')->updateOrInsert(
                ['tenant_id'=>$tenant->id,'role_code'=>'ADMIN','permission_code'=>$permission],
                ['created_at'=>now(),'updated_at'=>now()],
            );
        }

        DB::table('api_tokens')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$admin->id,'name'=>'Default Integration',
            'token_hash'=>hash('sha256','unifco-demo-token'),
            'abilities'=>json_encode(['*']),
            'created_at'=>now(),'updated_at'=>now(),
        ]);
    }
}