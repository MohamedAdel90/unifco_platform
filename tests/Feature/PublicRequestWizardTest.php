<?php

namespace Tests\Feature;

use App\Models\PublicServiceRequest;
use App\Services\PublicRequestPipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
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

    private function registeredAsset(): int
    {
        $tenantId = DB::table('tenants')->insertGetId(['name'=>'UNIFCO Test','code'=>'UNIFCO-TEST','status'=>'ACTIVE','created_at'=>now(),'updated_at'=>now()]);
        $customerId = DB::table('customers')->insertGetId(['tenant_id'=>$tenantId,'customer_code'=>'C-QR-1','name'=>'Red Sea Industrial Co.','status'=>'ACTIVE','onboarding_status'=>'ACTIVE','created_at'=>now(),'updated_at'=>now()]);
        $siteId = DB::table('customer_sites')->insertGetId(['customer_id'=>$customerId,'site_code'=>'JED-S','name'=>'Jeddah South Warehouse','city'=>'Jeddah','address'=>'Industrial City, Jeddah','latitude'=>21.4858,'longitude'=>39.1925,'contact_name'=>'Site Engineer','contact_mobile'=>'0500000000','status'=>'ACTIVE','created_at'=>now(),'updated_at'=>now()]);
        $assetId = DB::table('assets')->insertGetId([
            'tenant_id'=>$tenantId,'customer_id'=>$customerId,'customer_site_id'=>$siteId,'asset_code'=>'AST-2024-000123','name'=>'Air Compressor',
            'asset_category'=>'HVAC','manufacturer'=>'Atlas Copco','model_no'=>'GA 75','qr_token'=>'qr-safe-token-123','verification_status'=>'VERIFIED',
            'lifecycle_status'=>'ACTIVE','operational_status'=>'RUNNING','status'=>'REGISTERED','acquisition_cost'=>0,'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('asset_specifications')->insert(['asset_id'=>$assetId,'spec_key'=>'serial_number','spec_label'=>'Serial Number','spec_value'=>'AC-2023-4587','created_at'=>now(),'updated_at'=>now()]);
        return $assetId;
    }

    public function test_public_request_page_is_single_page_bilingual_and_reuses_homepage_header_without_login(): void
    {
        $this->get('/request-service')->assertOk()
            ->assertSee('خدمة أسرع تبدأ بطلب أوضح', false)
            ->assertSee('خطوات بسيطة تساعد فريق UNIFCO على فهم الخدمة المطلوبة بشكل سريع وواضح.', false)
            ->assertSee('class="top request-homepage-header"', false)
            ->assertSee('class="wrap nav"', false)
            ->assertSee('class="brand-link"', false)
            ->assertSee('class="site-logo-frame"', false)
            ->assertSee('class="brand-copy"', false)
            ->assertSee('class="nav-links"', false)
            ->assertSee('class="nav-actions"', false)
            ->assertSee('class="btn red"', false)
            ->assertSee('id="requestCenterAction"', false)
            ->assertSee('طلب خدمة', false)
            ->assertDontSee('تسجيل الدخول', false)
            ->assertDontSee('class="request-pill"', false)
            ->assertDontSee('class="primary-nav"', false)
            ->assertSee('طلب عرض سعر', false)
            ->assertSee('عرض سعر قطع غيار', false)
            ->assertSee('عرض سعر عقد صيانة', false)
            ->assertSee('خدمات الصيانة العادية', false)
            ->assertSee('خدمات الصيانة الطارئة', false)
            ->assertSee('id="technicalConsultationSubtype"', false)
            ->assertSee('id="assetQrSection"', false)
            ->assertSee('مسح QR للمعدة', false)
            ->assertSee('إدخال رقم الأصل', false)
            ->assertSee('#map{height:220px', false)
            ->assertSee('public-request-camera-attachments', false)
            ->assertSee('التقاط صورة', false)
            ->assertSee('اختيار من الجهاز', false)
            ->assertDontSee('id="subConsult"', false)
            ->assertDontSee('UNIFCO · ONE FACILITY SHOP', false);

        $this->get('/request-service?lang=en')->assertOk()
            ->assertSee('A faster service starts with a clearer request', false)
            ->assertSee('Simple steps help the UNIFCO team understand the required service quickly and clearly.', false)
            ->assertSee('Request Service', false)
            ->assertSee('Scan Equipment QR', false)
            ->assertSee('enter the Asset ID manually', false)
            ->assertSee('Home', false)
            ->assertSee('About Us', false)
            ->assertSee('Services', false)
            ->assertSee('Our Clients', false)
            ->assertSee('Projects', false)
            ->assertSee('Careers', false)
            ->assertSee('Contact us', false)
            ->assertDontSee('Sign In', false)
            ->assertSee('Request a Quotation', false)
            ->assertSee('Spare Parts Quotation', false)
            ->assertSee('Maintenance Contract Quotation', false)
            ->assertSee('Routine Maintenance', false)
            ->assertSee('Emergency Maintenance', false)
            ->assertSee('Take Photo', false)
            ->assertSee('Choose from Device', false);
    }

    public function test_asset_qr_lookup_returns_registry_data_and_request_links_authoritative_asset(): void
    {
        $assetId = $this->registeredAsset();

        $this->getJson('/service-assets/lookup?key=qr-safe-token-123')->assertOk()
            ->assertJsonPath('asset.id', $assetId)
            ->assertJsonPath('asset.asset_code', 'AST-2024-000123')
            ->assertJsonPath('asset.customer_name', 'Red Sea Industrial Co.')
            ->assertJsonPath('asset.site_name', 'Jeddah South Warehouse')
            ->assertJsonPath('asset.serial_number', 'AC-2023-4587');

        $this->getJson('/service-assets/lookup?key=AST-2024-000123')->assertOk()->assertJsonPath('asset.model', 'GA 75');

        $this->post('/service-requests', $this->payload('SERVICE_REQUEST','ROUTINE_MAINTENANCE', [
            'asset_id'=>$assetId,'company_name'=>'Tampered Company','site_name'=>'Tampered Site','site_city'=>'Riyadh',
            'asset_type'=>'UPS','equipment_brand'=>'Tampered Brand','equipment_model'=>'Tampered Model',
        ]))->assertRedirect();

        $this->assertDatabaseHas('public_service_requests', [
            'asset_id'=>$assetId,'company_name'=>'Red Sea Industrial Co.','site_name'=>'Jeddah South Warehouse','site_city'=>'Jeddah',
            'asset_type'=>'HVAC','equipment_brand'=>'Atlas Copco','equipment_model'=>'GA 75',
        ]);
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

    public function test_pipeline_failure_does_not_block_ticket_reference_or_receipt_redirect(): void
    {
        $pipeline = $this->mock(PublicRequestPipelineService::class);
        $pipeline->shouldReceive('convert')->once()->andThrow(new RuntimeException('Simulated downstream CRM failure'));

        $response = $this->post('/service-requests', $this->payload('QUOTATION','SPARE_PARTS_QUOTE'));

        $response->assertRedirect('/request-received/UNQ-926000001?lang=en');
        $this->assertDatabaseHas('public_service_requests', [
            'reference_no' => 'UNQ-926000001',
            'ticket_serial' => 926000001,
            'status' => 'NEW',
        ]);
        $this->get('/request-received/UNQ-926000001?lang=en')->assertOk()->assertSee('UNQ-926000001', false);
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
