<?php

namespace App\Services\Operations;

use App\Models\{ProjectUserAssignment,ServiceRequest,User};
use App\Services\ScopeService;

class OperationsRoutingService
{
    public function __construct(private ScopeService $scopes, private \App\Services\RequestStageOwnerService $owners) {}

    /** Resolve the best Operations Manager without bypassing normal RBAC/scope. */
    public function resolve(ServiceRequest $request): ?User
    {
        if (!$request->operational_domain_id) return null;

        $candidates = User::query()
            ->where('tenant_id',$request->tenant_id)
            ->where('status', 'ACTIVE')
            ->whereHas('roles', fn ($q) => $q->where('code', 'OPERATIONS_MANAGER')->where('is_active', true))
            ->whereHas('operationalDomains', function ($q) use ($request) {
                $q->where('operational_domains.id', $request->operational_domain_id)
                  ->where('user_operational_domains.is_active', true);
            })
            ->when($request->project_id, function ($q) use ($request) {
                $q->whereExists(function ($sub) use ($request) {
                    $sub->selectRaw('1')->from('project_user_assignments')
                        ->whereColumn('project_user_assignments.user_id','users.id')
                        ->where('project_user_assignments.project_id',$request->project_id)
                        ->where('project_user_assignments.project_role','OPERATIONS_MANAGER')
                        ->where('project_user_assignments.status','ACTIVE')
                        ->where(fn($d)=>$d->whereNull('project_user_assignments.starts_on')->orWhere('project_user_assignments.starts_on','<=',today()))
                        ->where(fn($d)=>$d->whereNull('project_user_assignments.ends_on')->orWhere('project_user_assignments.ends_on','>=',today()));
                });
            })
            ->with(['operationalDomains' => fn ($q) => $q->where('operational_domains.id', $request->operational_domain_id)])
            ->get();

        $eligible = $candidates->filter(fn (User $manager) => $this->requestIsInsideManagerScope($request, $manager));

        return $eligible->sortBy(function (User $manager) use ($request) {
            $pivot = $manager->operationalDomains->firstWhere('id', $request->operational_domain_id)?->pivot;
            $typeRank = ['PRIMARY'=>1,'BACKUP'=>2,'ESCALATION'=>3][$pivot?->assignment_type ?? 'ESCALATION'];
            return sprintf('%d-%010d-%010d', $typeRank, $pivot?->priority ?? 100, $manager->id);
        })->first();
    }

    public function assign(ServiceRequest $request): ServiceRequest
    {
        $manager = $this->resolve($request);
        $projectManager = $this->resolveProjectManager($request);

        $request->operations_manager_id = $manager?->id;
        $request->operations_routing_status = $manager ? 'ASSIGNED' : 'UNASSIGNED';
        $request->project_manager_id = $projectManager?->id;
        $request->project_routing_status = $request->project_id ? ($projectManager ? 'ASSIGNED' : 'UNASSIGNED') : 'NOT_APPLICABLE';
        $request->saveQuietly();
        $fresh=$request->refresh();
        $this->owners->refresh($fresh);

        return $fresh;
    }

    public function resolveProjectManager(ServiceRequest $request): ?User
    {
        if(!$request->project_id) return null;

        $assignment=ProjectUserAssignment::query()
            ->where('tenant_id',$request->tenant_id)
            ->where('project_id',$request->project_id)
            ->where('project_role','PROJECT_MANAGER')
            ->where('status','ACTIVE')
            ->where(fn($q)=>$q->whereNull('starts_on')->orWhere('starts_on','<=',today()))
            ->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',today()))
            ->orderBy('id')
            ->first();

        if(!$assignment) return null;

        $manager=User::query()->where('tenant_id',$request->tenant_id)->where('status','ACTIVE')->find($assignment->user_id);
        if(!$manager || !$manager->activeRoles()->where('roles.code','PROJECT_MANAGER')->exists()) return null;

        return $this->requestIsInsideManagerScope($request,$manager) ? $manager : null;
    }

    private function requestIsInsideManagerScope(ServiceRequest $request, User $manager): bool
    {
        return $this->scopes->apply(ServiceRequest::query()->whereKey($request->id), $manager)->exists();
    }
}
