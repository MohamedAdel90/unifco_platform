<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\{ApprovalRequest,Asset,CrmQuotation,FinancialDocument,PurchaseOrder,ServiceRequest,User,WorkOrder};
use App\Services\{AuthorizationService,ScopeService};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WorkflowWorkspaceController extends Controller
{
    private const ROLES=['MAINTENANCE_ENGINEER','MAINTENANCE_MANAGER','PROCUREMENT','TENDERS_CONTRACTS','FINANCE_MANAGER','PROJECT_MANAGER','CEO'];

    public function __invoke(Request $request, AuthorizationService $authorization, ScopeService $scopes): View
    {
        $user=$request->user();
        abort_unless($user,403);

        $roles=$authorization->roleCodes($user)
            ->push(strtoupper((string)$user->role))
            ->filter(fn($role)=>in_array($role,self::ROLES,true))
            ->unique()->values();
        abort_if($roles->isEmpty(),403);

        $requestedRole=strtoupper((string)$request->query('role'));
        if($requestedRole!=='') abort_unless($roles->contains($requestedRole),403);
        $legacyRole=strtoupper((string)$user->role);
        $role=$requestedRole!==''?$requestedRole:($roles->contains($legacyRole)?$legacyRole:$roles->first());

        $approvalLimit=$role==='MAINTENANCE_ENGINEER'?5:20;
        $approvalBase=$this->approvalBase($user,$role,$scopes);
        $pendingApprovals=(clone $approvalBase)->where('status','PENDING')->orderBy('due_at')->limit($approvalLimit)->get();
        $waitingApprovals=(clone $approvalBase)->where('status','WAITING')->count();
        $breachedApprovals=(clone $approvalBase)->where('status','PENDING')->whereNotNull('due_at')->where('due_at','<',now())->count();
        $recentDecisions=(clone $approvalBase)->whereIn('status',['APPROVED','REJECTED','RETURNED'])->latest('decided_at')->limit($role==='MAINTENANCE_ENGINEER'?5:8)->get();
        $serviceRequestIds=$pendingApprovals->where('entity_type',ServiceRequest::class)->pluck('entity_id');
        $serviceRequests=$this->scoped(ServiceRequest::class,$user,$scopes)->whereIn('id',$serviceRequestIds)->latest()->get()->keyBy('id');

        return view('workflow.workspace',[
            'user'=>$user,'role'=>$role,'availableRoles'=>$roles,'profile'=>$this->profile($role),
            'metrics'=>$this->metrics($role,$user,$scopes),'workQueue'=>$this->workQueue($role,$user,$scopes),
            'todayExecution'=>$this->todayExecution($role,$user,$scopes),'attention'=>$this->attention($role,$user,$scopes),
            'pendingApprovals'=>$pendingApprovals,'waitingApprovals'=>$waitingApprovals,'breachedApprovals'=>$breachedApprovals,
            'recentDecisions'=>$recentDecisions,'serviceRequests'=>$serviceRequests,
        ]);
    }

    private function approvalBase(User $user, string $role, ScopeService $scopes): Builder
    {
        $serviceRequestIds=$this->scoped(ServiceRequest::class,$user,$scopes)->pluck('id');
        return ApprovalRequest::query()
            ->where('tenant_id',$user->tenant_id)
            ->where('approval_role',$role)
            ->where(function($q) use($user){
                $q->where('assigned_user_id',$user->id)
                    ->orWhere(function($queue){$queue->whereNull('assigned_user_id')->where('routing_status','ROLE_QUEUE');});
            })
            ->where(function($q) use($serviceRequestIds){
                $q->where('entity_type','!=',ServiceRequest::class)
                    ->orWhere(function($service) use($serviceRequestIds){
                        $service->where('entity_type',ServiceRequest::class)->whereIn('entity_id',$serviceRequestIds);
                    });
            });
    }

    private function scoped(string $modelClass, User $user, ScopeService $scopes): Builder
    {
        $model=new $modelClass;
        $query=$modelClass::query()->where($model->qualifyColumn('tenant_id'),$user->tenant_id);
        return $scopes->apply($query,$user);
    }

    private function attention(string $role, User $user, ScopeService $scopes): array
    {
        if($role!=='MAINTENANCE_ENGINEER') return [];
        $approval=$this->approvalBase($user,$role,$scopes);
        $open=fn()=>clone $this->scoped(ServiceRequest::class,$user,$scopes)->whereNotIn('status',['CLOSED','REJECTED','CANCELLED']);
        return [
            ['key'=>'breached','label'=>'SLA Breached','value'=>(clone $approval)->where('status','PENDING')->whereNotNull('due_at')->where('due_at','<',now())->count(),'tone'=>'red'],
            ['key'=>'emergency','label'=>'Emergency Open','value'=>$open()->where('priority','EMERGENCY')->count(),'tone'=>'red'],
            ['key'=>'review','label'=>'Awaiting My Review','value'=>(clone $approval)->where('status','PENDING')->count(),'tone'=>'amber'],
            ['key'=>'workorder','label'=>'Work Orders In Progress','value'=>$this->scoped(WorkOrder::class,$user,$scopes)->whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->count(),'tone'=>'blue'],
        ];
    }

    private function metrics(string $role, User $user, ScopeService $scopes): array
    {
        $approval=$this->approvalBase($user,$role,$scopes);
        $pending=fn()=>(clone $approval)->where('status','PENDING')->count();
        $breached=fn()=>(clone $approval)->where('status','PENDING')->whereNotNull('due_at')->where('due_at','<',now())->count();
        $requests=fn()=>clone $this->scoped(ServiceRequest::class,$user,$scopes);
        $workOrders=fn()=>clone $this->scoped(WorkOrder::class,$user,$scopes);
        $purchaseOrders=fn()=>clone $this->scoped(PurchaseOrder::class,$user,$scopes);
        $quotations=fn()=>clone $this->scoped(CrmQuotation::class,$user,$scopes);
        $documents=fn()=>clone $this->scoped(FinancialDocument::class,$user,$scopes);

        return match($role){
            'MAINTENANCE_ENGINEER'=>[['label'=>'Awaiting My Review','value'=>$pending(),'hint'=>'Technical decisions requiring action'],['label'=>'SLA At Risk / Breached','value'=>$breached(),'hint'=>'Prioritize these first'],['label'=>'Emergency Open','value'=>$requests()->where('priority','EMERGENCY')->whereNotIn('status',['CLOSED','REJECTED','CANCELLED'])->count(),'hint'=>'Open emergency service requests'],['label'=>'Work Orders In Progress','value'=>$workOrders()->whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->count(),'hint'=>'Execution currently open']],
            'MAINTENANCE_MANAGER'=>[['label'=>'Manager Approvals','value'=>$pending()],['label'=>'Requests Under Review','value'=>$requests()->whereIn('workflow_stage',['MAINTENANCE_MANAGER_REVIEW','TECHNICAL_ASSESSMENT'])->count()],['label'=>'Open Work Orders','value'=>$workOrders()->whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->count()],['label'=>'SLA Breaches','value'=>$breached()]],
            'PROCUREMENT'=>[['label'=>'Procurement Approvals','value'=>$pending()],['label'=>'Requests Needing Procurement','value'=>$requests()->where('procurement_required',true)->whereNotIn('status',['CLOSED','REJECTED','CANCELLED'])->count()],['label'=>'Pending Purchase Orders','value'=>$purchaseOrders()->whereIn('status',['DRAFT','PENDING','PENDING_APPROVAL','SUBMITTED'])->count()],['label'=>'SLA Breaches','value'=>$breached()]],
            'TENDERS_CONTRACTS'=>[['label'=>'Commercial Approvals','value'=>$pending()],['label'=>'Draft Quotations','value'=>$quotations()->whereIn('status',['DRAFT','UNDER_REVIEW','REVISION_REQUESTED'])->count()],['label'=>'Commercial Ready Requests','value'=>$requests()->whereIn('workflow_stage',['COMMERCIAL_PREPARATION','COMMERCIAL_READY'])->count()],['label'=>'SLA Breaches','value'=>$breached()]],
            'FINANCE_MANAGER'=>[['label'=>'Finance Approvals','value'=>$pending()],['label'=>'Open Receivables','value'=>(float)$documents()->where('document_type','AR_INVOICE')->where('open_amount','>',0)->sum('open_amount'),'money'=>true],['label'=>'Long-Term Quotations','value'=>$quotations()->where('payment_terms_days','>',30)->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->count()],['label'=>'SLA Breaches','value'=>$breached()]],
            'PROJECT_MANAGER'=>[['label'=>'Execution Approvals','value'=>$pending()],['label'=>'Execution Review Requests','value'=>$requests()->where('workflow_stage','PROJECT_MANAGER_REVIEW')->count()],['label'=>'Open Work Orders','value'=>$workOrders()->whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->count()],['label'=>'SLA Breaches','value'=>$breached()]],
            'CEO'=>[['label'=>'Executive Approvals','value'=>$pending()],['label'=>'High Risk Quotations','value'=>$quotations()->where('risk_level','HIGH')->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->count()],['label'=>'High Value Quotations','value'=>$quotations()->where('amount','>',250000)->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->count()],['label'=>'Approval SLA Breaches','value'=>$breached()]],
        };
    }

    private function workQueue(string $role, User $user, ScopeService $scopes): Collection
    {
        if($role==='MAINTENANCE_ENGINEER'){
            $requests=$this->scoped(ServiceRequest::class,$user,$scopes)->whereNotIn('status',['CLOSED','REJECTED','CANCELLED'])->where(fn($q)=>$q->where('workflow_stage','TECHNICAL_REVIEW')->orWhere('priority','EMERGENCY'))->get();
            $assets=$this->scoped(Asset::class,$user,$scopes)->whereIn('id',$requests->pluck('asset_id')->filter())->with(['site','location'])->get()->keyBy('id');
            return $requests->sortBy(function($r){$breached=$r->current_stage_due_at && $r->current_stage_due_at->isPast(); return ($breached?-100:0)+match($r->priority){'EMERGENCY'=>0,'CRITICAL'=>10,'HIGH'=>20,'MEDIUM'=>30,default=>40};})->take(12)->values()->map(function($r)use($assets){
                $asset=$assets->get($r->asset_id); $due=$r->current_stage_due_at; $breached=$due&&$due->isPast(); $atRisk=$due&&!$breached&&now()->diffInMinutes($due)<=120; $emergency=$r->priority==='EMERGENCY'; $review=$r->workflow_stage==='TECHNICAL_REVIEW';
                $sla=$breached?'SLA BREACHED · '.$due->diffForHumans():($atRisk?'SLA AT RISK · '.$due->diffForHumans():($due?'SLA · '.$due->diffForHumans():'SLA NOT SET'));
                return ['type'=>'Service Request','request_no'=>$r->request_no,'subject'=>$r->subject,'company'=>$r->company_name,'request_type'=>str_replace('_',' ',$r->request_type),'priority'=>$r->priority,'eligibility'=>str_replace('_',' ',$r->eligibility),'state'=>str_replace('_',' ',$r->workflow_stage),'asset_code'=>$asset?->asset_code,'asset_name'=>$asset?->name,'site'=>$asset?->site?->name ?: $r->site_city,'location'=>$asset?->location?->name ?: $asset?->physical_location,'sla'=>$sla,'tone'=>$breached?'red':($atRisk?'amber':($emergency?'red':($review?'amber':'blue'))),'filter'=>$breached?'breached':($emergency?'emergency':'review'),'action'=>$review?'Review Request':'Inspect Emergency','url'=>route('workflow.approvals.index')];
            });
        }
        return match($role){
            'MAINTENANCE_MANAGER'=>$this->scoped(ServiceRequest::class,$user,$scopes)->whereIn('workflow_stage',['MAINTENANCE_MANAGER_REVIEW','TECHNICAL_ASSESSMENT'])->latest()->limit(8)->get()->map(fn($r)=>['type'=>'Technical Scope','title'=>$r->request_no.' · '.$r->subject,'meta'=>$r->company_name.' · '.$r->priority,'state'=>str_replace('_',' ',$r->workflow_stage),'url'=>route('workflow.approvals.index')]),
            'PROCUREMENT'=>$this->scoped(PurchaseOrder::class,$user,$scopes)->whereIn('status',['DRAFT','PENDING','PENDING_APPROVAL','SUBMITTED'])->latest()->limit(8)->get()->map(fn($po)=>['type'=>'Purchase Order','title'=>$po->po_number.' · '.$po->supplier_name,'meta'=>number_format((float)$po->total,2).' SAR','state'=>$po->status,'url'=>route('procurement.purchase-orders.index')]),
            'TENDERS_CONTRACTS'=>$this->scoped(CrmQuotation::class,$user,$scopes)->whereIn('status',['DRAFT','UNDER_REVIEW','REVISION_REQUESTED','SENT'])->latest('quotation_date')->limit(8)->get()->map(fn($q)=>['type'=>'Quotation','title'=>$q->quotation_no.' · R'.$q->revision_no,'meta'=>number_format((float)$q->amount,2).' '.$q->currency.' · Margin '.($q->margin_pct??'—').'%','state'=>str_replace('_',' ',$q->status),'url'=>route('modules.index','crm')]),
            'FINANCE_MANAGER'=>$this->scoped(CrmQuotation::class,$user,$scopes)->where('payment_terms_days','>',30)->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->latest('quotation_date')->limit(8)->get()->map(fn($q)=>['type'=>'Financial Terms','title'=>$q->quotation_no,'meta'=>number_format((float)$q->amount,2).' '.$q->currency.' · '.$q->payment_terms_days.' days','state'=>$q->risk_level,'url'=>route('finance.core.index')]),
            'PROJECT_MANAGER'=>$this->scoped(ServiceRequest::class,$user,$scopes)->whereIn('workflow_stage',['PROJECT_MANAGER_REVIEW','TECHNICIAN_ASSIGNMENT','EXECUTION'])->latest()->limit(8)->get()->map(fn($r)=>['type'=>'Execution','title'=>$r->request_no.' · '.$r->subject,'meta'=>$r->company_name.' · '.$r->priority,'state'=>str_replace('_',' ',$r->workflow_stage),'url'=>route('projects.projects.index')]),
            'CEO'=>$this->scoped(CrmQuotation::class,$user,$scopes)->where(fn($q)=>$q->where('amount','>',250000)->orWhere('risk_level','HIGH')->orWhere('margin_pct','<',10)->orWhere('payment_terms_days','>',90))->whereNotIn('status',['CUSTOMER_REJECTED','EXPIRED'])->latest('quotation_date')->limit(8)->get()->map(fn($q)=>['type'=>'Executive Exception','title'=>$q->quotation_no.' · '.number_format((float)$q->amount,2).' '.$q->currency,'meta'=>'Risk '.$q->risk_level.' · Margin '.($q->margin_pct??'—').'% · Terms '.($q->payment_terms_days??0).' days','state'=>$q->status,'url'=>route('workflow.approvals.index')]),
        };
    }

    private function todayExecution(string $role, User $user, ScopeService $scopes): Collection
    {
        if($role!=='MAINTENANCE_ENGINEER') return collect();
        return $this->scoped(WorkOrder::class,$user,$scopes)->with('asset')->whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->whereNotNull('planned_start')->whereDate('planned_start',today())->orderBy('planned_start')->limit(8)->get()->map(fn($wo)=>['time'=>$wo->planned_start->format('H:i'),'number'=>$wo->work_order_no,'asset'=>$wo->asset?->asset_code.' · '.$wo->asset?->name,'type'=>str_replace('_',' ',$wo->maintenance_type),'priority'=>$wo->priority,'status'=>str_replace('_',' ',$wo->status),'url'=>'/eam/work-orders']);
    }

    private function profile(string $role): array
    {
        return match($role){
            'MAINTENANCE_ENGINEER'=>['title'=>'Maintenance Engineer Workspace','subtitle'=>'Prioritize technical decisions, emergencies and work-order execution from one operational command center.','accent'=>'Technical Review & Execution Control','queue'=>'Prioritized Technical Work Queue'],
            'MAINTENANCE_MANAGER'=>['title'=>'Maintenance Manager Workspace','subtitle'=>'Approve technical scope, control workload and manage operational SLA exceptions.','accent'=>'Operational Approval','queue'=>'Operational Review Queue'],
            'PROCUREMENT'=>['title'=>'Procurement Workspace','subtitle'=>'Validate external cost, supplier requirements and purchase-order readiness.','accent'=>'Cost Validation','queue'=>'Procurement Action Queue'],
            'TENDERS_CONTRACTS'=>['title'=>'Tenders & Contracts Workspace','subtitle'=>'Prepare quotations, manage revisions, commercial terms and contract governance.','accent'=>'Commercial Review','queue'=>'Commercial Queue'],
            'FINANCE_MANAGER'=>['title'=>'Finance Workspace','subtitle'=>'Review payment terms, receivables, credit exposure and financial exceptions.','accent'=>'Financial Approval','queue'=>'Financial Review Queue'],
            'PROJECT_MANAGER'=>['title'=>'Project Manager Workspace','subtitle'=>'Validate execution feasibility, capacity, scheduling and delivery risk.','accent'=>'Execution Approval','queue'=>'Execution Queue'],
            'CEO'=>['title'=>'CEO Executive Workspace','subtitle'=>'Decide high-value, high-risk and policy exception approvals only.','accent'=>'Executive Approval','queue'=>'Executive Exception Queue'],
        };
    }
}
