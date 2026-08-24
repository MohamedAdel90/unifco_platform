<?php

namespace Tests\Feature;

use App\Models\PublicServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRequestWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_request_page_renders_four_step_wizard(): void
    {
        $this->get('/request-service')
            ->assertOk()
            ->assertSee('خدمة أسرع تبدأ بطلب أوضح', false)
            ->assertSee('طلب عرض سعر', false)
            ->assertSee('طلب صيانة / خدمة', false)
            ->assertSee('استشارة فنية', false)
            ->assertSee('مراجعة وإرسال', false);
    }

    public function test_quotation_can_be_submitted_without_commercial_registration(): void
    {
        $response = $this->post('/service-requests', [
            'request_type' => 'QUOTATION',
            'request_intent' => 'QUOTATION',
            'service_family' => 'ELECTRICAL',
            'service_category' => 'UPS & Critical Power',
            'asset_type' => 'UPS 80 kVA',
            'subject' => 'UPS replacement quotation',
            'details' => 'Please inspect and quote replacement options.',
            'urgency' => 'NORMAL',
            'site_city' => 'Riyadh',
            'site_address' => 'Industrial Area',
            'company_name' => 'Acme Facilities',
            'responsible_person' => 'Ahmed Ali',
            'contact_role' => 'Facility Manager',
            'email' => 'ahmed@example.test',
            'mobile' => '0500000000',
        ]);

        $record = PublicServiceRequest::firstOrFail();
        $response->assertRedirect('/request-received/'.$record->reference_no);
        $this->assertStringStartsWith('RFQ-', $record->reference_no);
        $this->assertSame('QUOTATION', $record->request_intent);
        $this->assertNull($record->commercial_registration);
        $this->assertNotNull($record->crm_opportunity_id);
        $this->assertNotNull($record->crm_quotation_id);
    }

    public function test_service_request_uses_selected_priority_and_operations_pipeline(): void
    {
        $this->post('/service-requests', [
            'request_type' => 'SERVICE_REQUEST',
            'request_intent' => 'SERVICE_REQUEST',
            'service_family' => 'MAINTENANCE',
            'service_category' => 'Corrective Maintenance',
            'subject' => 'Pump abnormal vibration',
            'details' => 'Pump vibration increased during operation.',
            'urgency' => 'URGENT',
            'site_city' => 'Dammam',
            'company_name' => 'Plant Client',
            'responsible_person' => 'Site Engineer',
            'email' => 'site@example.test',
            'mobile' => '0550000000',
        ])->assertRedirect();

        $record = PublicServiceRequest::firstOrFail();
        $this->assertSame('SERVICE_REQUEST', $record->request_type);
        $this->assertSame('URGENT', $record->urgency);
        $this->assertNotNull($record->service_request_id);
        $this->assertNotNull($record->work_order_id);
    }

    public function test_success_page_contains_reference_and_next_step_copy(): void
    {
        $record = PublicServiceRequest::create([
            'reference_no'=>'RFQ-TEST-001','request_type'=>'QUOTATION','request_intent'=>'QUOTATION','service_family'=>'FACILITY','service_category'=>'Integrated Facility Management',
            'subject'=>'FM proposal','details'=>'Scope review','urgency'=>'NORMAL','site_city'=>'Riyadh','company_name'=>'Client Co','responsible_person'=>'Client Contact','email'=>'client@example.test','mobile'=>'0500000000','status'=>'NEW','submitted_at'=>now(),
        ]);
        $this->get('/request-received/'.$record->reference_no)
            ->assertOk()->assertSee('RFQ-TEST-001', false)->assertSee('ماذا يحدث الآن؟', false);
    }
}
