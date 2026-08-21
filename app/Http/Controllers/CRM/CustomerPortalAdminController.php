<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Customer,CustomerContact,CustomerSite,FinancialDocument,ServiceContract,User};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CustomerPortalAdminController extends Controller
{
    public function show(Customer $customer): View
    {
        $contacts = CustomerContact::where('customer_id',$customer->id)->orderByDesc('is_primary')->orderBy('name')->get();
        $sites = CustomerSite::where('customer_id',$customer->id)->orderBy('site_code')->get();
        $portalUsers = User::where('customer_id',$customer->id)->orderBy('name')->get();
        $contracts = ServiceContract::where('customer_id',$customer->id)->latest('starts_on')->get();
        $assignedAssets = Asset::where('customer_id',$customer->id)->count();
        $steps = [
            'company' => filled($customer->name) && filled($customer->customer_code),
            'contacts' => $contacts->isNotEmpty(),
            'sites' => $sites->isNotEmpty(),
            'assets' => $assignedAssets > 0,
            'contracts' => $contracts->isNotEmpty(),
            'portal_users' => $portalUsers->isNotEmpty(),
        ];
        $progress = (int) round((collect($steps)->filter()->count()/count($steps))*100);

        return view('crm.customers.portal', [
            'customer'=>$customer,'contacts'=>$contacts,'sites'=>$sites,'portalUsers'=>$portalUsers,'contracts'=>$contracts,
            'assets'=>Asset::where(fn($q)=>$q->whereNull('customer_id')->orWhere('customer_id',$customer->id))->orderBy('asset_code')->get(),
            'invoices'=>FinancialDocument::where('document_type','AR_INVOICE')->where(fn($q)=>$q->whereNull('customer_id')->orWhere('customer_id',$customer->id))->latest('document_date')->limit(100)->get(),
            'steps'=>$steps,'progress'=>$progress,'assignedAssets'=>$assignedAssets,
        ]);
    }

    public function storeContact(Request $request, Customer $customer): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:180'],'job_title'=>['nullable','string','max:180'],'contact_type'=>['required','in:PRIMARY,FACILITY,MAINTENANCE,FINANCE,EMERGENCY,APPROVER'],'email'=>['nullable','email','max:255'],'mobile'=>['nullable','string','max:40'],'is_primary'=>['nullable','boolean']]);
        if(!empty($data['is_primary'])) CustomerContact::where('customer_id',$customer->id)->update(['is_primary'=>false]);
        CustomerContact::create($data+['customer_id'=>$customer->id]);
        return back()->with('status','Customer contact added.');
    }

    public function storeSite(Request $request, Customer $customer): RedirectResponse
    {
        $data=$request->validate(['site_code'=>['required','string','max:60'],'name'=>['required','string','max:180'],'city'=>['nullable','string','max:120'],'address'=>['nullable','string','max:500'],'latitude'=>['nullable','numeric','between:-90,90'],'longitude'=>['nullable','numeric','between:-180,180'],'contact_name'=>['nullable','string','max:180'],'contact_mobile'=>['nullable','string','max:40']]);
        CustomerSite::create($data+['customer_id'=>$customer->id,'status'=>'ACTIVE']);
        return back()->with('status','Customer site added.');
    }

    public function activate(Customer $customer): RedirectResponse
    {
        $hasContact=CustomerContact::where('customer_id',$customer->id)->exists();
        $hasSite=CustomerSite::where('customer_id',$customer->id)->exists();
        $hasUser=User::where('customer_id',$customer->id)->where('role','CUSTOMER')->exists();
        abort_unless($hasContact && $hasSite && $hasUser,422,'Complete at least one contact, one site and one portal user before activation.');
        $customer->update(['onboarding_status'=>'ACTIVE','status'=>'ACTIVE']);
        return back()->with('status','Customer onboarding activated.');
    }

    public function provisionUser(Request $request, Customer $customer): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:180'],'email'=>['required','email','max:255','unique:users,email'],'password'=>['required','confirmed',Password::min(12)->letters()->numbers()],'customer_portal_role'=>['required','in:CUSTOMER_ADMIN,FACILITY_MANAGER,FINANCE,REQUESTER,VIEWER']]);
        User::create(['tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,'customer_id'=>$customer->id,'name'=>$data['name'],'email'=>$data['email'],'password'=>$data['password'],'role'=>'CUSTOMER','customer_portal_role'=>$data['customer_portal_role'],'status'=>'ACTIVE']);
        return back()->with('status','Customer portal user created.');
    }

    public function storeContract(Request $request, Customer $customer): RedirectResponse
    {
        $data=$request->validate(['contract_no'=>['required','string','max:60'],'title'=>['required','string','max:255'],'starts_on'=>['required','date'],'ends_on'=>['required','date','after_or_equal:starts_on'],'contract_value'=>['required','numeric','min:0'],'currency'=>['required','string','size:3'],'billing_cycle'=>['required','string','max:30'],'scope'=>['nullable','string','max:5000'],'sla_summary'=>['nullable','string','max:5000']]);
        ServiceContract::create($data+['tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,'customer_id'=>$customer->id,'status'=>'ACTIVE']);
        return back()->with('status','Service contract created.');
    }

    public function assignAsset(Request $request, Customer $customer): RedirectResponse
    {
        $data=$request->validate(['asset_id'=>['required','integer','exists:assets,id']]);
        Asset::whereKey($data['asset_id'])->update(['customer_id'=>$customer->id]);
        return back()->with('status','Asset assigned to customer.');
    }

    public function linkInvoice(Request $request, Customer $customer): RedirectResponse
    {
        $data=$request->validate(['financial_document_id'=>['required','integer','exists:financial_documents,id']]);
        FinancialDocument::whereKey($data['financial_document_id'])->where('document_type','AR_INVOICE')->update(['customer_id'=>$customer->id]);
        return back()->with('status','Invoice linked to customer portal.');
    }
}
