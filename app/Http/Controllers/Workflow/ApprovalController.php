<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Services\ApprovalService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $user=$request->user();
        $query=ApprovalRequest::query()
            ->when($user && $user->role!=='ADMIN', fn($q)=>$q->where('approval_role',$user->role))
            ->orderByRaw("CASE WHEN status='PENDING' THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')->latest('id');

        if($request->filled('status')) $query->where('status',strtoupper((string)$request->query('status')));
        if($user?->role==='ADMIN' && $request->filled('role')) $query->where('approval_role',strtoupper((string)$request->query('role')));
        if($request->boolean('breached')) $query->where('status','PENDING')->whereNotNull('due_at')->where('due_at','<',now());

        $countBase=ApprovalRequest::query()->when($user && $user->role!=='ADMIN',fn($q)=>$q->where('approval_role',$user->role));
        return view('workflow.approvals.index',[
            'approvals'=>$query->paginate(30)->withQueryString(),
            'pendingCount'=>(clone $countBase)->where('status','PENDING')->count(),
            'breachedCount'=>(clone $countBase)->where('status','PENDING')->whereNotNull('due_at')->where('due_at','<',now())->count(),
        ]);
    }

    public function decide(Request $http, ApprovalRequest $approval, ApprovalService $service): RedirectResponse
    {
        $data=$http->validate(['decision'=>['required','in:APPROVED,REJECTED,RETURNED'],'note'=>['nullable','string','max:1000']]);
        $service->decide($approval,$data['decision'],$data['note']??null);
        return back()->with('status','Approval decision recorded.');
    }
}
