<?php

namespace Tests\Feature;

use App\Models\{CrmLead,Customer};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteGuestRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_is_available_without_login(): void
    {
        $this->get('/')->assertOk()->assertSee('UNIFCO')->assertSee('طلب عرض سعر')->assertSee('صيانة طارئة');
    }

    public function test_guest_request_service_page_is_available(): void
    {
        $this->get('/request-service')->assertOk()->assertSee('طلب خدمة');
    }

    public function test_guest_quote_uses_unified_acquisition_engine_and_converts_to_opportunity_and_quotation(): void
    {
        $this->post('/service-requests', [
            'request_type' => 'QUOTATION', 'service_category' => 'مولدات كهربائية',
            'subject' => 'توريد وصيانة مولد', 'details' => 'نحتاج عرض سعر لتوريد وصيانة مولد للموقع.',
            'site_city' => 'الرياض', 'company_name' => 'Test Company', 'commercial_registration' => '1010000000',
            'email' => 'procurement@example.test', 'mobile' => '0500000000',
        ])->assertRedirect();

        $this->assertDatabaseHas('public_service_requests', ['request_type'=>'QUOTATION','company_name'=>'Test Company','status'=>'CONVERTED_TO_QUOTATION']);
        $lead=CrmLead::where('company','Test Company')->firstOrFail();
        $customer=Customer::where('commercial_registration','1010000000')->firstOrFail();

        $this->assertSame('WEBSITE',$lead->source_channel);
        $this->assertSame('WEBSITE',$lead->source);
        $this->assertSame('PUBLIC_SERVICE_REQUEST',$lead->source_detail);
        $this->assertSame('CONVERTED',$lead->lifecycle_stage);
        $this->assertSame('SYSTEM_APPROVED',$lead->conversion_approval_status);
        $this->assertSame($customer->id,(int)$lead->converted_customer_id);
        $this->assertSame('WEBSITE',$customer->acquisition_source);
        $this->assertSame($lead->id,(int)$customer->origin_lead_id);

        $this->assertDatabaseHas('crm_opportunities', [
            'lead_id'=>$lead->id,'customer_id'=>$customer->id,'name'=>'توريد وصيانة مولد','stage'=>'QUALIFICATION','status'=>'OPEN'
        ]);
        $this->assertDatabaseHas('crm_quotations', ['customer_id'=>$customer->id,'currency'=>'SAR','amount'=>0,'status'=>'DRAFT']);
    }

    public function test_repeat_website_inquiry_reuses_existing_customer_without_duplicate_lead(): void
    {
        foreach ([1,2] as $attempt) {
            $this->post('/service-requests', [
                'request_type' => 'QUOTATION', 'service_category' => 'Electrical Maintenance',
                'subject' => 'Repeat request '.$attempt, 'details' => 'Follow-up website request '.$attempt,
                'site_city' => 'Riyadh', 'company_name' => 'Repeat Website Company', 'commercial_registration' => '1010000099',
                'email' => 'repeat.web@example.test', 'mobile' => '0500099999',
            ])->assertRedirect();
        }

        $this->assertSame(1,Customer::where('commercial_registration','1010000099')->count());
        $this->assertSame(1,CrmLead::where('commercial_registration','1010000099')->count());
        $customer=Customer::where('commercial_registration','1010000099')->firstOrFail();
        $this->assertSame(2,\App\Models\CrmOpportunity::where('customer_id',$customer->id)->count());
    }

    public function test_guest_emergency_is_converted_to_service_request_and_work_order(): void
    {
        $this->post('/service-requests', [
            'request_type' => 'EMERGENCY_MAINTENANCE', 'service_category' => 'لوحات ATS',
            'subject' => 'ATS failure', 'details' => 'The ATS is not transferring to the generator.',
            'site_city' => 'المدينة المنورة', 'company_name' => 'Emergency Client', 'commercial_registration' => '1010000001',
            'email' => 'ops@example.test', 'mobile' => '0500000001',
        ])->assertRedirect();

        $this->assertDatabaseHas('public_service_requests', ['request_type'=>'EMERGENCY_MAINTENANCE','urgency'=>'EMERGENCY','status'=>'CONVERTED_TO_WORK_ORDER']);
        $this->assertDatabaseHas('service_requests', ['company_name'=>'Emergency Client','priority'=>'EMERGENCY','status'=>'OPEN']);
        $this->assertDatabaseHas('work_orders', ['maintenance_type'=>'CORRECTIVE','priority'=>'EMERGENCY','status'=>'OPEN']);
        $this->assertDatabaseHas('assets', ['asset_code'=>'PUBLIC-SERVICE-INBOX','name'=>'Public Emergency Service Intake']);
        $this->assertDatabaseHas('crm_leads', ['company'=>'Emergency Client','source_channel'=>'WEBSITE','lifecycle_stage'=>'CONVERTED']);
    }
}
