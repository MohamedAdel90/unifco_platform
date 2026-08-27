<?php

namespace Tests\Feature;

use App\Models\{CrmLead,Customer,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAcquisitionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'ACQ','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'ACQ-HQ','status'=>'ACTIVE']);
        return User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'CRM Admin','email'=>'acquisition.admin@example.test',
            'password'=>'StrongPassword123','role'=>'ADMIN','status'=>'ACTIVE',
        ]);
    }

    private function userFor(User $admin,string $role,string $email): User
    {
        return User::create([
            'tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'name'=>$role.' User','email'=>$email,
            'password'=>'StrongPassword123','role'=>$role,'status'=>'ACTIVE',
        ]);
    }

    public function test_field_marketing_capture_creates_lead_not_customer_and_tracks_owner_followup(): void
    {
        $user=$this->admin();
        $sales=$this->userFor($user,'SALES','sales.acquisition@example.test');
        $this->actingAs($user)->post('/crm/acquisition/leads',[
            'name'=>'Ahmed','company'=>'Field Prospect LLC','mobile'=>'0501112233','source_channel'=>'FIELD_MARKETING',
            'service_interest'=>'Maintenance','city'=>'Riyadh','inquiry_notes'=>'Visited during field campaign.',
            'assigned_to'=>$sales->id,'next_follow_up_at'=>'2026-09-01 10:00:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('crm_leads',[
            'company'=>'Field Prospect LLC','source_channel'=>'FIELD_MARKETING','lifecycle_stage'=>'LEAD','assigned_to'=>$sales->id,
            'conversion_approval_status'=>'NOT_REQUESTED',
        ]);
        $this->assertDatabaseMissing('customers',['name'=>'Field Prospect LLC']);
    }

    public function test_whatsapp_duplicate_lead_is_reused(): void
    {
        $user=$this->admin();
        foreach([1,2] as $attempt){
            $this->actingAs($user)->post('/crm/acquisition/leads',[
                'name'=>'Sara','company'=>'Message Prospect','mobile'=>'+966 50 222 3344','source_channel'=>'WHATSAPP','inquiry_notes'=>'WhatsApp inquiry '.$attempt,
            ])->assertRedirect();
        }
        $this->assertSame(1,CrmLead::where('tenant_id',$user->tenant_id)->where('mobile','+966 50 222 3344')->count());
    }

    public function test_existing_customer_match_does_not_create_duplicate_lead(): void
    {
        $user=$this->admin();
        Customer::create([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_code'=>'CUS-EXIST','name'=>'Existing Co',
            'commercial_registration'=>'1010999999','phone'=>'0503334455','status'=>'ACTIVE','onboarding_status'=>'ACTIVE','onboarding_review_status'=>'APPROVED',
        ]);
        $this->actingAs($user)->post('/crm/acquisition/leads',[
            'name'=>'Existing Contact','company'=>'Existing Co','commercial_registration'=>'1010999999','source_channel'=>'PHONE',
        ])->assertRedirect()->assertSessionHas('status',fn($value)=>str_contains($value,'Existing customer matched'));
        $this->assertSame(0,CrmLead::where('tenant_id',$user->tenant_id)->count());
    }

    public function test_qualified_lead_requires_approval_then_converts_once_and_preserves_source(): void
    {
        $admin=$this->admin();
        $sales=$this->userFor($admin,'SALES','sales.convert@example.test');
        $this->actingAs($sales)->post('/crm/acquisition/leads',[
            'name'=>'Mona','company'=>'Qualified Facilities Co','email'=>'mona@qualified.test','mobile'=>'0504445566','source_channel'=>'REFERRAL',
            'source_detail'=>'Existing customer referral','service_interest'=>'Technical Consultation','city'=>'Jeddah',
        ])->assertRedirect();
        $lead=CrmLead::where('company','Qualified Facilities Co')->firstOrFail();

        $this->actingAs($sales)->post('/crm/acquisition/leads/'.$lead->id.'/stage',['lifecycle_stage'=>'QUALIFIED'])->assertRedirect();
        $this->actingAs($sales)->post('/crm/acquisition/leads/'.$lead->id.'/request-conversion',['notes'=>'Qualified opportunity with confirmed need.'])->assertRedirect();
        $lead->refresh();
        $this->assertSame('PENDING',$lead->conversion_approval_status);

        $this->actingAs($sales)->post('/crm/acquisition/leads/'.$lead->id.'/review-conversion',['decision'=>'APPROVE'])->assertForbidden();
        $this->actingAs($admin)->post('/crm/acquisition/leads/'.$lead->id.'/review-conversion',['decision'=>'APPROVE','notes'=>'Identity and qualification reviewed.'])->assertRedirect();
        $this->actingAs($sales)->post('/crm/acquisition/leads/'.$lead->id.'/convert')->assertRedirect();

        $lead->refresh();
        $customer=Customer::findOrFail($lead->converted_customer_id);
        $this->assertSame('ONBOARDING',$customer->status);
        $this->assertSame('PENDING',$customer->onboarding_review_status);
        $this->assertSame('REFERRAL',$customer->acquisition_source);
        $this->assertSame($lead->id,(int)$customer->origin_lead_id);
        $this->assertSame('CONVERTED',$lead->lifecycle_stage);

        $customerCount=Customer::where('tenant_id',$admin->tenant_id)->count();
        $this->actingAs($sales)->post('/crm/acquisition/leads/'.$lead->id.'/convert')->assertRedirect();
        $this->assertSame($customerCount,Customer::where('tenant_id',$admin->tenant_id)->count());
    }

    public function test_admin_onboarding_review_activates_customer(): void
    {
        $admin=$this->admin();
        $customer=Customer::create([
            'tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'customer_code'=>'CUS-ONB','name'=>'Onboarding Co',
            'status'=>'ONBOARDING','onboarding_status'=>'PENDING','onboarding_review_status'=>'PENDING','acquisition_source'=>'EMAIL',
        ]);
        $this->actingAs($admin)->post('/crm/acquisition/customers/'.$customer->id.'/review-onboarding',[
            'decision'=>'APPROVED','notes'=>'Commercial identity verified.',
        ])->assertRedirect();
        $customer->refresh();
        $this->assertSame('ACTIVE',$customer->status);
        $this->assertSame('ACTIVE',$customer->onboarding_status);
        $this->assertSame('APPROVED',$customer->onboarding_review_status);
    }

    public function test_customer_user_cannot_access_internal_acquisition_workspace(): void
    {
        $user=$this->admin();
        $customer=Customer::create([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_code'=>'CUS-PORTAL','name'=>'Portal Co','status'=>'ACTIVE','onboarding_status'=>'ACTIVE','onboarding_review_status'=>'APPROVED',
        ]);
        $portal=User::create([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$customer->id,'name'=>'Portal User',
            'email'=>'portal.acq@example.test','password'=>'StrongPassword123','role'=>'CUSTOMER','status'=>'ACTIVE','customer_portal_role'=>'CUSTOMER_ADMIN',
        ]);
        $this->actingAs($portal)->get('/crm/acquisition')->assertForbidden();
    }
}
