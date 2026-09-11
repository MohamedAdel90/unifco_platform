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

    public function test_public_request_service_uses_current_maintenance_workspace(): void
    {
        $response = $this->get('/request-service')->assertOk();

        $response
            ->assertSee('طلب خدمة', false)
            ->assertSee('نموذج UNIFCO الموحد للعملاء الحاليين والجدد وجميع أنواع طلبات الخدمة.', false)
            ->assertSee('id="maintenance-form"', false)
            ->assertSee('بيانات العميل', false)
            ->assertSee('بيانات العقد', false)
            ->assertSee('الموقع والتواصل', false)
            ->assertSee('المعدة وبياناتها', false)
            ->assertSee('اختيار من معدات العقد', false)
            ->assertSee('مسح / إدخال QR', false)
            ->assertSee('رقم الأصل', false)
            ->assertSee('السيريال نمبر', false)
            ->assertSee('معدة غير تعاقدية', false)
            ->assertDontSee('خدمة أسرع تبدأ بطلب أوضح', false)
            ->assertDontSee('id="requestForm"', false);
    }

    public function test_language_switch_stays_in_same_request_workflow_and_preserves_type(): void
    {
        $this->get('/request-service?emergency=1&service=hvac')
            ->assertOk()
            ->assertSee(route('public.request-service', [
                'emergency' => 1,
                'service' => 'hvac',
                'lang' => 'en',
            ]))
            ->assertSee('data-language-switch="en"', false);

        $this->get('/request-service?quotation=1&lang=en')
            ->assertOk()
            ->assertSee('/request-service?quotation=1', false)
            ->assertSee('data-language-switch="ar"', false);
    }

    public function test_english_request_page_localizes_the_form_and_direction(): void
    {
        $this->get('/request-service?lang=en')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('id="unifco-public-request-english-localization"', false)
            ->assertSee("'بيانات العميل':'Customer Information'", false)
            ->assertSee("el.name==='lang')el.value='en'", false);
    }

    public function test_customer_lookup_card_is_compact_and_has_no_waiting_placeholder(): void
    {
        $this->get('/request-service')
            ->assertOk()
            ->assertSee('unifco-customer-context-layout-v16', false)
            ->assertSee('uf-customer-lookup-intro', false)
            ->assertSee('align-self:start!important', false)
            ->assertSee('height:auto!important', false)
            ->assertSee("legacySummary?.classList.add('uf-legacy-customer-summary')", false)
            ->assertSee("if(['بانتظار إدخال رقم العميل','Waiting for customer number'].includes", false)
            ->assertDontSee("#customer-status:empty:before{content:'بانتظار إدخال رقم العميل'", false);

        $this->get('/request-service?lang=en')
            ->assertOk()
            ->assertSee('unifco-customer-context-layout-v16', false)
            ->assertSee('html[dir="ltr"] .uf-customer-context-layout>.uf-context-customer', false);
    }

    public function test_customer_details_remain_visible_and_empty_until_lookup_succeeds(): void
    {
        $this->get('/request-service')
            ->assertOk()
            ->assertSee('#customer-summary.uf-legacy-customer-summary{display:block!important', false)
            ->assertSee('.uf-legacy-customer-summary:not(.is-ok) .customer-summary-head', false)
            ->assertSee('.uf-legacy-customer-summary .customer-skeleton{display:none!important}', false)
            ->assertSee('.uf-legacy-customer-summary .customer-details{display:grid!important', false)
            ->assertSee("legacySummary?.classList.add('uf-legacy-customer-summary')", false);
    }

    public function test_verified_customer_status_appears_once_below_lookup_with_dark_green_check(): void
    {
        $this->get('/request-service')
            ->assertOk()
            ->assertSee('#customer-status.uf-lookup-state{display:flex!important', false)
            ->assertSee('#customer-status.uf-verified-status:after', false)
            ->assertSee("setLookupState('uf-verified-status',english?'Verified':'تم التحقق')", false)
            ->assertSee('grid-template-columns:minmax(130px,30%) repeat(2,minmax(125px,1fr))!important', false)
            ->assertSee('.lookup-row>#customer-lookup{width:100%!important;min-width:0!important;height:42px!important', false)
            ->assertSee('justify-content:center!important', false)
            ->assertSee('overflow:hidden!important', false)
            ->assertSee('#customer-summary.uf-legacy-customer-summary{display:block!important;width:100%!important', false)
            ->assertSee('background:#08752c!important', false)
            ->assertDontSee('uf-customer-verified-end', false);

        $this->get('/request-service?lang=en')
            ->assertOk()
            ->assertSee("'تم التحقق':'Verified'", false);
    }

    public function test_customer_id_shares_company_row_and_email_stays_inside_its_cell(): void
    {
        $this->get('/request-service')
            ->assertOk()
            ->assertSee('.customer-identity>div:last-child{display:flex!important', false)
            ->assertSee(".customer-code:before{content:'ID : '!important", false)
            ->assertSee('#summary-email{display:block!important;width:100%!important;max-width:100%!important', false)
            ->assertSee('overflow-wrap:anywhere!important', false)
            ->assertSee('text-align:right!important', false);
    }

    public function test_customer_lookup_state_box_covers_prompt_verified_and_missing_states(): void
    {
        $this->get('/request-service')
            ->assertOk()
            ->assertSee('uf-prompt-status', false)
            ->assertSee('uf-pending-status', false)
            ->assertSee('uf-verified-status', false)
            ->assertSee('uf-missing-status', false)
            ->assertSee("english?'Please enter the number':'يرجى إدخال الرقم'", false)
            ->assertSee("english?'Customer not found':'العميل غير موجود'", false)
            ->assertSee("content:'×'!important", false)
            ->assertSee('font-size:clamp(8.5px,.78vw,11px)!important', false)
            ->assertSee('white-space:nowrap!important', false);
    }

    public function test_customer_layout_recovers_when_compatibility_code_lifts_the_panel_outside_the_form(): void
    {
        $this->get('/request-service')
            ->assertOk()
            ->assertSee("document.getElementById('customer_number')?.closest('section.panel')", false)
            ->assertSee('lookup-row>#customer-status.uf-lookup-state{grid-column:2!important}', false);
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

    public function test_unified_request_workspace_has_interactive_timing_icons_and_attachment_previews(): void
    {
        $this->get('/request-service')->assertOk()
            ->assertSee('unifco-unified-asset-issue-workspace-polish-v6', false)
            ->assertSee('unifco-unified-asset-issue-workspace-polish-script-v6', false)
            ->assertSee('uf-file-count', false)
            ->assertSee('uf-preview-groups', false)
            ->assertSee('uf-preview-remove', false)
            ->assertSee('renderAttachmentPreviews', false)
            ->assertSee('removeAttachment', false)
            ->assertSee('preserveInputFiles', false)
            ->assertSee('capture="environment" multiple', false)
            ->assertSee('activateChip', false)
            ->assertSee("!customerCard.closest('.uf-customer-context-layout')", false)
            ->assertSee('.uf-customer-context-layout>.uf-context-details:only-child{grid-area:auto!important;grid-column:1/-1!important}', false)
            ->assertSee("time:'<svg", false)
            ->assertSee("registered:'<svg", false)
            ->assertSee("manual:'<svg", false)
            ->assertSee('align-items:stretch!important', false)
            ->assertSee('height:100%!important', false);
    }

    public function test_legacy_request_entry_points_redirect_to_the_unified_form(): void
    {
        $this->get('/request-service/current-maintenance')->assertRedirect('/request-service');
        $this->get('/request-quote')->assertRedirect('/request-service?quotation=1');
        $this->get('/emergency-maintenance')->assertRedirect('/request-service?emergency=1');
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
