<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\{ApprovalRequest,Project,ProjectUserAssignment,ServiceRequest,User};
use App\Services\{AuthorizationService,MaintenanceRequestTransitionService,RequestStageOwnerService,ScopeService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

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
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $serviceRequest->id)
            ->where('action', $serviceRequest->workflow_stage)
            ->where('status', 'PENDING')
            ->first();
        $roles = $authorization->roleCodes($user)->push(strtoupper((string) $user->role))->filter()->unique();
        $canAct = $step
            && $roles->contains(strtoupper((string) $step->approval_role))
            && $step->routing_status !== 'NEEDS_ASSIGNMENT'
            && (!$step->assigned_user_id || (int)$step->assigned_user_id === (int)$user->id);
        if ($serviceRequest->workflow_stage === 'EXECUTION') $canAct = $canAct && (int) $serviceRequest->assigned_engineer_id === (int) $user->id;

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
            ->when($serviceRequest->customer_id,fn($q)=>$q->where('customer_id',$serviceRequest->customer_id))
            ->orderBy('project_no')->get(['id','project_no','name','customer_id']);

        $canAssignOwner = $step
            && $step->routing_status === 'NEEDS_ASSIGNMENT'
            && $authorization->allows($user,'service_requests.assign',$serviceRequest);
        $ownerCandidates = $canAssignOwner
            ? $owners->candidates($serviceRequest,(string)$step->approval_role)
            : collect();

        return view('workflow.maintenance-request', compact('serviceRequest','step','canAct','technicians','projectOptions','canAssignOwner','ownerCandidates'));
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
            ->where('entity_type',ServiceRequest::class)
            ->where('entity_id',$serviceRequest->id)
            ->where('action',$serviceRequest->workflow_stage)
            ->where('status','PENDING')
            ->firstOrFail();

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

    public function triage(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
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
        if(!$projectId && $candidateProjects->count()>1){
            return back()->withErrors(['project_id'=>'Select the project responsible for this request before routing it.']);
        }

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
                return back()->withErrors(['project_id'=>'This project has no active Project Manager assignment. Configure Team & Access before routing the request.']);
            }

            if((int)$serviceRequest->project_id!==(int)$project->id){
                $serviceRequest->update(['project_id'=>$project->id]);
                $serviceRequest->refresh();
            }
        }

        $transitions->complete($request->user(), $serviceRequest, ['TRIAGE','EMERGENCY_DISPATCH'], $data['notes'] ?? null);
        return back()->with('status', 'Request routed to the next workflow stage.');
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
            'notes'=>['nullable','string','max:3000'],
        ]);
        $stage=(string)$serviceRequest->workflow_stage;
        $allowed=[
            'MAINTENANCE_MANAGER_REVIEW',
            'TECHNICAL_ASSESSMENT',
            'TECHNICAL_REVIEW',
        ];
        abort_unless(in_array($stage,$allowed,true),422,'This stage is not handled by technical review.');

        if($data['decision']==='APPROVE'){
            $transitions->complete($request->user(),$serviceRequest,[$stage],$data['notes']??null);
        } else {
            $target=match($stage){
                'MAINTENANCE_MANAGER_REVIEW'=>'PROJECT_MANAGER_REVIEW',
                'TECHNICAL_ASSESSMENT'=>'MAINTENANCE_MANAGER_REVIEW',
                'TECHNICAL_REVIEW'=>'EXECUTION',
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
        return back()->with('status', 'Execution completed and sent to verification / customer acceptance.');
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
        $context = (array) ($serviceRequest->workflow_context ?? []);
        $context['operationally_closed_at'] = now()->toIso8601String();
        $context['operationally_closed_by'] = $request->user()->id;
        $serviceRequest->update(['workflow_context' => $context,'status' => 'RESOLVED','resolved_at' => $serviceRequest->resolved_at ?: now()]);
        $transitions->complete($request->user(), $serviceRequest, ['CLOSURE'], $data['notes'] ?? 'Operational closure completed.');
        return back()->with('status', 'Request operationally closed and sent for customer satisfaction.');
    }
}
