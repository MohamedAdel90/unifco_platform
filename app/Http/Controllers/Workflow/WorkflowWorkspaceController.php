<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\{ApprovalRequest,CrmQuotation,FinancialDocument,PurchaseOrder,ServiceRequest,WorkOrder};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkflowWorkspaceController extends Controller
{
    private const ROLES = [
        'MAINTENANCE_ENGINEER','MAINTENANCE_MANAGER','PROCUREMENT','TENDERS_CONTRACTS','FINANCE','PROJECT_MANAGER','CEO',
    ];

    public function __invoke(Request $request): View
    {
        $user=$request->user();
        abort_unless($user && in_array($user->role,self::ROLES,true),403);

        $role=$user->role;
        $pendingApprovals=ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->orderBy('due_at')->limit(20)->get();
        $waitingApprovals=ApprovalRequest::where('approval_role',$role)->where('status','WAITING')->count();
        $breachedApprovals=ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->whereNotNull('due_at')->where('due_at','<',now())->count();
        $recentDecisions=ApprovalRequest::where('approval_role',$role)->whereIn('status',['APPROVED','REJECTED','RETURNED'])->latest('decided_at')->limit(8)->get();

        $serviceRequestIds=$pendingApprovals->where('entity_type',ServiceRequest::class)->pluck('entity_id');
        $serviceRequests=ServiceRequest::whereIn('id',$serviceRequestIds)->latest()->get()->keyBy('id');

        $metrics=$this->metrics($role);
        $profile=$this->profile($role);

        return view('workflow.workspace',compact(
            'user','role','profile','metrics','pendingApprovals','waitingApprovals','breachedApprovals','recentDecisions','serviceRequests'
        ));
    }

    private function metrics(string $role): array
    {
        return match($role){
            'MAINTENANCE_ENGINEER'=>[
                ['label'=>'Technical Reviews','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->count()],
                ['label'=>'Open Work Orders','value'=>WorkOrder::whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->count()],
                ['label'=>'Emergency Requests','value'=>ServiceRequest::where('priority','EMERGENCY')->whereNotIn('status',['CLOSED','REJECTED','CANCELLED'])->count()],
                ['label'=>'Technical SLA Breaches','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->where('due_at','<',now())->count()],
            ],
            'MAINTENANCE_MANAGER'=>[
                ['label'=>'Manager Approvals','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->count()],
                ['label'=>'Requests Under Review','value'=>ServiceRequest::whereIn('workflow_stage',['MAINTENANCE_MANAGER','TECHNICAL_SCOPE_APPROVAL'])->count()],
                ['label'=>'Open Work Orders','value'=>WorkOrder::whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->count()],
                ['label'=>'SLA Breaches','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->where('due_at','<',now())->count()],
            ],
            'PROCUREMENT'=>[
                ['label'=>'Procurement Approvals','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->count()],
                ['label'=>'Requests Needing Procurement','value'=>ServiceRequest::where('procurement_required',true)->whereNotIn('status',['CLOSED','REJECTED','CANCELLED'])->count()],
                ['label'=>'Pending Purchase Orders','value'=>PurchaseOrder::whereIn('status',['DRAFT','PENDING','PENDING_APPROVAL','SUBMITTED'])->count()],
                ['label'=>'SLA Breaches','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->where('due_at','<',now())->count()],
            ],
            'TENDERS_CONTRACTS'=>[
                ['label'=>'Commercial Approvals','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->count()],
                ['label'=>'Draft Quotations','value'=>CrmQuotation::whereIn('status',['DRAFT','UNDER_REVIEW','REVISION_REQUESTED'])->count()],
                ['label'=>'Commercial Ready Requests','value'=>ServiceRequest::whereIn('workflow_stage',['COMMERCIAL_PREPARATION','COMMERCIAL_READY'])->count()],
                ['label'=>'SLA Breaches','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->where('due_at','<',now())->count()],
            ],
            'FINANCE'=>[
                ['label'=>'Finance Approvals','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->count()],
                ['label'=>'Open Receivables','value'=>(float)FinancialDocument::where('document_type','AR_INVOICE')->where('open_amount','>',0)->sum('open_amount'),'money'=>true],
                ['label'=>'Long-Term Quotations','value'=>CrmQuotation::where('payment_terms_days','>',30)->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->count()],
                ['label'=>'SLA Breaches','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->where('due_at','<',now())->count()],
            ],
            'PROJECT_MANAGER'=>[
                ['label'=>'Execution Approvals','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->count()],
                ['label'=>'Execution Review Requests','value'=>ServiceRequest::where('workflow_stage','EXECUTION_FEASIBILITY')->count()],
                ['label'=>'Open Work Orders','value'=>WorkOrder::whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->count()],
                ['label'=>'SLA Breaches','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->where('due_at','<',now())->count()],
            ],
            'CEO'=>[
                ['label'=>'Executive Approvals','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->count()],
                ['label'=>'High Risk Quotations','value'=>CrmQuotation::where('risk_level','HIGH')->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->count()],
                ['label'=>'High Value Quotations','value'=>CrmQuotation::where('amount','>',250000)->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->count()],
                ['label'=>'Approval SLA Breaches','value'=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->where('due_at','<',now())->count()],
            ],
        };
    }

    private function profile(string $role): array
    {
        return match($role){
            'MAINTENANCE_ENGINEER'=>['title'=>'Maintenance Engineer Workspace','subtitle'=>'Technical qualification, assets, emergency response and work-order readiness.','accent'=>'Technical Review'],
            'MAINTENANCE_MANAGER'=>['title'=>'Maintenance Manager Workspace','subtitle'=>'Technical scope approval, team workload, SLA and operational exceptions.','accent'=>'Operational Approval'],
            'PROCUREMENT'=>['title'=>'Procurement Workspace','subtitle'=>'External cost validation, supplier requirements and purchase-order actions.','accent'=>'Cost Validation'],
            'TENDERS_CONTRACTS'=>['title'=>'Tenders & Contracts Workspace','subtitle'=>'Commercial preparation, quotation revisions, terms and contract governance.','accent'=>'Commercial Review'],
            'FINANCE'=>['title'=>'Finance Workspace','subtitle'=>'Payment terms, receivables, credit exposure and financial exceptions.','accent'=>'Financial Approval'],
            'PROJECT_MANAGER'=>['title'=>'Project Manager Workspace','subtitle'=>'Execution feasibility, capacity, scheduling and delivery risk.','accent'=>'Execution Approval'],
            'CEO'=>['title'=>'CEO Executive Workspace','subtitle'=>'High-value, high-risk and exception approvals only.','accent'=>'Executive Approval'],
        };
    }
}
