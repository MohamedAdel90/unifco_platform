<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteGuestRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_is_available_without_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('UNIFCO')
            ->assertSee('طلب عرض سعر')
            ->assertSee('صيانة طارئة');
    }

    public function test_guest_can_submit_quotation_request_with_only_company_contact_at_final_step(): void
    {
        $response = $this->post('/service-requests', [
            'request_type' => 'QUOTATION',
            'service_category' => 'مولدات كهربائية',
            'subject' => 'توريد وصيانة مولد',
            'details' => 'نحتاج عرض سعر لتوريد وصيانة مولد للموقع.',
            'site_city' => 'الرياض',
            'company_name' => 'Test Company',
            'commercial_registration' => '1010000000',
            'email' => 'procurement@example.test',
            'mobile' => '0500000000',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('public_service_requests', [
            'request_type' => 'QUOTATION',
            'company_name' => 'Test Company',
            'commercial_registration' => '1010000000',
            'status' => 'NEW',
        ]);
    }

    public function test_guest_emergency_request_is_marked_emergency(): void
    {
        $this->post('/service-requests', [
            'request_type' => 'EMERGENCY_MAINTENANCE',
            'service_category' => 'لوحات ATS',
            'subject' => 'ATS failure',
            'details' => 'The ATS is not transferring to the generator.',
            'site_city' => 'المدينة المنورة',
            'company_name' => 'Emergency Client',
            'commercial_registration' => '1010000001',
            'email' => 'ops@example.test',
            'mobile' => '0500000001',
        ])->assertRedirect();

        $this->assertDatabaseHas('public_service_requests', [
            'request_type' => 'EMERGENCY_MAINTENANCE',
            'urgency' => 'EMERGENCY',
            'company_name' => 'Emergency Client',
        ]);
    }
}
