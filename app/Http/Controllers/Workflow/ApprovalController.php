<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\{ApprovalRequest,ServiceRequest};
use App\Services\{ApprovalService,AuthorizationService,ScopeService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(Request $request, AuthorizationService $authorization, ScopeService $scopes): View
    {
        $user=$request->user();
        $roles=$authorization->roleCodes($user)->push(strtoupper((string)$user->role))->filter()->unique()->values();
        $serviceRequestIds=$scopes->apply(
            ServiceRequest::query()->where('tenant_id',$user->tenant_id),
            $user
        )->pluck('id');

        $base=ApprovalRequest::query()
            ->where('tenant_id',$user->tenant_id)
            ->whereIn('approval_role',$roles)
            ->where(function($q) use($serviceRequestIds){
                $q->where('entity_type','!=',ServiceRequest::class)
                  ->orWhere(function($service) use($serviceRequestIds){
                      $service->where('entity_type',ServiceRequest::class)->whereIn('entity_id',$serviceRequestIds);
                  });
            });

        $query=(clone $base)
            ->orderByRaw("CASE WHEN status='PENDING' THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')->latest('id');

        if($request->filled('status')) $query->where('status',strtoupper((string)$request->query('status')));
        if($request->filled('role')){
            $role=strtoupper((string)$request->query('role'));
            abort_unless($roles->contains($role),403);
            $query->where('approval_role',$role);
        }
        if($request->boolean('breached')) $query->where('status','PENDING')->whereNotNull('due_at')->where('due_at','<',now());

        return view('workflow.approvals.index',[
            'approvals'=>$query->paginate(30)->withQueryString(),
            'pendingCount'=>(clone $base)->where('status','PENDING')->count(),
            'breachedCount'=>(clone $base)->where('status','PENDING')->whereNotNull('due_at')->where('due_at','<',now())->count(),
        ]);
    }

    public function decide(Request $http, ApprovalRequest $approval, ApprovalService $service): RedirectResponse
    {
        $data=$http->validate(['decision'=>['required','in:APPROVED,REJECTED,RETURNED'],'note'=>['nullable','string','max:1000']]);
        $service->decide($approval,$data['decision'],$data['note']??null);
        return back()->with('status','Approval decision recorded.');
    }
}
