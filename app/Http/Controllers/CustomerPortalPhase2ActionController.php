<?php

namespace App\Http\Controllers;

use App\Models\{Asset,CustomerActivityEvent,CustomerPortalActionRequest,FinancialDocument,ServiceContract,WorkOrder};
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

    private function event(\App\Models\User $user,string $type,string $referenceType,int $referenceId,string $title,?string $description,int $actionId): void
    {
        CustomerActivityEvent::create([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$user->customer_id,
            'event_type'=>$type,'reference_type'=>$referenceType,'reference_id'=>$referenceId,
            'title'=>$title,'description'=>$description,'visibility'=>'BOTH','metadata'=>['portal_action_request_id'=>$actionId],
        ]);
    }

    private function createAction(\App\Models\User $user,array $attributes,array $values): CustomerPortalActionRequest
    {
        return CustomerPortalActionRequest::create(array_merge([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$user->customer_id,'user_id'=>$user->id,
            'status'=>'OPEN','submitted_at'=>now(),
        ],$attributes,$values));
    }

    public function requestContractRenewal(Request $request, ServiceContract $contract, CustomerPortalAccessService $access): RedirectResponse
    {
        $user=$this->customerUser($request);
        abort_unless(in_array($access->role($user),['CUSTOMER_ADMIN','FINANCE'],true),403);
        abort_unless((int)$contract->customer_id===(int)$user->customer_id,403);
        $access->assertContract($user,(int)$contract->id);
        $data=$request->validate(['notes'=>['nullable','string','max:2000']]);

        $existing=CustomerPortalActionRequest::where('customer_id',$user->customer_id)->where('action_type','CONTRACT_RENEWAL')
            ->where('reference_type',ServiceContract::class)->where('reference_id',$contract->id)->where('status','OPEN')->first();
        $action=$existing ?: $this->createAction($user,[
            'action_type'=>'CONTRACT_RENEWAL','assigned_role'=>'TENDERS_CONTRACTS','priority'=>'NORMAL',
            'reference_type'=>ServiceContract::class,'reference_id'=>$contract->id,
        ],['notes'=>$data['notes']??null,'due_at'=>now()->addBusinessDays(5)]);

        if(!$existing) $this->event($user,'CONTRACT_RENEWAL_REQUESTED',ServiceContract::class,$contract->id,'Contract renewal requested: '.$contract->contract_no,$data['notes']??null,$action->id);
        return back()->with('status',$existing?'A renewal request is already open for this contract.':'Contract renewal request submitted to UNIFCO.');
    }

    public function invoiceQuery(Request $request, FinancialDocument $invoice, CustomerPortalAccessService $access): RedirectResponse
    {
        $user=$this->customerUser($request);
        abort_unless(in_array($access->role($user),['CUSTOMER_ADMIN','FINANCE'],true),403);
        abort_unless((int)$invoice->customer_id===(int)$user->customer_id && $invoice->document_type==='AR_INVOICE',403);
        $data=$request->validate(['notes'=>['required','string','max:2000']]);

        $action=$this->createAction($user,[
            'action_type'=>'INVOICE_QUERY','assigned_role'=>'FINANCE','priority'=>'NORMAL','reference_type'=>FinancialDocument::class,'reference_id'=>$invoice->id,
        ],['notes'=>$data['notes'],'due_at'=>now()->addBusinessDays(2)]);

        $this->event($user,'INVOICE_QUERY_SUBMITTED',FinancialDocument::class,$invoice->id,'Invoice query submitted: '.$invoice->document_no,$data['notes'],$action->id);
        return back()->with('status','Invoice query submitted to UNIFCO Finance.');
    }

    public function paymentProof(Request $request, FinancialDocument $invoice, CustomerPortalAccessService $access): RedirectResponse
    {
        $user=$this->customerUser($request);
        abort_unless(in_array($access->role($user),['CUSTOMER_ADMIN','FINANCE'],true),403);
        abort_unless((int)$invoice->customer_id===(int)$user->customer_id && $invoice->document_type==='AR_INVOICE',403);
        $data=$request->validate([
            'proof'=>['required','file','mimes:pdf,jpg,jpeg,png','max:10240'],
            'notes'=>['nullable','string','max:2000'],
        ]);
        $file=$data['proof'];
        $path=$file->store('customer-portal/payment-proofs/'.$user->customer_id,'local');

        $action=$this->createAction($user,[
            'action_type'=>'PAYMENT_PROOF','assigned_role'=>'FINANCE','priority'=>'HIGH','reference_type'=>FinancialDocument::class,'reference_id'=>$invoice->id,
        ],[
            'notes'=>$data['notes']??null,'attachment_path'=>$path,'attachment_name'=>$file->getClientOriginalName(),
            'attachment_mime'=>$file->getClientMimeType(),'due_at'=>now()->addBusinessDay(),
        ]);

        $this->event($user,'PAYMENT_PROOF_SUBMITTED',FinancialDocument::class,$invoice->id,'Payment proof submitted: '.$invoice->document_no,$data['notes']??null,$action->id);
        return back()->with('status','Payment proof uploaded and sent to UNIFCO Finance.');
    }

    public function requestRevisit(Request $request, WorkOrder $workOrder, CustomerPortalAccessService $access): RedirectResponse
    {
        $user=$this->customerUser($request);
        abort_unless($access->canAcceptWork($user),403);
        $access->assertAsset($user,(int)$workOrder->asset_id);
        abort_unless(Asset::whereKey($workOrder->asset_id)->where('customer_id',$user->customer_id)->exists(),403);
        abort_unless($workOrder->status==='COMPLETED',422,'Only completed work can be returned for revisit.');
        $data=$request->validate(['notes'=>['required','string','max:2000']]);

        $existing=CustomerPortalActionRequest::where('customer_id',$user->customer_id)->where('action_type','WORK_REVISIT')
            ->where('reference_type',WorkOrder::class)->where('reference_id',$workOrder->id)->where('status','OPEN')->first();
        $action=$existing ?: $this->createAction($user,[
            'action_type'=>'WORK_REVISIT','assigned_role'=>'MAINTENANCE_MANAGER','priority'=>'HIGH','reference_type'=>WorkOrder::class,'reference_id'=>$workOrder->id,
        ],['notes'=>$data['notes'],'due_at'=>now()->addHours(4)]);

        if(!$existing){
            $workOrder->update(['customer_rejected_at'=>now(),'customer_accepted_at'=>null,'customer_acceptance_notes'=>$data['notes']]);
            $this->event($user,'WORK_REVISIT_REQUESTED',WorkOrder::class,$workOrder->id,'Revisit requested: '.$workOrder->work_order_no,$data['notes'],$action->id);
        }
        return back()->with('status',$existing?'A revisit request is already open for this work order.':'Revisit request submitted to UNIFCO Maintenance.');
    }
}
