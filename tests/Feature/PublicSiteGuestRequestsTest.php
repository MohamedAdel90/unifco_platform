<?php

namespace Tests\Feature;

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

    public function test_guest_quote_is_converted_to_lead_opportunity_and_quotation(): void
    {
        $this->post('/service-requests', [
            'request_type' => 'QUOTATION', 'service_category' => 'مولدات كهربائية',
            'subject' => 'توريد وصيانة مولد', 'details' => 'نحتاج عرض سعر لتوريد وصيانة مولد للموقع.',
            'site_city' => 'الرياض', 'company_name' => 'Test Company', 'commercial_registration' => '1010000000',
            'email' => 'procurement@example.test', 'mobile' => '0500000000',
        ])->assertRedirect();

        $this->assertDatabaseHas('public_service_requests', ['request_type'=>'QUOTATION','company_name'=>'Test Company','status'=>'CONVERTED_TO_QUOTATION']);
        $this->assertDatabaseHas('crm_leads', ['company'=>'Test Company','commercial_registration'=>'1010000000','source'=>'PUBLIC_WEBSITE']);
        $this->assertDatabaseHas('crm_opportunities', ['name'=>'توريد وصيانة مولد','stage'=>'QUALIFICATION','status'=>'OPEN']);
        $this->assertDatabaseHas('crm_quotations', ['currency'=>'SAR','amount'=>0,'status'=>'DRAFT']);
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
    }
}
