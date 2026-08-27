<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\{CrmLead,Customer};
use App\Services\CustomerAcquisitionService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class CustomerAcquisitionController extends Controller
{
    private const ROLES=['ADMIN','CRM_MANAGER','SALES','MARKETING','CUSTOMER_SERVICE','TENDERS_CONTRACTS'];

    private function user(Request $request): \App\Models\User
    {
        $user=$request->user();
        abort_unless($user && in_array($user->role,self::ROLES,true),403,'This role cannot manage customer acquisition.');
        return $user;
    }

    public function index(Request $request): View
    {
        $user=$this->user($request);
        $base=CrmLead::where('tenant_id',$user->tenant_id);
        $stages=[];
        foreach(CustomerAcquisitionService::STAGES as $stage) $stages[$stage]=(clone $base)->where('lifecycle_stage',$stage)->count();
        $recent=(clone $base)->latest()->limit(50)->get();
        $customers=Customer::where('tenant_id',$user->tenant_id)->whereNotNull('acquisition_source')->latest()->limit(20)->get();
        return view('crm.acquisition',[
            'stages'=>$stages,'recent'=>$recent,'customers'=>$customers,
            'sources'=>CustomerAcquisitionService::SOURCES,'allowedStages'=>array_values(array_filter(CustomerAcquisitionService::STAGES,fn($s)=>$s!=='CONVERTED')),
        ]);
    }

    public function store(Request $request,CustomerAcquisitionService $service): RedirectResponse
    {
        $user=$this->user($request);
        $data=$request->validate([
            'name'=>['required','string','max:160'],'company'=>['nullable','string','max:190'],'email'=>['nullable','email','max:190'],
            'mobile'=>['nullable','string','max:40'],'commercial_registration'=>['nullable','string','max:80'],
            'source_channel'=>['required','string','max:40'],'source_detail'=>['nullable','string','max:160'],
            'service_interest'=>['nullable','string','max:120'],'city'=>['nullable','string','max:100'],'inquiry_notes'=>['nullable','string','max:3000'],
        ]);
        $result=$service->capture((int)$user->tenant_id,(int)$user->organization_id,(int)$user->id,$data);
        if($result['type']==='CUSTOMER') return back()->with('status','Existing customer matched: '.$result['customer']->customer_code.' · '.$result['customer']->name);
        if(!$result['created']) return back()->with('status','Existing lead matched: '.$result['lead']->lead_no.' · no duplicate was created.');
        return back()->with('status','Lead '.$result['lead']->lead_no.' created from '.str_replace('_',' ',$result['lead']->source_channel).'.');
    }

    public function stage(Request $request,CrmLead $lead,CustomerAcquisitionService $service): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless((int)$lead->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['lifecycle_stage'=>['required','string','max:30']]);
        $service->stage($lead,$data['lifecycle_stage'],(int)$user->id);
        return back()->with('status',$lead->lead_no.' moved to '.str_replace('_',' ',$data['lifecycle_stage']).'.');
    }

    public function convert(Request $request,CrmLead $lead,CustomerAcquisitionService $service): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless((int)$lead->tenant_id===(int)$user->tenant_id,404);
        $customer=$service->convert($lead,(int)$user->id);
        return back()->with('status',$lead->lead_no.' converted to customer '.$customer->customer_code.' · '.$customer->name.'.');
    }
}
