<?php

namespace Tests\Feature;

use App\Models\PublicServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRequestWizardTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $intent, string $subtype, array $overrides=[]): array
    {
        return array_merge([
            'lang'=>'en','request_intent'=>$intent,'request_subtype'=>$subtype,
            'site_name'=>'Ministry of Health','company_name'=>'ABC Company','responsible_person'=>'Ahmed Ali',
            'mobile'=>'02123332','email'=>'ahmed@example.test','site_area'=>'Riyadh','site_city'=>'Riyadh',
            'latitude'=>24.7136,'longitude'=>46.6753,'asset_type'=>'UPS','equipment_brand'=>'Schneider','equipment_model'=>'XXXX',
            'service_category'=>'Preventive Maintenance','urgency'=>'URGENT','requested_date'=>now()->addDay()->toDateString(),
            'requested_time'=>'10:00','details'=>'Annual UPS maintenance',
        ], $overrides);
    }

    public function test_public_request_page_is_single_page_bilingual_and_uses_final_header_layout(): void
    {
        $this->get('/request-service')->assertOk()
            ->assertSee('خدمة أسرع تبدأ بطلب أوضح', false)
            ->assertSee('خطوات بسيطة تساعد فريق UNIFCO على فهم الخدمة المطلوبة بشكل سريع وواضح.', false)
            ->assertSee('class="request-pill"', false)
            ->assertSee('class="primary-nav"', false)
            ->assertSee('class="brandzone"', false)
            ->assertSee('طلب خدمة', false)
            ->assertSee('طلب عرض سعر', false)
            ->assertSee('عرض سعر قطع غيار', false)
            ->assertSee('عرض سعر عقد صيانة', false)
            ->assertSee('خدمات الصيانة العادية', false)
            ->assertSee('خدمات الصيانة الطارئة', false)
            ->assertSee('id="technicalConsultationSubtype"', false)
            ->assertSee('#map{height:220px', false)
            ->assertSee('width:calc((100% - 22px)/3)', false)
            ->assertSee('بيانات طلب عرض السعر', false)
            ->assertDontSee('id="subConsult"', false)
            ->assertDontSee('UNIFCO · ONE FACILITY SHOP', false)
            ->assertDontSee('كل تفاصيل الطلب في صفحة واحدة', false)
            ->assertDontSee('مراجعة وإرسال', false);

        $this->get('/request-service?lang=en')->assertOk()
            ->assertSee('A faster service starts with a clearer request', false)
            ->assertSee('Simple steps help the UNIFCO team understand the required service quickly and clearly.', false)
            ->assertSee('Request Service', false)
            ->assertSee('Home', false)
            ->assertSee('About Us', false)
            ->assertSee('Services', false)
            ->assertSee('Our Clients', false)
            ->assertSee('Projects', false)
            ->assertSee('Careers', false)
            ->assertSee('Contact us', false)
            ->assertSee('Request a Quotation', false)
            ->assertSee('Spare Parts Quotation', false)
            ->assertSee('Maintenance Contract Quotation', false)
            ->assertSee('Routine Maintenance', false)
            ->assertSee('Emergency Maintenance', false)
            ->assertSee('Quotation Request Details', false)
            ->assertDontSee('UNIFCO · ONE FACILITY SHOP', false);
    }

    public function test_ticket_prefixes_and_serial_sequence_are_generated_as_requested(): void
    {
        $this->post('/service-requests', $this->payload('QUOTATION','SPARE_PARTS_QUOTE'))->assertRedirect();
        $this->assertDatabaseHas('public_service_requests',['reference_no'=>'UNQ-926000001','request_subtype'=>'SPARE_PARTS_QUOTE']);

        $this->post('/service-requests', $this->payload('QUOTATION','MAINTENANCE_CONTRACT_QUOTE'))->assertRedirect();
        $this->assertDatabaseHas('public_service_requests',['reference_no'=>'UNM-926000002','request_subtype'=>'MAINTENANCE_CONTRACT_QUOTE']);

        $this->post('/service-requests', $this->payload('SERVICE_REQUEST','ROUTINE_MAINTENANCE'))->assertRedirect();
        $this->assertDatabaseHas('public_service_requests',['reference_no'=>'UNRM-926000003','request_type'=>'SERVICE_REQUEST']);

        $this->post('/service-requests', $this->payload('SERVICE_REQUEST','URGENT_MAINTENANCE'))->assertRedirect();
        $this->assertDatabaseHas('public_service_requests',['reference_no'=>'UNUM-926000004','request_type'=>'EMERGENCY_MAINTENANCE','urgency'=>'EMERGENCY']);

        $this->post('/service-requests', $this->payload('CONSULTATION','TECHNICAL_CONSULTATION'))->assertRedirect();
        $this->assertDatabaseHas('public_service_requests',['reference_no'=>'UNC-926000005','request_type'=>'CONSULTATION']);
    }

    public function test_ticket_receipt_shows_request_details_and_appointment(): void
    {
        $this->post('/service-requests', $this->payload('QUOTATION','SPARE_PARTS_QUOTE'))->assertRedirect();
        $record=PublicServiceRequest::firstOrFail();
        $this->get('/request-received/'.$record->reference_no.'?lang=en')->assertOk()
            ->assertSee('UNQ-926000001', false)
            ->assertSee('Spare Parts Quotation', false)
            ->assertSee('Ministry of Health', false)
            ->assertSee('Schneider', false)
            ->assertSee('10:00', false);
    }
}
