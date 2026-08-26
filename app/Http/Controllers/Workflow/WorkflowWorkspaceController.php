<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\{ApprovalRequest,CrmQuotation,FinancialDocument,PurchaseOrder,ServiceRequest,WorkOrder};
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WorkflowWorkspaceController extends Controller
{
    private const ROLES=['MAINTENANCE_ENGINEER','MAINTENANCE_MANAGER','PROCUREMENT','TENDERS_CONTRACTS','FINANCE','PROJECT_MANAGER','CEO'];

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

        return view('workflow.workspace',[
            'user'=>$user,
            'role'=>$role,
            'profile'=>$this->profile($role),
            'metrics'=>$this->metrics($role),
            'workQueue'=>$this->workQueue($role),
            'pendingApprovals'=>$pendingApprovals,
            'waitingApprovals'=>$waitingApprovals,
            'breachedApprovals'=>$breachedApprovals,
            'recentDecisions'=>$recentDecisions,
            'serviceRequests'=>$serviceRequests,
        ]);
    }

    private function metrics(string $role): array
    {
        $pending=fn()=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->count();
        $breached=fn()=>ApprovalRequest::where('approval_role',$role)->where('status','PENDING')->whereNotNull('due_at')->where('due_at','<',now())->count();

        return match($role){
            'MAINTENANCE_ENGINEER'=>[
                ['label'=>'Technical Reviews','value'=>$pending()],
                ['label'=>'Open Work Orders','value'=>WorkOrder::whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->count()],
                ['label'=>'Emergency Requests','value'=>ServiceRequest::where('priority','EMERGENCY')->whereNotIn('status',['CLOSED','REJECTED','CANCELLED'])->count()],
                ['label'=>'Technical SLA Breaches','value'=>$breached()],
            ],
            'MAINTENANCE_MANAGER'=>[
                ['label'=>'Manager Approvals','value'=>$pending()],
                ['label'=>'Requests Under Review','value'=>ServiceRequest::whereIn('workflow_stage',['MAINTENANCE_MANAGER','TECHNICAL_SCOPE_APPROVAL'])->count()],
                ['label'=>'Open Work Orders','value'=>WorkOrder::whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->count()],
                ['label'=>'SLA Breaches','value'=>$breached()],
            ],
            'PROCUREMENT'=>[
                ['label'=>'Procurement Approvals','value'=>$pending()],
                ['label'=>'Requests Needing Procurement','value'=>ServiceRequest::where('procurement_required',true)->whereNotIn('status',['CLOSED','REJECTED','CANCELLED'])->count()],
                ['label'=>'Pending Purchase Orders','value'=>PurchaseOrder::whereIn('status',['DRAFT','PENDING','PENDING_APPROVAL','SUBMITTED'])->count()],
                ['label'=>'SLA Breaches','value'=>$breached()],
            ],
            'TENDERS_CONTRACTS'=>[
                ['label'=>'Commercial Approvals','value'=>$pending()],
                ['label'=>'Draft Quotations','value'=>CrmQuotation::whereIn('status',['DRAFT','UNDER_REVIEW','REVISION_REQUESTED'])->count()],
                ['label'=>'Commercial Ready Requests','value'=>ServiceRequest::whereIn('workflow_stage',['COMMERCIAL_PREPARATION','COMMERCIAL_READY'])->count()],
                ['label'=>'SLA Breaches','value'=>$breached()],
            ],
            'FINANCE'=>[
                ['label'=>'Finance Approvals','value'=>$pending()],
                ['label'=>'Open Receivables','value'=>(float)FinancialDocument::where('document_type','AR_INVOICE')->where('open_amount','>',0)->sum('open_amount'),'money'=>true],
                ['label'=>'Long-Term Quotations','value'=>CrmQuotation::where('payment_terms_days','>',30)->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->count()],
                ['label'=>'SLA Breaches','value'=>$breached()],
            ],
            'PROJECT_MANAGER'=>[
                ['label'=>'Execution Approvals','value'=>$pending()],
                ['label'=>'Execution Review Requests','value'=>ServiceRequest::where('workflow_stage','EXECUTION_FEASIBILITY')->count()],
                ['label'=>'Open Work Orders','value'=>WorkOrder::whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->count()],
                ['label'=>'SLA Breaches','value'=>$breached()],
            ],
            'CEO'=>[
                ['label'=>'Executive Approvals','value'=>$pending()],
                ['label'=>'High Risk Quotations','value'=>CrmQuotation::where('risk_level','HIGH')->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->count()],
                ['label'=>'High Value Quotations','value'=>CrmQuotation::where('amount','>',250000)->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->count()],
                ['label'=>'Approval SLA Breaches','value'=>$breached()],
            ],
        };
    }

    private function workQueue(string $role): Collection
    {
        return match($role){
            'MAINTENANCE_ENGINEER'=>ServiceRequest::whereNotIn('status',['CLOSED','REJECTED','CANCELLED'])
                ->where(fn($q)=>$q->where('workflow_stage','TECHNICAL_REVIEW')->orWhere('priority','EMERGENCY'))
                ->latest()->limit(8)->get()->map(fn($r)=>[
                    'type'=>'Service Request','title'=>$r->request_no.' · '.$r->subject,
                    'meta'=>str_replace('_',' ',$r->request_type).' · '.$r->priority.' · '.str_replace('_',' ',$r->eligibility),
                    'state'=>str_replace('_',' ',$r->workflow_stage),'url'=>route('workflow.approvals.index'),
                ]),
            'MAINTENANCE_MANAGER'=>ServiceRequest::whereIn('workflow_stage',['MAINTENANCE_MANAGER','TECHNICAL_SCOPE_APPROVAL'])->latest()->limit(8)->get()->map(fn($r)=>[
                    'type'=>'Technical Scope','title'=>$r->request_no.' · '.$r->subject,'meta'=>$r->company_name.' · '.$r->priority,
                    'state'=>str_replace('_',' ',$r->workflow_stage),'url'=>route('workflow.approvals.index'),
                ]),
            'PROCUREMENT'=>PurchaseOrder::whereIn('status',['DRAFT','PENDING','PENDING_APPROVAL','SUBMITTED'])->latest()->limit(8)->get()->map(fn($po)=>[
                    'type'=>'Purchase Order','title'=>$po->po_number.' · '.$po->supplier_name,'meta'=>number_format((float)$po->total,2).' SAR',
                    'state'=>$po->status,'url'=>route('procurement.purchase-orders.index'),
                ]),
            'TENDERS_CONTRACTS'=>CrmQuotation::whereIn('status',['DRAFT','UNDER_REVIEW','REVISION_REQUESTED','SENT'])->latest('quotation_date')->limit(8)->get()->map(fn($q)=>[
                    'type'=>'Quotation','title'=>$q->quotation_no.' · R'.$q->revision_no,'meta'=>number_format((float)$q->amount,2).' '.$q->currency.' · Margin '.($q->margin_pct??'—').'%',
                    'state'=>str_replace('_',' ',$q->status),'url'=>route('modules.index','crm'),
                ]),
            'FINANCE'=>CrmQuotation::where('payment_terms_days','>',30)->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->latest('quotation_date')->limit(8)->get()->map(fn($q)=>[
                    'type'=>'Financial Terms','title'=>$q->quotation_no,'meta'=>number_format((float)$q->amount,2).' '.$q->currency.' · '.$q->payment_terms_days.' days',
                    'state'=>$q->risk_level,'url'=>route('finance.core.index'),
                ]),
            'PROJECT_MANAGER'=>ServiceRequest::whereIn('workflow_stage',['EXECUTION_FEASIBILITY','QUALIFIED','PLANNING'])->latest()->limit(8)->get()->map(fn($r)=>[
                    'type'=>'Execution','title'=>$r->request_no.' · '.$r->subject,'meta'=>$r->company_name.' · '.$r->priority,
                    'state'=>str_replace('_',' ',$r->workflow_stage),'url'=>route('projects.projects.index'),
                ]),
            'CEO'=>CrmQuotation::where(fn($q)=>$q->where('amount','>',250000)->orWhere('risk_level','HIGH')->orWhere('margin_pct','<',10)->orWhere('payment_terms_days','>',90))
                ->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->latest('quotation_date')->limit(8)->get()->map(fn($q)=>[
                    'type'=>'Executive Exception','title'=>$q->quotation_no.' · '.number_format((float)$q->amount,2).' '.$q->currency,
                    'meta'=>'Risk '.$q->risk_level.' · Margin '.($q->margin_pct??'—').'% · Terms '.($q->payment_terms_days??0).' days',
                    'state'=>$q->status,'url'=>route('workflow.approvals.index'),
                ]),
        };
    }

    private function profile(string $role): array
    {
        return match($role){
            'MAINTENANCE_ENGINEER'=>['title'=>'Maintenance Engineer Workspace','subtitle'=>'Qualify requests technically, inspect asset context and prepare work-order execution.','accent'=>'Technical Review','queue'=>'Technical Work Queue'],
            'MAINTENANCE_MANAGER'=>['title'=>'Maintenance Manager Workspace','subtitle'=>'Approve technical scope, control workload and manage operational SLA exceptions.','accent'=>'Operational Approval','queue'=>'Operational Review Queue'],
            'PROCUREMENT'=>['title'=>'Procurement Workspace','subtitle'=>'Validate external cost, supplier requirements and purchase-order readiness.','accent'=>'Cost Validation','queue'=>'Procurement Action Queue'],
            'TENDERS_CONTRACTS'=>['title'=>'Tenders & Contracts Workspace','subtitle'=>'Prepare quotations, manage revisions, commercial terms and contract governance.','accent'=>'Commercial Review','queue'=>'Commercial Queue'],
            'FINANCE'=>['title'=>'Finance Workspace','subtitle'=>'Review payment terms, receivables, credit exposure and financial exceptions.','accent'=>'Financial Approval','queue'=>'Financial Review Queue'],
            'PROJECT_MANAGER'=>['title'=>'Project Manager Workspace','subtitle'=>'Validate execution feasibility, capacity, scheduling and delivery risk.','accent'=>'Execution Approval','queue'=>'Execution Queue'],
            'CEO'=>['title'=>'CEO Executive Workspace','subtitle'=>'Decide high-value, high-risk and policy exception approvals only.','accent'=>'Executive Approval','queue'=>'Executive Exception Queue'],
        };
    }
}
