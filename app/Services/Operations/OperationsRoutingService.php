<?php

namespace App\Services\Operations;

use App\Models\{ServiceRequest,User};
use App\Services\ScopeService;

class OperationsRoutingService
{
    public function __construct(private ScopeService $scopes) {}

    /** Resolve the best Operations Manager without bypassing normal RBAC/scope. */
    public function resolve(ServiceRequest $request): ?User
    {
        if (!$request->operational_domain_id) return null;

        $candidates = User::query()
            ->where('status', 'ACTIVE')
            ->whereHas('roles', fn ($q) => $q->where('code', 'OPERATIONS_MANAGER')->where('is_active', true))
            ->whereHas('operationalDomains', function ($q) use ($request) {
                $q->where('operational_domains.id', $request->operational_domain_id)
                  ->where('user_operational_domains.is_active', true);
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
        $request->operations_manager_id = $manager?->id;
        $request->operations_routing_status = $manager ? 'ASSIGNED' : 'UNASSIGNED';
        $request->save();
        return $request->refresh();
    }

    private function requestIsInsideManagerScope(ServiceRequest $request, User $manager): bool
    {
        return $this->scopes->apply(ServiceRequest::query()->whereKey($request->id), $manager)->exists();
    }
}
