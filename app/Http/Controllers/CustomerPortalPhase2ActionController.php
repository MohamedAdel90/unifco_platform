<?php

namespace App\Http\Controllers;

use App\Models\{CustomerActivityEvent,CustomerPortalActionRequest,FinancialDocument,ServiceContract};
use App\Services\CustomerPortalAccessService;
use Illuminate\Http\{RedirectResponse,Request};

class CustomerPortalPhase2ActionController extends Controller
{
    private function customerUser(Request $request): \App\Models\User
    {
        $user=$request->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403);
        return $user;
    }

    public function requestContractRenewal(Request $request, ServiceContract $contract, CustomerPortalAccessService $access): RedirectResponse
    {
        $user=$this->customerUser($request);
        abort_unless(in_array($access->role($user),['CUSTOMER_ADMIN','FINANCE'],true),403);
        abort_unless((int)$contract->customer_id===(int)$user->customer_id,403);
        $access->assertContract($user,(int)$contract->id);
        $data=$request->validate(['notes'=>['nullable','string','max:2000']]);

        $action=CustomerPortalActionRequest::firstOrCreate([
            'customer_id'=>$user->customer_id,'action_type'=>'CONTRACT_RENEWAL','reference_type'=>ServiceContract::class,
            'reference_id'=>$contract->id,'status'=>'OPEN',
        ],[
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'user_id'=>$user->id,
            'notes'=>$data['notes']??null,'submitted_at'=>now(),
        ]);

        CustomerActivityEvent::create([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$user->customer_id,
            'event_type'=>'CONTRACT_RENEWAL_REQUESTED','reference_type'=>ServiceContract::class,'reference_id'=>$contract->id,
            'title'=>'Contract renewal requested: '.$contract->contract_no,'description'=>$data['notes']??null,'visibility'=>'BOTH',
            'metadata'=>['portal_action_request_id'=>$action->id],
        ]);

        return back()->with('status','Contract renewal request submitted to UNIFCO.');
    }

    public function invoiceQuery(Request $request, FinancialDocument $invoice, CustomerPortalAccessService $access): RedirectResponse
    {
        $user=$this->customerUser($request);
        abort_unless(in_array($access->role($user),['CUSTOMER_ADMIN','FINANCE'],true),403);
        abort_unless((int)$invoice->customer_id===(int)$user->customer_id && $invoice->document_type==='AR_INVOICE',403);
        $data=$request->validate(['notes'=>['required','string','max:2000']]);

        $action=CustomerPortalActionRequest::create([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$user->customer_id,'user_id'=>$user->id,
            'action_type'=>'INVOICE_QUERY','reference_type'=>FinancialDocument::class,'reference_id'=>$invoice->id,'status'=>'OPEN',
            'notes'=>$data['notes'],'submitted_at'=>now(),
        ]);

        CustomerActivityEvent::create([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$user->customer_id,
            'event_type'=>'INVOICE_QUERY_SUBMITTED','reference_type'=>FinancialDocument::class,'reference_id'=>$invoice->id,
            'title'=>'Invoice query submitted: '.$invoice->document_no,'description'=>$data['notes'],'visibility'=>'BOTH',
            'metadata'=>['portal_action_request_id'=>$action->id],
        ]);

        return back()->with('status','Invoice query submitted to UNIFCO Finance.');
    }
}
