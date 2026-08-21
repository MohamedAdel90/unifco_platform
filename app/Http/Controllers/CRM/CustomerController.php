<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View { return view('crm.customers.index',['customers'=>Customer::orderBy('customer_code')->paginate(25)]); }
    public function create(): View { return view('crm.customers.form',['customer'=>new Customer()]); }
    public function edit(Customer $customer): View { return view('crm.customers.form',compact('customer')); }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $customer=Customer::create([...$this->validated($request),'organization_id'=>Auth::user()->organization_id,'status'=>'ACTIVE','onboarding_status'=>'ONBOARDING']);
        $audit->record('crm.customer.created',$customer,[],$customer->toArray());
        return redirect()->route('crm.customers.portal',$customer)->with('status','Customer created. Continue the onboarding checklist.');
    }

    public function update(Request $request, Customer $customer, AuditService $audit): RedirectResponse
    {
        $before=$customer->toArray(); $customer->update($this->validated($request,$customer));
        $audit->record('crm.customer.updated',$customer,$before,$customer->fresh()->toArray());
        return redirect()->route('crm.customers.portal',$customer)->with('status','Customer master data updated.');
    }

    public function block(Customer $customer, AuditService $audit): RedirectResponse
    {
        $before=$customer->toArray(); $customer->update(['status'=>'BLOCKED']);
        $audit->record('crm.customer.blocked',$customer,$before,$customer->fresh()->toArray());
        return back()->with('status','Customer blocked.');
    }

    private function validated(Request $request, ?Customer $customer=null): array
    {
        return $request->validate([
            'customer_code'=>['required','string','max:50',Rule::unique('customers')->where(fn($q)=>$q->where('tenant_id',Auth::user()->tenant_id))->ignore($customer?->id)],
            'name'=>['required','string','max:180'],'commercial_registration'=>['nullable','string','max:60'],'vat_number'=>['nullable','string','max:60'],
            'industry'=>['nullable','string','max:120'],'email'=>['nullable','email','max:255'],'contact_name'=>['nullable','string','max:180'],
            'phone'=>['nullable','string','max:40'],'city'=>['nullable','string','max:120'],'country'=>['nullable','string','max:120'],'address'=>['nullable','string','max:500'],
        ]);
    }
}
