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

    public function test_field_marketing_capture_creates_lead_not_customer(): void
    {
        $user=$this->admin();
        $this->actingAs($user)->post('/crm/acquisition/leads',[
            'name'=>'Ahmed','company'=>'Field Prospect LLC','mobile'=>'0501112233','source_channel'=>'FIELD_MARKETING',
            'service_interest'=>'Maintenance','city'=>'Riyadh','inquiry_notes'=>'Visited during field campaign.',
        ])->assertRedirect();

        $this->assertDatabaseHas('crm_leads',['company'=>'Field Prospect LLC','source_channel'=>'FIELD_MARKETING','lifecycle_stage'=>'LEAD']);
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
            'commercial_registration'=>'1010999999','phone'=>'0503334455','status'=>'ACTIVE','onboarding_status'=>'ACTIVE',
        ]);
        $this->actingAs($user)->post('/crm/acquisition/leads',[
            'name'=>'Existing Contact','company'=>'Existing Co','commercial_registration'=>'1010999999','source_channel'=>'PHONE',
        ])->assertRedirect()->assertSessionHas('status',fn($value)=>str_contains($value,'Existing customer matched'));
        $this->assertSame(0,CrmLead::where('tenant_id',$user->tenant_id)->count());
    }

    public function test_qualified_lead_converts_once_and_preserves_acquisition_source(): void
    {
        $user=$this->admin();
        $this->actingAs($user)->post('/crm/acquisition/leads',[
            'name'=>'Mona','company'=>'Qualified Facilities Co','email'=>'mona@qualified.test','mobile'=>'0504445566','source_channel'=>'REFERRAL',
            'source_detail'=>'Existing customer referral','service_interest'=>'Technical Consultation','city'=>'Jeddah',
        ])->assertRedirect();
        $lead=CrmLead::where('company','Qualified Facilities Co')->firstOrFail();

        $this->actingAs($user)->post('/crm/acquisition/leads/'.$lead->id.'/stage',['lifecycle_stage'=>'QUALIFIED'])->assertRedirect();
        $this->actingAs($user)->post('/crm/acquisition/leads/'.$lead->id.'/convert')->assertRedirect();
        $lead->refresh();
        $customer=Customer::findOrFail($lead->converted_customer_id);
        $this->assertSame('ONBOARDING',$customer->status);
        $this->assertSame('REFERRAL',$customer->acquisition_source);
        $this->assertSame($lead->id,(int)$customer->origin_lead_id);
        $this->assertSame('CONVERTED',$lead->lifecycle_stage);

        $customerCount=Customer::where('tenant_id',$user->tenant_id)->count();
        $this->actingAs($user)->post('/crm/acquisition/leads/'.$lead->id.'/convert')->assertRedirect();
        $this->assertSame($customerCount,Customer::where('tenant_id',$user->tenant_id)->count());
    }

    public function test_customer_user_cannot_access_internal_acquisition_workspace(): void
    {
        $user=$this->admin();
        $customer=Customer::create([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_code'=>'CUS-PORTAL','name'=>'Portal Co','status'=>'ACTIVE','onboarding_status'=>'ACTIVE',
        ]);
        $portal=User::create([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$customer->id,'name'=>'Portal User',
            'email'=>'portal.acq@example.test','password'=>'StrongPassword123','role'=>'CUSTOMER','status'=>'ACTIVE','customer_portal_role'=>'CUSTOMER_ADMIN',
        ]);
        $this->actingAs($portal)->get('/crm/acquisition')->assertForbidden();
    }
}
