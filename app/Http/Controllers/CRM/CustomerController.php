<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers=$this->filteredCustomers($request)->orderBy('customer_code')->paginate(25)->withQueryString();
        $portfolio=Customer::query();

        return view('crm.customers.index',[
            'customers'=>$customers,
            'metrics'=>[
                'total'=>(clone $portfolio)->count(),
                'active'=>(clone $portfolio)->where('status','ACTIVE')->count(),
                'onboarding'=>(clone $portfolio)->whereIn('onboarding_status',['DRAFT','ONBOARDING','PENDING'])->count(),
                'blocked'=>(clone $portfolio)->where('status','BLOCKED')->count(),
            ],
            'cities'=>Customer::query()->whereNotNull('city')->where('city','!=','')->distinct()->orderBy('city')->pluck('city'),
            'industries'=>Customer::query()->whereNotNull('industry')->where('industry','!=','')->distinct()->orderBy('industry')->pluck('industry'),
        ]);
    }

    public function export(Request $request)
    {
        $customers=$this->filteredCustomers($request)->orderBy('customer_code')->get();

        return response()->streamDownload(function () use ($customers) {
            $stream=fopen('php://output','w');
            fwrite($stream,"\xEF\xBB\xBF");
            fputcsv($stream,['customer_code','name','industry','city','contact_name','email','phone','status','onboarding_status']);
            foreach($customers as $customer){
                fputcsv($stream,[$customer->customer_code,$customer->name,$customer->industry,$customer->city,$customer->contact_name,$customer->email,$customer->phone,$customer->status,$customer->onboarding_status]);
            }
            fclose($stream);
        },'unifco-customers-'.now()->format('Ymd').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file'=>['required','file','mimes:csv,txt','max:5120']]);
        $handle=fopen($request->file('file')->getRealPath(),'r');
        $header=fgetcsv($handle);
        if($header===false){
            throw ValidationException::withMessages(['file'=>'The CSV file is empty.']);
        }

        $header=array_map(fn($value)=>strtolower(trim((string)$value," \t\n\r\0\x0B\xEF\xBB\xBF")),$header);
        if(array_diff(['customer_code','name'],$header)){
            fclose($handle);
            throw ValidationException::withMessages(['file'=>'CSV columns customer_code and name are required.']);
        }

        $allowed=['customer_code','name','industry','city','contact_name','email','phone','status','onboarding_status'];
        $imported=0;
        DB::transaction(function () use ($handle,$header,$allowed,&$imported) {
            while(($row=fgetcsv($handle))!==false){
                if($imported>=500){
                    throw ValidationException::withMessages(['file'=>'A maximum of 500 customer rows can be imported at once.']);
                }
                $row=array_pad($row,count($header),null);
                $record=array_intersect_key(array_combine($header,array_slice($row,0,count($header))),array_flip($allowed));
                $record=array_map(fn($value)=>is_string($value)?trim($value):$value,$record);
                if(!array_filter($record,fn($value)=>$value!==null && $value!=='')){ continue; }

                validator($record,[
                    'customer_code'=>['required','string','max:50'],'name'=>['required','string','max:180'],
                    'email'=>['nullable','email','max:255'],'industry'=>['nullable','string','max:120'],'city'=>['nullable','string','max:120'],
                    'contact_name'=>['nullable','string','max:180'],'phone'=>['nullable','string','max:40'],
                    'status'=>['nullable',Rule::in(['ACTIVE','BLOCKED'])],
                    'onboarding_status'=>['nullable',Rule::in(['DRAFT','ONBOARDING','PENDING','COMPLETED','ACTIVE'])],
                ])->validate();

                $record['status']=$record['status']??'ACTIVE';
                $record['onboarding_status']=$record['onboarding_status']??'ONBOARDING';
                Customer::updateOrCreate(
                    ['tenant_id'=>Auth::user()->tenant_id,'customer_code'=>$record['customer_code']],
                    [...$record,'organization_id'=>Auth::user()->organization_id]
                );
                $imported++;
            }
        });
        fclose($handle);

        return redirect()->route('crm.customers.index')->with('status',"{$imported} customers imported successfully.");
    }
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
            'contract_manager_name'=>['nullable','string','max:180'],'contract_manager_title'=>['nullable','string','max:180'],'project_name'=>['nullable','string','max:255'],
            'phone'=>['nullable','string','max:40'],'city'=>['nullable','string','max:120'],'country'=>['nullable','string','max:120'],'address'=>['nullable','string','max:500'],
        ]);
    }

    private function filteredCustomers(Request $request)
    {
        $search=trim((string)$request->query('q'));

        return Customer::query()
            ->when($search!=='',function($query)use($search){
                $query->where(function($nested)use($search){
                    $nested->where('customer_code','like',"%{$search}%")
                        ->orWhere('name','like',"%{$search}%")
                        ->orWhere('email','like',"%{$search}%")
                        ->orWhere('contact_name','like',"%{$search}%")
                        ->orWhere('phone','like',"%{$search}%");
                });
            })
            ->when($request->filled('status'),fn($query)=>$query->where('status',$request->query('status')))
            ->when($request->filled('city'),fn($query)=>$query->where('city',$request->query('city')))
            ->when($request->filled('industry'),fn($query)=>$query->where('industry',$request->query('industry')));
    }
}
