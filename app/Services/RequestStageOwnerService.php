<?php

namespace App\Services;

use App\Models\{ProjectUserAssignment,ServiceRequest,User};

class RequestStageOwnerService
{
    private const PROJECT_BOUND = [
        'OPERATIONS_MANAGER','MAINTENANCE_MANAGER','PROJECT_MANAGER','MAINTENANCE_ENGINEER',
        'TECHNICAL_SUPERVISOR','TECHNICIAN','QUALITY','HSE',
    ];

    public function resolve(ServiceRequest $request,string $role,string $stage): array
    {
        $role=strtoupper($role);

        if($role==='OPERATIONS_MANAGER'){
            return $request->operations_manager_id
                ? ['user_id'=>(int)$request->operations_manager_id,'status'=>'ASSIGNED']
                : ['user_id'=>null,'status'=>$request->project_id?'NEEDS_ASSIGNMENT':'ROLE_QUEUE'];
        }

        if($role==='PROJECT_MANAGER'){
            return $request->project_manager_id
                ? ['user_id'=>(int)$request->project_manager_id,'status'=>'ASSIGNED']
                : ['user_id'=>null,'status'=>$request->project_id?'NEEDS_ASSIGNMENT':'ROLE_QUEUE'];
        }

        if($role==='TECHNICIAN'){
            return $request->assigned_engineer_id
                ? ['user_id'=>(int)$request->assigned_engineer_id,'status'=>'ASSIGNED']
                : ['user_id'=>null,'status'=>$request->project_id?'NEEDS_ASSIGNMENT':'ROLE_QUEUE'];
        }

        if($request->project_id && in_array($role,self::PROJECT_BOUND,true)){
            $ids=ProjectUserAssignment::query()
                ->where('tenant_id',$request->tenant_id)
                ->where('project_id',$request->project_id)
                ->where('project_role',$role)
                ->where('status','ACTIVE')
                ->where(fn($q)=>$q->whereNull('starts_on')->orWhere('starts_on','<=',today()))
                ->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',today()))
                ->orderBy('id')->pluck('user_id')->unique()->values();

            if($ids->count()===1) return ['user_id'=>(int)$ids->first(),'status'=>'ASSIGNED'];
            return ['user_id'=>null,'status'=>'NEEDS_ASSIGNMENT'];
        }

        $candidates=User::query()
            ->where('tenant_id',$request->tenant_id)
            ->whereIn('status',['ACTIVE','ENABLED'])
            ->where(function($q) use($role){
                $q->where('role',$role)
                  ->orWhereHas('activeRoles',fn($r)=>$r->where('roles.code',$role));
            })
            ->orderBy('id')->pluck('id')->unique()->values();

        if($candidates->count()===1) return ['user_id'=>(int)$candidates->first(),'status'=>'ASSIGNED'];

        return ['user_id'=>null,'status'=>'ROLE_QUEUE'];
    }

    public function candidates(ServiceRequest $request,string $role)
    {
        $role=strtoupper($role);
        $query=User::query()
            ->where('tenant_id',$request->tenant_id)
            ->whereIn('status',['ACTIVE','ENABLED'])
            ->where(function($q) use($role){
                $q->where('role',$role)
                  ->orWhereHas('activeRoles',fn($r)=>$r->where('roles.code',$role));
            });

        if($request->project_id && in_array($role,self::PROJECT_BOUND,true)){
            $ids=ProjectUserAssignment::query()
                ->where('tenant_id',$request->tenant_id)
                ->where('project_id',$request->project_id)
                ->where('project_role',$role)
                ->where('status','ACTIVE')
                ->where(fn($q)=>$q->whereNull('starts_on')->orWhere('starts_on','<=',today()))
                ->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',today()))
                ->pluck('user_id');
            $query->whereIn('id',$ids);
        }

        return $query->orderBy('name')->get(['id','name','email','role']);
    }

    public function refresh(ServiceRequest $request): void
    {
        $steps=\App\Models\ApprovalRequest::query()
            ->where('tenant_id',$request->tenant_id)
            ->where('entity_type',ServiceRequest::class)
            ->where('entity_id',$request->id)
            ->whereIn('status',['WAITING','PENDING'])
            ->get();

        foreach($steps as $step){
            $owner=$this->resolve($request,(string)$step->approval_role,(string)$step->action);
            $step->update([
                'assigned_user_id'=>$owner['user_id'],
                'routing_status'=>$owner['status'],
            ]);
        }
    }
}
