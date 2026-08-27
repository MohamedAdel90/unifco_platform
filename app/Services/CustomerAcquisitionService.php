<?php

namespace App\Services;

use App\Models\{CrmLead,Customer};
use Illuminate\Support\Facades\DB;

class CustomerAcquisitionService
{
    public const SOURCES=['FIELD_MARKETING','WEBSITE','WHATSAPP','EMAIL','PHONE','REFERRAL','TENDER','SOCIAL_MEDIA','PARTNER','WALK_IN','EXISTING_RELATIONSHIP'];
    public const STAGES=['LEAD','CONTACTED','QUALIFIED','OPPORTUNITY','NURTURE','DISQUALIFIED','CONVERTED'];

    public function findCustomer(int $tenantId,array $data): ?Customer
    {
        $base=Customer::where('tenant_id',$tenantId);
        if($cr=$this->clean($data['commercial_registration']??null)){
            if($customer=(clone $base)->where('commercial_registration',$cr)->first()) return $customer;
        }
        if($email=$this->email($data['email']??null)){
            if($customer=(clone $base)->whereRaw('LOWER(email)=?',[$email])->first()) return $customer;
        }
        if($phone=$this->phone($data['mobile']??$data['phone']??null)){
            $customers=(clone $base)->whereNotNull('phone')->get();
            if($customer=$customers->first(fn($c)=>$this->phone($c->phone)===$phone)) return $customer;
        }
        if($company=$this->name($data['company']??$data['company_name']??null)){
            $customers=(clone $base)->whereNotNull('name')->get();
            if($customer=$customers->first(fn($c)=>$this->name($c->name)===$company)) return $customer;
        }
        return null;
    }

    public function findLead(int $tenantId,array $data): ?CrmLead
    {
        $base=CrmLead::where('tenant_id',$tenantId)->whereNotIn('lifecycle_stage',['DISQUALIFIED','CONVERTED']);
        if($cr=$this->clean($data['commercial_registration']??null)){
            if($lead=(clone $base)->where('commercial_registration',$cr)->first()) return $lead;
        }
        if($email=$this->email($data['email']??null)){
            if($lead=(clone $base)->whereRaw('LOWER(email)=?',[$email])->first()) return $lead;
        }
        if($phone=$this->phone($data['mobile']??null)){
            $leads=(clone $base)->whereNotNull('mobile')->get();
            if($lead=$leads->first(fn($l)=>$this->phone($l->mobile)===$phone)) return $lead;
        }
        return null;
    }

    public function capture(int $tenantId,int $organizationId,?int $userId,array $data): array
    {
        if($customer=$this->findCustomer($tenantId,$data)) return ['type'=>'CUSTOMER','customer'=>$customer,'lead'=>null,'created'=>false];
        if($lead=$this->findLead($tenantId,$data)) return ['type'=>'LEAD','customer'=>null,'lead'=>$lead,'created'=>false];

        $source=strtoupper($data['source_channel']??'FIELD_MARKETING');
        abort_unless(in_array($source,self::SOURCES,true),422,'Unsupported acquisition source.');
        $next=((int)CrmLead::where('tenant_id',$tenantId)->max('id'))+1;
        $lead=CrmLead::create([
            'tenant_id'=>$tenantId,'organization_id'=>$organizationId,'lead_no'=>'LD-'.now()->format('Y').'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT),
            'name'=>$data['name'],'company'=>$data['company']??null,'email'=>$this->email($data['email']??null),'mobile'=>$data['mobile']??null,
            'commercial_registration'=>$data['commercial_registration']??null,'source'=>$source,'source_channel'=>$source,'source_detail'=>$data['source_detail']??null,
            'status'=>'NEW','lifecycle_stage'=>'LEAD','service_interest'=>$data['service_interest']??null,'city'=>$data['city']??null,
            'inquiry_notes'=>$data['inquiry_notes']??null,'first_touch_at'=>now(),'first_touch_user_id'=>$userId,'created_by'=>$userId,
            'assigned_to'=>$data['assigned_to']??$userId,'next_follow_up_at'=>$data['next_follow_up_at']??null,
            'duplicate_review_status'=>'CLEAR','conversion_approval_status'=>'NOT_REQUESTED',
        ]);
        return ['type'=>'LEAD','customer'=>null,'lead'=>$lead,'created'=>true];
    }

    public function linkSystemCustomer(CrmLead $lead,Customer $customer): CrmLead
    {
        abort_unless((int)$lead->tenant_id===(int)$customer->tenant_id,422,'Lead and customer tenant mismatch.');
        $lead->update([
            'lifecycle_stage'=>'CONVERTED','status'=>'CONVERTED','converted_customer_id'=>$customer->id,
            'converted_at'=>now(),'conversion_approval_status'=>'SYSTEM_APPROVED',
        ]);
        if(!$customer->origin_lead_id){
            $customer->update([
                'origin_lead_id'=>$lead->id,'acquisition_source'=>$lead->source_channel ?: $lead->source,
                'first_touch_at'=>$lead->first_touch_at ?: now(),
            ]);
        }
        return $lead->refresh();
    }

    public function stage(CrmLead $lead,string $stage,int $userId): CrmLead
    {
        $stage=strtoupper($stage);
        abort_unless(in_array($stage,self::STAGES,true) && $stage!=='CONVERTED',422,'Invalid lifecycle stage.');
        abort_if($lead->lifecycle_stage==='CONVERTED',422,'Converted leads cannot be moved back.');
        $changes=['lifecycle_stage'=>$stage,'status'=>$stage];
        if($stage==='QUALIFIED' && !$lead->qualified_at) $changes+=['qualified_at'=>now(),'qualified_by'=>$userId];
        if(!in_array($stage,['QUALIFIED','OPPORTUNITY'],true)) $changes['conversion_approval_status']='NOT_REQUESTED';
        $lead->update($changes);
        return $lead->refresh();
    }

