<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\{CrmLead,Customer,User};
use App\Services\CustomerAcquisitionService;
use App\Support\UnifcoContact;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerAcquisitionController extends Controller
{
    private const ROLES=['ADMIN','CRM_MANAGER','SALES','MARKETING','CUSTOMER_SERVICE','TENDERS_CONTRACTS'];
    private const REVIEW_ROLES=['ADMIN','CRM_MANAGER'];

    private function user(Request $request): User
    {
        $user=$request->user();
        abort_unless($user && in_array($user->role,self::ROLES,true),403,'This role cannot manage customer acquisition.');
        return $user;
    }

    private function reviewer(Request $request): User
    {
        $user=$this->user($request);
        abort_unless(in_array($user->role,self::REVIEW_ROLES,true),403,'Only CRM reviewers can approve duplicate reviews, conversions or onboarding.');
        return $user;
    }

    public function index(Request $request): View
    {
        $user=$this->user($request);
        $base=CrmLead::where('tenant_id',$user->tenant_id);
        $stages=[];
        foreach(CustomerAcquisitionService::STAGES as $stage) $stages[$stage]=(clone $base)->where('lifecycle_stage',$stage)->count();
        $recent=(clone $base)->latest()->limit(60)->get();
        $customers=Customer::where('tenant_id',$user->tenant_id)->whereNotNull('acquisition_source')->latest()->limit(30)->get();
        $assignees=User::where('tenant_id',$user->tenant_id)->where('status','ACTIVE')->whereIn('role',self::ROLES)->orderBy('name')->get(['id','name','role']);
        return view('crm.acquisition',[
            'stages'=>$stages,'recent'=>$recent,'customers'=>$customers,'assignees'=>$assignees,
            'sources'=>CustomerAcquisitionService::SOURCES,'allowedStages'=>array_values(array_filter(CustomerAcquisitionService::STAGES,fn($s)=>$s!=='CONVERTED')),
            'canReview'=>in_array($user->role,self::REVIEW_ROLES,true),
            'pendingDuplicates'=>(clone $base)->where('duplicate_review_status','REVIEW')->count(),
            'pendingConversions'=>(clone $base)->where('conversion_approval_status','PENDING')->count(),
            'overdueFollowUps'=>(clone $base)->whereNotIn('lifecycle_stage',['CONVERTED','DISQUALIFIED'])->whereNotNull('next_follow_up_at')->where('next_follow_up_at','<',now())->count(),
            'pendingOnboarding'=>Customer::where('tenant_id',$user->tenant_id)->where('status','ONBOARDING')->where('onboarding_review_status','PENDING')->count(),
            'officialWhatsapp'=>UnifcoContact::WHATSAPP_DISPLAY,'officialEmail'=>UnifcoContact::EMAIL,
        ]);
    }

    public function store(Request $request,CustomerAcquisitionService $service): RedirectResponse
    {
        $user=$this->user($request);
        $data=$request->validate([
            'name'=>['required','string','max:160'],'company'=>['nullable','string','max:190'],'email'=>['nullable','email','max:190'],
            'mobile'=>['nullable','string','max:40'],'commercial_registration'=>['nullable','string','max:80'],
            'source_channel'=>['required',Rule::in(CustomerAcquisitionService::SOURCES)],'source_detail'=>['nullable','string','max:160'],
            'service_interest'=>['nullable','string','max:120'],'city'=>['nullable','string','max:100'],'inquiry_notes'=>['nullable','string','max:3000'],
            'assigned_to'=>['nullable','integer'],'next_follow_up_at'=>['nullable','date'],
        ]);
        if(empty($data['source_detail']) && $data['source_channel']==='WHATSAPP') $data['source_detail']='UNIFCO WhatsApp '.UnifcoContact::WHATSAPP_DISPLAY;
        if(empty($data['source_detail']) && $data['source_channel']==='EMAIL') $data['source_detail']='UNIFCO Email '.UnifcoContact::EMAIL;
        if(!empty($data['assigned_to'])) User::where('tenant_id',$user->tenant_id)->whereIn('role',self::ROLES)->findOrFail($data['assigned_to']);
        $result=$service->capture((int)$user->tenant_id,(int)$user->organization_id,(int)$user->id,$data);
        if($result['type']==='CUSTOMER') return back()->with('status','Existing customer matched: '.$result['customer']->customer_code.' · '.$result['customer']->name);
        if(!$result['created']) return back()->with('status','Existing lead matched: '.$result['lead']->lead_no.' · no duplicate was created.');
        if($result['lead']->duplicate_review_status==='REVIEW') return back()->with('status','Lead '.$result['lead']->lead_no.' created and flagged for duplicate review before conversion.');
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

    public function followUp(Request $request,CrmLead $lead,CustomerAcquisitionService $service): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless((int)$lead->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['assigned_to'=>['nullable','integer'],'next_follow_up_at'=>['nullable','date']]);
        if(!empty($data['assigned_to'])) User::where('tenant_id',$user->tenant_id)->whereIn('role',self::ROLES)->findOrFail($data['assigned_to']);
        $service->assignFollowUp($lead,$data['assigned_to']??null,$data['next_follow_up_at']??null);
        return back()->with('status',$lead->lead_no.' ownership / follow-up updated.');
    }

    public function reviewDuplicate(Request $request,CrmLead $lead,CustomerAcquisitionService $service): RedirectResponse
    {
        $user=$this->reviewer($request);
        abort_unless((int)$lead->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['decision'=>['required',Rule::in(['KEEP_SEPARATE','LINK_CUSTOMER','USE_EXISTING_LEAD'])]]);
        $service->reviewDuplicate($lead,$data['decision'],(int)$user->id);
        return back()->with('status',$lead->lead_no.' duplicate review resolved: '.str_replace('_',' ',$data['decision']).'.');
    }

    public function requestConversion(Request $request,CrmLead $lead,CustomerAcquisitionService $service): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless((int)$lead->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['notes'=>['nullable','string','max:2000']]);
        $service->requestConversion($lead,(int)$user->id,$data['notes']??null);
        return back()->with('status',$lead->lead_no.' customer conversion sent for approval.');
    }

    public function reviewConversion(Request $request,CrmLead $lead,CustomerAcquisitionService $service): RedirectResponse
    {
        $user=$this->reviewer($request);
        abort_unless((int)$lead->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['decision'=>['required',Rule::in(['APPROVE','REJECT'])],'notes'=>['nullable','string','max:2000']]);
        $service->reviewConversion($lead,(int)$user->id,$data['decision']==='APPROVE',$data['notes']??null);
        return back()->with('status',$lead->lead_no.' conversion '.strtolower($data['decision']==='APPROVE'?'approved':'rejected').'.');
    }

    public function convert(Request $request,CrmLead $lead,CustomerAcquisitionService $service): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless((int)$lead->tenant_id===(int)$user->tenant_id,404);
        $customer=$service->convert($lead,(int)$user->id);
        return back()->with('status',$lead->lead_no.' converted to customer '.$customer->customer_code.' · '.$customer->name.'. Onboarding review is now pending.');
    }

    public function reviewOnboarding(Request $request,Customer $customer,CustomerAcquisitionService $service): RedirectResponse
    {
        $user=$this->reviewer($request);
        abort_unless((int)$customer->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['decision'=>['required',Rule::in(['APPROVED','NEEDS_INFO','REJECTED'])],'notes'=>['nullable','string','max:2000']]);
        $service->reviewOnboarding($customer,(int)$user->id,$data['decision'],$data['notes']??null);
        return back()->with('status',$customer->customer_code.' onboarding review updated to '.str_replace('_',' ',$data['decision']).'.');
    }
}
