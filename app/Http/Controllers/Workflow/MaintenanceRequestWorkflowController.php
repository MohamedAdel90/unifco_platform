<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\{ApprovalRequest,CrmQuotation,Project,ProjectUserAssignment,ServiceRequest,User};
use App\Services\{AuthorizationService,MaintenanceRequestTransitionService,RequestStageOwnerService,ScopeService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class MaintenanceRequestWorkflowController extends Controller
{
    public function show(Request $request, ServiceRequest $serviceRequest, AuthorizationService $authorization, ScopeService $scopes, RequestStageOwnerService $owners): View
    {
        $user = $request->user();
        abort_unless((int) $serviceRequest->tenant_id === (int) $user->tenant_id, 404);
        if ($user->role === 'CUSTOMER') {
            abort_unless((int) $user->customer_id === (int) $serviceRequest->customer_id, 404);
        } else {
            $visible = $scopes->apply(ServiceRequest::query()->where('tenant_id', $user->tenant_id)->whereKey($serviceRequest->id), $user)->exists();
            abort_unless($visible, 404);
        }

        $step = ApprovalRequest::query()
            ->where('tenant_id', $serviceRequest->tenant_id)
            ->whereIn('entity_type', [ServiceRequest::class,'service_request'])
            ->where('entity_id', $serviceRequest->id)
            ->where('action', $serviceRequest->workflow_stage)
            ->where('status', 'PENDING')
            ->first();
        $roles = $authorization->roleCodes($user)->push(strtoupper((string) $user->role))->filter()->unique();
        $canAct = $step
            && $roles->contains(strtoupper((string) $step->approval_role))
            && $step->routing_status !== 'NEEDS_ASSIGNMENT'
            && !($this->projectRequiredForQuotation($serviceRequest,$step) && !$serviceRequest->project_id)
            && (!$step->assigned_user_id || (int)$step->assigned_user_id === (int)$user->id);
        if (in_array($serviceRequest->workflow_stage, ['EXECUTION','SITE_VISIT'], true)) $canAct = $canAct && (int) $serviceRequest->assigned_engineer_id === (int) $user->id;

        $technicianIds=$serviceRequest->project_id
            ? ProjectUserAssignment::query()
                ->where('tenant_id',$user->tenant_id)->where('project_id',$serviceRequest->project_id)
                ->where('status','ACTIVE')->whereIn('project_role',['TECHNICIAN','MAINTENANCE_ENGINEER'])
                ->where(fn($q)=>$q->whereNull('starts_on')->orWhere('starts_on','<=',today()))
                ->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',today()))
                ->pluck('user_id')
            : null;
        $technicians = User::query()->where('tenant_id', $user->tenant_id)
            ->whereIn('status', ['ACTIVE','ENABLED'])
            ->where(function($q){
                $q->whereIn('role',['TECHNICIAN','MAINTENANCE_ENGINEER'])
                  ->orWhereHas('activeRoles',fn($r)=>$r->whereIn('roles.code',['TECHNICIAN','MAINTENANCE_ENGINEER']));
            })
            ->when($technicianIds!==null,fn($q)=>$q->whereIn('id',$technicianIds))
            ->orderBy('name')->get(['id','name','role']);

        $projectOptions=Project::query()
            ->where('tenant_id',$user->tenant_id)
            ->where('status','ACTIVE')
            ->where('customer_id',$serviceRequest->customer_id ?? 0)
            ->orderBy('project_no')->get(['id','project_no','name','customer_id']);

        $canAssignProject = $step
            && !$serviceRequest->project_id
            && $this->projectRequiredForQuotation($serviceRequest,$step)
            && $authorization->allows($user,'service_requests.assign',$serviceRequest);
        $canAssignOwner = $step
            && !$canAssignProject
            && $step->routing_status === 'NEEDS_ASSIGNMENT'
            && $authorization->allows($user,'service_requests.assign',$serviceRequest);
        $ownerCandidates = $canAssignOwner
            ? $owners->candidates($serviceRequest,(string)$step->approval_role)
            : collect();

        $quotation = $serviceRequest->quotation_id
            ? CrmQuotation::query()->where('tenant_id',$user->tenant_id)->where('customer_id',$serviceRequest->customer_id)->find($serviceRequest->quotation_id)
            : null;

        return view('workflow.maintenance-request', compact('serviceRequest','step','canAct','technicians','projectOptions','canAssignProject','canAssignOwner','ownerCandidates','quotation'));
    }

    public function assignProject(Request $request, ServiceRequest $serviceRequest, AuthorizationService $authorization, ScopeService $scopes, RequestStageOwnerService $owners): RedirectResponse
    {
        $user=$request->user();
        abort_unless((int)$serviceRequest->tenant_id===(int)$user->tenant_id,404);
        $authorization->authorize($user,'service_requests.assign',$serviceRequest);
        abort_unless($scopes->apply(
            ServiceRequest::query()->where('tenant_id',$user->tenant_id)->whereKey($serviceRequest->id),
            $user
        )->exists(),403,'Request is outside your access scope.');

        $data=$request->validate(['project_id'=>['required','integer']]);
        return DB::transaction(function () use ($serviceRequest,$user,$data,$owners): RedirectResponse {
            $requestRecord=ServiceRequest::query()->where('tenant_id',$user->tenant_id)
                ->lockForUpdate()->findOrFail($serviceRequest->id);
            $step=ApprovalRequest::query()
                ->where('tenant_id',$requestRecord->tenant_id)
                ->whereIn('entity_type',[ServiceRequest::class,'service_request'])
                ->where('entity_id',$requestRecord->id)
                ->where('action',$requestRecord->workflow_stage)
                ->where('status','PENDING')->firstOrFail();
            abort_unless(!$requestRecord->project_id && $this->projectRequiredForQuotation($requestRecord,$step),
                422,'Project selection is not available at this stage.');
            $project=Project::query()->where('tenant_id',$user->tenant_id)
                ->where('customer_id',$requestRecord->customer_id)
                ->where('status','ACTIVE')->findOrFail($data['project_id']);
            $managerCount=ProjectUserAssignment::query()
                ->where('tenant_id',$user->tenant_id)->where('project_id',$project->id)
                ->where('project_role','PROJECT_MANAGER')->where('status','ACTIVE')
                ->where(fn($q)=>$q->whereNull('starts_on')->orWhere('starts_on','<=',today()))
                ->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',today()))
                ->distinct()->count('user_id');
            if($managerCount!==1){
                return back()->withErrors(['project_id'=>'The selected project needs one active Project Manager assignment before routing.'])->withInput();
            }
            $requestRecord->update(['project_id'=>$project->id]);
            $owners->refresh($requestRecord->fresh());
            return back()->with('status','Request linked to the responsible project and stage owner recalculated.');
        });
    }

    private function projectRequiredForQuotation(ServiceRequest $request, ?ApprovalRequest $step): bool
    {
        return $step
            && in_array((string)$request->workflow_key,['QUOTATION','SPARE_PARTS_QUOTATION','TECHNICAL_VISIT'],true)
            && in_array((string)$step->approval_role,['PROJECT_MANAGER','MAINTENANCE_MANAGER','MAINTENANCE_ENGINEER','TECHNICAL_SUPERVISOR','TECHNICIAN','QUALITY','HSE'],true);
    }


    public function saveQuotationPricing(Request $request, ServiceRequest $serviceRequest, AuthorizationService $authorization, ScopeService $scopes): RedirectResponse
    {
        $user = $request->user();
        abort_unless((int)$serviceRequest->tenant_id === (int)$user->tenant_id, 404);
        abort_unless($scopes->apply(ServiceRequest::query()->where('tenant_id',$user->tenant_id)->whereKey($serviceRequest->id),$user)->exists(), 403);
        $roles = $authorization->roleCodes($user)->push(strtoupper((string)$user->role))->filter()->unique();
        $pricingRole = $serviceRequest->workflow_key === 'TECHNICAL_VISIT' && $serviceRequest->workflow_stage === 'PRICING'
            ? 'SALES' : ($serviceRequest->workflow_key === 'MAINTENANCE_CONTRACT_QUOTATION' && $serviceRequest->workflow_stage === 'FINANCE_REVIEW' ? 'FINANCE_MANAGER' : 'TENDERS_CONTRACTS');
        abort_unless($roles->contains($pricingRole), 403);

        $data = $request->validate([
            'cost_amount' => ['required','numeric','gte:0'],
            'amount' => ['required','numeric','gt:0'],
            'pricing_basis' => ['required','string','max:2000'],
            'payment_terms_days' => [$serviceRequest->workflow_key === 'MAINTENANCE_CONTRACT_QUOTATION' ? 'required' : 'nullable','integer','min:0','max:365'],
        ]);

        DB::transaction(function () use ($serviceRequest,$user,$data,$pricingRole): void {
            $serviceRequest = ServiceRequest::query()->where('tenant_id',$user->tenant_id)->lockForUpdate()->findOrFail($serviceRequest->id);
            abort_unless(
                ($serviceRequest->workflow_key === 'SPARE_PARTS_QUOTATION' && $serviceRequest->workflow_stage === 'CONTRACT_REVIEW')
                || ($serviceRequest->workflow_key === 'TECHNICAL_VISIT' && $serviceRequest->workflow_stage === 'PRICING')
                || ($serviceRequest->workflow_key === 'MAINTENANCE_CONTRACT_QUOTATION' && $serviceRequest->workflow_stage === 'FINANCE_REVIEW'), 422);
            $step = ApprovalRequest::query()->where('tenant_id',$user->tenant_id)
                ->whereIn('entity_type',[ServiceRequest::class,'service_request'])->where('entity_id',$serviceRequest->id)
                ->where('action',$serviceRequest->workflow_stage)->where('status','PENDING')->lockForUpdate()->firstOrFail();
            abort_unless($step->approval_role === $pricingRole
                && $step->routing_status !== 'NEEDS_ASSIGNMENT'
                && (!$step->assigned_user_id || (int)$step->assigned_user_id === (int)$user->id), 403);

            $quotation = CrmQuotation::query()->where('tenant_id',$user->tenant_id)
                ->where('customer_id',$serviceRequest->customer_id)
                ->whereKey($serviceRequest->quotation_id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($quotation->status,['DRAFT','UNDER_REVIEW','REVISION_REQUESTED'],true), 422);
            $quotation->update([
                'cost_amount'=>$data['cost_amount'],
                'amount'=>$data['amount'],
                'margin_pct'=>round(((float)$data['amount']-(float)$data['cost_amount'])*100/(float)$data['amount'],2),
                'currency'=>'SAR',
                ...($pricingRole === 'FINANCE_MANAGER' ? ['payment_terms_days'=>$data['payment_terms_days']] : []),
            ]);
            $context = (array)($serviceRequest->workflow_context ?? []);
            $context['quotation_pricing'] = [
                'quotation_id'=>$quotation->id,
                'basis'=>$data['pricing_basis'],
                'estimated'=>true,
                ...($pricingRole === 'FINANCE_MANAGER' ? ['payment_terms_days'=>(int)$data['payment_terms_days']] : []),
                'recorded_by'=>$user->id,
                'recorded_at'=>now()->toIso8601String(),
            ];
            $serviceRequest->update(['workflow_context'=>$context]);
        });

        return back()->with('status','Estimated quotation pricing saved. The current commercial review can continue.');
    }

    public function assignStageOwner(Request $request, ServiceRequest $serviceRequest, AuthorizationService $authorization, ScopeService $scopes, RequestStageOwnerService $owners): RedirectResponse
    {
        $user=$request->user();
        abort_unless((int)$serviceRequest->tenant_id===(int)$user->tenant_id,404);
        $authorization->authorize($user,'service_requests.assign',$serviceRequest);

        $visible=$scopes->apply(
            ServiceRequest::query()->where('tenant_id',$user->tenant_id)->whereKey($serviceRequest->id),
            $user
        )->exists();
        abort_unless($visible,403,'Request is outside your access scope.');

        $step=ApprovalRequest::query()
            ->where('tenant_id',$serviceRequest->tenant_id)
            ->whereIn('entity_type',[ServiceRequest::class,'service_request'])
            ->where('entity_id',$serviceRequest->id)
            ->where('action',$serviceRequest->workflow_stage)
            ->where('status','PENDING')
            ->firstOrFail();
        abort_unless($serviceRequest->project_id || !$this->projectRequiredForQuotation($serviceRequest,$step),
            422,'Select the responsible customer project before assigning a stage owner.');

        $data=$request->validate(['owner_user_id'=>['required','integer']]);
        $candidate=$owners->candidates($serviceRequest,(string)$step->approval_role)
            ->firstWhere('id',(int)$data['owner_user_id']);
        abort_unless($candidate,422,'Selected user is not eligible for this project, role, or workflow stage.');

        $step->update([
            'assigned_user_id'=>$candidate->id,
            'routing_status'=>'ASSIGNED',
        ]);

        return back()->with('status','Workflow stage owner assigned to '.$candidate->name.'.');
    }

    public function triage(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions, RequestStageOwnerService $owners): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['nullable','integer'],
            'notes' => ['nullable','string','max:2000'],
        ]);

        $candidateProjects=Project::query()
            ->where('tenant_id',$request->user()->tenant_id)
            ->where('status','ACTIVE')
            ->when($serviceRequest->customer_id,fn($q)=>$q->where('customer_id',$serviceRequest->customer_id))
            ->get(['id']);

        $projectId=$data['project_id'] ?? $serviceRequest->project_id;
        if(!$projectId && $candidateProjects->count()===1) $projectId=$candidateProjects->first()->id;
        if(!$projectId){
            return back()->withErrors(['project_id'=>'Create or select an active project for this customer before routing the request.'])->withInput();
        }

        return DB::transaction(function () use ($projectId, $request, $serviceRequest, $transitions, $owners, $data): RedirectResponse {
        if($projectId){
            $project=Project::query()
                ->where('tenant_id',$request->user()->tenant_id)
                ->where('status','ACTIVE')
                ->when($serviceRequest->customer_id,fn($q)=>$q->where('customer_id',$serviceRequest->customer_id))
                ->findOrFail($projectId);

            $hasProjectManager=ProjectUserAssignment::query()
                ->where('tenant_id',$request->user()->tenant_id)
                ->where('project_id',$project->id)
                ->where('project_role','PROJECT_MANAGER')
                ->where('status','ACTIVE')
                ->where(fn($q)=>$q->whereNull('starts_on')->orWhere('starts_on','<=',today()))
                ->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',today()))
                ->exists();

            if(!$hasProjectManager){
                return back()->withErrors(['project_id'=>'This project has no active Project Manager assignment. Configure Team & Access before routing the request.'])->withInput();
            }

        }

        // Complete the Operations role-queue action before project binding
        // re-resolves its owner. The transaction keeps both changes atomic.
        $transitions->complete($request->user(), $serviceRequest, ['TRIAGE','EMERGENCY_DISPATCH','OPERATIONS_REVIEW'], $data['notes'] ?? null);
        if ((int)$serviceRequest->project_id !== (int)$projectId) {
            $serviceRequest->update(['project_id'=>$projectId]);
            // The next stage was opened before project binding to preserve the
            // Operations actor's original scope. Resolve its project owner now,
            // in the same transaction, before another actor can claim it.
            $owners->refresh($serviceRequest->fresh());
        }
        return back()->with('status', 'Request routed to the next workflow stage.');
        });
    }

    public function projectReview(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required','in:APPROVE,RETURN'],'notes' => ['nullable','string','max:2000']]);
        if ($data['decision'] === 'RETURN') {
            $target = $serviceRequest->workflow_key === 'EMERGENCY_MAINTENANCE' ? 'EMERGENCY_DISPATCH' : 'TRIAGE';
            $transitions->returnToStage($request->user(), $serviceRequest, ['PROJECT_MANAGER_REVIEW'], $target, $data['notes'] ?? 'Project Manager requested additional review.');
        } else {
            $transitions->complete($request->user(), $serviceRequest, ['PROJECT_MANAGER_REVIEW'], $data['notes'] ?? null);
        }
        return back()->with('status', 'Project Manager review recorded.');
    }

    public function stageReview(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data=$request->validate([
            'decision'=>['required','in:APPROVE,RETURN,REWORK'],
            'notes'=>[$serviceRequest->workflow_stage === 'TECHNICAL_REPORT' ? 'required' : 'nullable','string','max:3000'],
            'customer_report'=>[$serviceRequest->workflow_stage === 'TECHNICAL_REPORT' && $request->input('decision') === 'APPROVE' ? 'required' : 'nullable','string','max:5000'],
        ]);
        $stage=(string)$serviceRequest->workflow_stage;
        $allowed=[
            'MAINTENANCE_MANAGER_REVIEW',
            'TECHNICAL_ASSESSMENT',
            'TECHNICAL_REVIEW',
            'TECHNICAL_REPORT',
        ];
        abort_unless(in_array($stage,$allowed,true),422,'This stage is not handled by technical review.');

        if($data['decision']==='APPROVE'){
            if ($stage==='TECHNICAL_REPORT') {
                DB::transaction(function () use ($transitions,$request,$serviceRequest,$stage,$data): void {
                    $transitions->complete($request->user(),$serviceRequest,[$stage],$data['notes']??null);
                    $current=$serviceRequest->fresh();
                    $context=(array)($current->workflow_context ?? []);
                    $context['customer_delivery_report']=[
                        'text'=>trim($data['customer_report']),
                        'submitted_at'=>now()->toIso8601String(),
                    ];
                    $current->update(['workflow_context'=>$context]);
                });
            } else {
                $transitions->complete($request->user(),$serviceRequest,[$stage],$data['notes']??null);
            }
        } else {
            $target=match($stage){
                'MAINTENANCE_MANAGER_REVIEW'=>'PROJECT_MANAGER_REVIEW',
                'TECHNICAL_ASSESSMENT'=>'MAINTENANCE_MANAGER_REVIEW',
                'TECHNICAL_REVIEW'=>'EXECUTION',
                'TECHNICAL_REPORT'=>'SITE_VISIT',
            };
            $transitions->returnToStage(
                $request->user(),
                $serviceRequest,
                [$stage],
                $target,
                $data['notes']??'Returned for additional work.'
            );
        }

        return back()->with('status','Technical workflow review recorded.');
    }

    public function assignTechnician(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['technician_id' => ['required','integer'],'notes' => ['nullable','string','max:2000']]);
        $transitions->assignTechnician($request->user(), $serviceRequest, (int) $data['technician_id'], $data['notes'] ?? null);
        return back()->with('status', 'Technician assigned and request moved to execution.');
    }

    public function completeExecution(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['completion_notes' => ['required','string','max:5000']]);
        $transitions->completeExecution($request->user(), $serviceRequest, $data['completion_notes']);
        return back()->with('status', 'Technical work completed and sent to the next workflow stage.');
    }

    public function verify(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required','in:APPROVE,REWORK'],'notes' => ['nullable','string','max:2000']]);
        $stage = $serviceRequest->workflow_stage;
        abort_unless(in_array($stage, ['QUALITY_VERIFICATION','HSE_CLEARANCE'], true), 422);
        if ($data['decision'] === 'REWORK') {
            if($stage==='HSE_CLEARANCE'){
                $target=$serviceRequest->workflow_key==='EMERGENCY_MAINTENANCE'
                    ? 'MAINTENANCE_MANAGER_REVIEW'
                    : 'TECHNICAL_ASSESSMENT';
                $transitions->returnToStage($request->user(),$serviceRequest,[$stage],$target,$data['notes']??'HSE changes required before execution.');
            } else {
                $transitions->rework($request->user(), $serviceRequest, $stage, $data['notes'] ?? null);
            }
        } else {
            $transitions->complete($request->user(), $serviceRequest, [$stage], $data['notes'] ?? null);
        }
        return back()->with('status', $stage==='HSE_CLEARANCE'?'HSE clearance decision recorded.':'Quality verification decision recorded.');
    }

    public function close(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable','string','max:2000']]);
        DB::transaction(function () use ($data, $request, $serviceRequest, $transitions) {
            $context = (array) ($serviceRequest->workflow_context ?? []);
            $context['operationally_closed_at'] = now()->toIso8601String();
            $context['operationally_closed_by'] = $request->user()->id;
            $serviceRequest->update(['workflow_context' => $context,'status' => 'RESOLVED','resolved_at' => $serviceRequest->resolved_at ?: now()]);
            $transitions->complete($request->user(), $serviceRequest, ['CLOSURE'], $data['notes'] ?? 'Operational closure completed.');
        });
        return back()->with('status', 'Request operationally closed and sent for customer satisfaction.');
    }
}
