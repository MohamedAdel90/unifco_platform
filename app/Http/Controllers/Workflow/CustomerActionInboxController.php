<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\{CustomerActivityEvent,CustomerPortalActionRequest};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerActionInboxController extends Controller
{
    private const ROLE_TYPES=[
        'MAINTENANCE_ENGINEER'=>['WORK_REVISIT'],
        'MAINTENANCE_MANAGER'=>['WORK_REVISIT'],
        'PROJECT_MANAGER'=>['WORK_REVISIT'],
        'TENDERS_CONTRACTS'=>['CONTRACT_RENEWAL'],
        'FINANCE'=>['INVOICE_QUERY','PAYMENT_PROOF','CONTRACT_RENEWAL'],
        'CEO'=>['CONTRACT_RENEWAL'],
        'ADMIN'=>['WORK_REVISIT','CONTRACT_RENEWAL','INVOICE_QUERY','PAYMENT_PROOF'],
    ];

    private function allowedTypes(Request $request): array
    {
        $user=$request->user();
        abort_unless($user,403);
        $types=self::ROLE_TYPES[$user->role]??[];
        abort_if($types===[],403,'This role has no customer action inbox.');
        return $types;
    }

    public function index(Request $request): View
    {
        $types=$this->allowedTypes($request);
        $open=CustomerPortalActionRequest::whereIn('action_type',$types)->where('status','OPEN')->latest('submitted_at')->limit(100)->get();
        $recent=CustomerPortalActionRequest::whereIn('action_type',$types)->where('status','!=','OPEN')->latest('resolved_at')->limit(50)->get();
        return view('workflow.customer-actions',compact('open','recent','types'));
    }

    public function resolve(Request $request, CustomerPortalActionRequest $action): RedirectResponse
    {
        $types=$this->allowedTypes($request);
        abort_unless(in_array($action->action_type,$types,true),403);
        abort_unless($action->status==='OPEN',422,'This customer action is already resolved.');
        $data=$request->validate(['decision'=>['required','in:RESOLVED,REJECTED'],'resolution_notes'=>['required','string','max:3000']]);

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
        $types=$this->allowedTypes($request);
        abort_unless(in_array($action->action_type,$types,true),403);
        abort_unless($action->attachment_path && Storage::disk('local')->exists($action->attachment_path),404);
        return Storage::disk('local')->download($action->attachment_path,$action->attachment_name ?: basename($action->attachment_path),[
            'Content-Type'=>$action->attachment_mime ?: 'application/octet-stream',
        ]);
    }
}