    public function assignFollowUp(CrmLead $lead,?int $assignedTo,?string $nextFollowUpAt): CrmLead
    {
        abort_if($lead->lifecycle_stage==='CONVERTED',422,'Converted leads cannot be reassigned.');
        $lead->update(['assigned_to'=>$assignedTo,'next_follow_up_at'=>$nextFollowUpAt]);
        return $lead->refresh();
    }

    public function requestConversion(CrmLead $lead,int $userId,?string $notes=null): CrmLead
    {
        abort_unless(in_array($lead->lifecycle_stage,['QUALIFIED','OPPORTUNITY'],true),422,'Lead must be qualified before requesting conversion.');
        abort_if($lead->duplicate_review_status==='REVIEW',422,'Duplicate review must be resolved before conversion.');
        $lead->update([
            'conversion_approval_status'=>'PENDING','conversion_requested_by'=>$userId,'conversion_requested_at'=>now(),
            'conversion_approved_by'=>null,'conversion_approved_at'=>null,'conversion_review_notes'=>$notes,
        ]);
        return $lead->refresh();
    }

    public function reviewConversion(CrmLead $lead,int $reviewerId,bool $approve,?string $notes=null): CrmLead
    {
        abort_unless($lead->conversion_approval_status==='PENDING',422,'Conversion approval is not pending.');
        $lead->update([
            'conversion_approval_status'=>$approve?'APPROVED':'REJECTED',
            'conversion_approved_by'=>$reviewerId,'conversion_approved_at'=>now(),'conversion_review_notes'=>$notes,
        ]);
        return $lead->refresh();
    }

    public function convert(CrmLead $lead,int $userId): Customer
    {
        if($lead->converted_customer_id) return Customer::findOrFail($lead->converted_customer_id);
        abort_unless(in_array($lead->lifecycle_stage,['QUALIFIED','OPPORTUNITY'],true),422,'Lead must be qualified before customer conversion.');
        abort_unless($lead->conversion_approval_status==='APPROVED',422,'Customer conversion requires approved conversion review.');

        return DB::transaction(function() use($lead,$userId){
            $lead=CrmLead::lockForUpdate()->findOrFail($lead->id);
            if($lead->converted_customer_id) return Customer::findOrFail($lead->converted_customer_id);
            if($existing=$this->findCustomer((int)$lead->tenant_id,[
                'commercial_registration'=>$lead->commercial_registration,'email'=>$lead->email,'mobile'=>$lead->mobile,'company'=>$lead->company,
            ])){
                $customer=$existing;
            } else {
                $next=((int)Customer::where('tenant_id',$lead->tenant_id)->max('id'))+1;
                $customer=Customer::create([
                    'tenant_id'=>$lead->tenant_id,'organization_id'=>$lead->organization_id,'customer_code'=>'CUS-'.str_pad((string)$next,6,'0',STR_PAD_LEFT),
                    'name'=>$lead->company ?: $lead->name,'commercial_registration'=>$lead->commercial_registration,'email'=>$lead->email,
                    'contact_name'=>$lead->name,'phone'=>$lead->mobile,'city'=>$lead->city,'country'=>'Saudi Arabia','status'=>'ONBOARDING','onboarding_status'=>'PENDING',
                    'onboarding_review_status'=>'PENDING','acquisition_source'=>$lead->source_channel ?: $lead->source,'origin_lead_id'=>$lead->id,'first_touch_at'=>$lead->first_touch_at,
                    'converted_by'=>$userId,'converted_at'=>now(),
                ]);
            }
            $lead->update(['lifecycle_stage'=>'CONVERTED','status'=>'CONVERTED','converted_customer_id'=>$customer->id,'converted_at'=>now(),'converted_by'=>$userId]);
            return $customer;
        });
    }

    public function reviewOnboarding(Customer $customer,int $reviewerId,string $decision,?string $notes=null): Customer
    {
        $decision=strtoupper($decision);
        abort_unless(in_array($decision,['APPROVED','NEEDS_INFO','REJECTED'],true),422,'Invalid onboarding review decision.');
        $changes=['onboarding_review_status'=>$decision,'onboarding_reviewed_by'=>$reviewerId,'onboarding_reviewed_at'=>now(),'onboarding_review_notes'=>$notes];
        if($decision==='APPROVED') $changes+=['onboarding_status'=>'ACTIVE','status'=>'ACTIVE'];
        elseif($decision==='REJECTED') $changes+=['onboarding_status'=>'REJECTED','status'=>'INACTIVE'];
        else $changes['onboarding_status']='PENDING';
        $customer->update($changes);
        return $customer->refresh();
    }

    private function clean(?string $value): ?string { $value=trim((string)$value); return $value===''?null:$value; }
    private function email(?string $value): ?string { $value=mb_strtolower(trim((string)$value)); return $value===''?null:$value; }
    private function phone(?string $value): ?string { $value=preg_replace('/\D+/','',(string)$value); return $value===''?null:ltrim($value,'0'); }
    private function name(?string $value): ?string { $value=mb_strtolower(trim(preg_replace('/\s+/u',' ',(string)$value))); return $value===''?null:$value; }
}
