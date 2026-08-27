<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\{Customer,CustomerActivityEvent,CustomerPortalActionRequest,User,WorkOrder};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerActionInboxController extends Controller
{
    private const ROLES=['MAINTENANCE_MANAGER','TENDERS_CONTRACTS','FINANCE','ADMIN'];

    private function role(Request $request): string
    {
        $user=$request->user();
        abort_unless($user && in_array($user->role,self::ROLES,true),403,'This role has no customer action inbox.');
        return $user->role;
    }

    private function visibleQuery(Request $request)
    {
        $role=$this->role($request);
        $query=CustomerPortalActionRequest::query();
        if($role!=='ADMIN') $query->where('assigned_role',$role);
        return $query;
    }

    private function assertVisible(Request $request, CustomerPortalActionRequest $action): void
    {
        $role=$this->role($request);
        if($role!=='ADMIN') abort_unless($action->assigned_role===$role,403);
    }

    public function index(Request $request): View
    {
        $role=$this->role($request);
        $open=$this->visibleQuery($request)->where('status','OPEN')->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')->orderBy('due_at')->limit(100)->get();
        $recent=$this->visibleQuery($request)->where('status','!=','OPEN')->latest('resolved_at')->limit(50)->get();
        $customerIds=$open->pluck('customer_id')->merge($recent->pluck('customer_id'))->unique();
        $userIds=$open->pluck('user_id')->merge($recent->pluck('user_id'))->unique();
        $customers=Customer::whereIn('id',$customerIds)->get()->keyBy('id');
        $submitters=User::whereIn('id',$userIds)->get()->keyBy('id');
        $breached=$open->filter(fn($a)=>$a->due_at && $a->due_at->isPast())->count();
        return view('workflow.customer-actions',compact('open','recent','role','customers','submitters','breached'));
    }

    public function resolve(Request $request, CustomerPortalActionRequest $action): RedirectResponse
    {
        $this->assertVisible($request,$action);
        abort_unless($action->status==='OPEN',422,'This customer action is already resolved.');
        $data=$request->validate(['decision'=>['required','in:RESOLVED,REJECTED'],'resolution_notes'=>['required','string','max:3000']]);

        if($action->action_type==='WORK_REVISIT' && $data['decision']==='RESOLVED'){
            $workOrder=WorkOrder::find($action->reference_id);
            if($workOrder && $workOrder->status==='COMPLETED'){
                $workOrder->update([
                    'status'=>'OPEN','completed_at'=>null,'completed_by'=>null,'customer_accepted_at'=>null,
                    'execution_notes'=>trim((string)$workOrder->execution_notes."\nCustomer revisit accepted: ".$data['resolution_notes']),
                ]);
            }
        }

        $action->update([
            'status'=>$data['decision'],'resolved_at'=>now(),'resolved_by'=>$request->user()->id,'resolution_notes'=>$data['resolution_notes'],
        ]);

        CustomerActivityEvent::create([
            'tenant_id'=>$action->tenant_id,'organization_id'=>$action->organization_id,'customer_id'=>$action->customer_id,
            'event_type'=>'CUSTOMER_ACTION_'.$data['decision'],'reference_type'=>$action->reference_type,'reference_id'=>$action->reference_id,
            'title'=>str_replace('_',' ',$action->action_type).' '.$data['decision'],
            'description'=>$data['resolution_notes'],'visibility'=>'BOTH','metadata'=>['portal_action_request_id'=>$action->id,'resolved_by'=>$request->user()->id],
        ]);

        return back()->with('status','Customer action updated successfully.');
    }

    public function attachment(Request $request, CustomerPortalActionRequest $action): StreamedResponse
    {
        $this->assertVisible($request,$action);
        abort_unless($action->attachment_path && Storage::disk('local')->exists($action->attachment_path),404);
        return Storage::disk('local')->download($action->attachment_path,$action->attachment_name ?: basename($action->attachment_path),[
            'Content-Type'=>$action->attachment_mime ?: 'application/octet-stream',
        ]);
    }
}
