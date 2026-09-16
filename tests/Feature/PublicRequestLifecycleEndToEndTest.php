<?php

namespace Tests\Feature;

use App\Models\{ApprovalRequest,Asset,CrmQuotation,Customer,CustomerSite,Organization,PublicServiceRequest,ServiceContract,ServiceRequest,Tenant,User,WorkOrder};
use App\Services\{ApprovalService,MaintenanceRequestTransitionService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRequestLifecycleEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private array $actors=[];
    private Customer $customer;
    private CustomerSite $site;
    private Asset $asset;
    private User $portalUser;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'UNIFCO','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $this->customer=Customer::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'E2E-100','name'=>'Lifecycle Test Customer',
            'commercial_registration'=>'1010999999','email'=>'lifecycle@example.test','phone'=>'0500999999','status'=>'ACTIVE',
        ]);
        $this->site=CustomerSite::create(['customer_id'=>$this->customer->id,'site_code'=>'E2E-RUH','name'=>'Lifecycle Riyadh Site','city'=>'Riyadh','status'=>'ACTIVE']);
        $this->asset=Asset::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$this->customer->id,'customer_site_id'=>$this->site->id,
            'asset_code'=>'E2E-PUMP-1','name'=>'Lifecycle Pump','asset_category'=>'PUMPS','status'=>'REGISTERED','verification_status'=>'VERIFIED',
        ]);
        ServiceContract::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$this->customer->id,'contract_no'=>'E2E-CNT-1',
            'title'=>'Lifecycle Coverage','starts_on'=>today()->subDay(),'ends_on'=>today()->addYear(),'contract_value'=>100000,
            'currency'=>'SAR','billing_cycle'=>'MONTHLY','status'=>'ACTIVE',
        ]);

        foreach(['ADMIN','OPERATIONS_MANAGER','PROJECT_MANAGER','TECHNICIAN','SALES','MAINTENANCE_ENGINEER','PROCUREMENT','TENDERS_CONTRACTS','FINANCE','CEO','CUSTOMER_SERVICE'] as $role){
            $this->actors[$role]=User::create([
                'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>$role,'email'=>strtolower($role).'@lifecycle.test',
                'password'=>'StrongPassword123','role'=>$role,'user_type'=>'INTERNAL','status'=>'ACTIVE',
            ]);
        }
        $this->portalUser=User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$this->customer->id,'name'=>'Lifecycle Customer',
            'email'=>'portal@lifecycle.test','password'=>'StrongPassword123','role'=>'CUSTOMER','user_type'=>'EXTERNAL','status'=>'ACTIVE',
        ]);
    }

    private function payload(string $intent,string $subtype,array $overrides=[]): array
    {
        return array_merge([
            'lang'=>'en','request_intent'=>$intent,'request_subtype'=>$subtype,'company_name'=>$this->customer->name,
            'responsible_person'=>'Customer Representative','commercial_registration'=>$this->customer->commercial_registration,
            'mobile'=>$this->customer->phone,'email'=>$this->customer->email,'site_name'=>$this->site->name,'site_city'=>$this->site->city,
            'asset_type'=>'Pump','equipment_brand'=>'UNIFCO Test','equipment_model'=>'E2E-1','service_category'=>'Maintenance',
            'urgency'=>'NORMAL','requested_date'=>today()->addDay()->toDateString(),'requested_time'=>'10:00','details'=>'Lifecycle end-to-end request.',
        ],$overrides);
    }

    private function submit(string $intent,string $subtype,array $overrides=[]): ServiceRequest
    {
        $this->post('/service-requests',$this->payload($intent,$subtype,$overrides))->assertRedirect();
        $public=PublicServiceRequest::latest('id')->firstOrFail();
        $this->assertNotNull(
            $public->converted_at,
            (string) ($public->conversion_error ?: 'Public request was not converted.')
        );
        $this->assertNotNull($public->service_request_id);
        return ServiceRequest::findOrFail($public->service_request_id);
    }

    private function approveCurrent(ServiceRequest $request): void
    {
        $request->refresh();
        $approval=ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$request->id)
            ->where('action',$request->workflow_stage)->where('status','PENDING')->firstOrFail();
        $actor=$this->actors[$approval->approval_role]??null;
        $this->assertNotNull($actor,'Missing actor for '.$approval->approval_role.' at '.$approval->action);
        $this->actingAs($actor);
        app(ApprovalService::class)->decide($approval,'APPROVED','E2E '.$approval->action.' completed.');
    }

    private function completeMaintenance(ServiceRequest $request): void
    {
        $transitions=app(MaintenanceRequestTransitionService::class);
        $first=$request->fresh()->workflow_stage;
        $this->assertContains($first,['TRIAGE','EMERGENCY_DISPATCH']);
        $transitions->complete($this->actors['OPERATIONS_MANAGER'],$request,[$first],'Initial operations review completed.');
        $transitions->complete($this->actors['PROJECT_MANAGER'],$request->fresh(),['PROJECT_MANAGER_REVIEW'],'Project review completed.');
        $transitions->assignTechnician($this->actors['PROJECT_MANAGER'],$request->fresh(),$this->actors['TECHNICIAN']->id,'Technician assigned.');
        $transitions->completeExecution($this->actors['TECHNICIAN'],$request->fresh(),'Repair completed and tested.');

        $request->refresh();
        $this->assertSame('CUSTOMER_ACCEPTANCE',$request->workflow_stage);
        $workOrder=WorkOrder::findOrFail($request->work_order_id);
        $this->assertSame('COMPLETED',$workOrder->status);
        $this->actingAs($this->portalUser)->post(route('customer.work-acceptance.decide',$workOrder),[
            'decision'=>'ACCEPT','notes'=>'Customer verified completed work.',
        ])->assertRedirect();

        $request->refresh();
        while($request->workflow_stage==='FINANCE_REVIEW'){
            $this->approveCurrent($request);
            $request->refresh();
        }
        $this->assertSame('CLOSURE',$request->workflow_stage);
        $this->actingAs($this->actors['OPERATIONS_MANAGER'])->post(route('service-requests.workflow.close',$request),[
            'notes'=>'Operational closure verified.',
        ])->assertRedirect();
        $this->assertSame('CSAT',$request->fresh()->workflow_stage);
        $this->actingAs($this->portalUser)->post(route('customer.requests.satisfaction',$request),[
            'rating'=>5,'nps'=>10,'comment'=>'Lifecycle completed successfully.',
        ])->assertRedirect();

        $request->refresh();
        $this->assertSame('CLOSED',$request->status);
        $this->assertSame('COMPLETED',$request->workflow_stage);
        $this->assertNotNull($workOrder->fresh()->customer_accepted_at);
    }

    private function completeQuotation(ServiceRequest $request): void
    {
        while(!in_array($request->fresh()->workflow_stage,['CUSTOMER_DECISION','COMPLETED'],true)) $this->approveCurrent($request);
        $request->refresh();
        $this->assertSame('CUSTOMER_DECISION',$request->workflow_stage);
        $quotation=CrmQuotation::findOrFail($request->quotation_id);
        $this->actingAs($this->portalUser)->post(route('customer.quotations.decision',$quotation),[
            'decision'=>'APPROVE','notes'=>'Customer approved the commercial offer.',
        ])->assertRedirect();
        $this->assertSame('CUSTOMER_APPROVED',$quotation->fresh()->status);

        $request->refresh();
        while($request->status!=='COMPLETED'){
            $this->approveCurrent($request);
            $request->refresh();
        }
        $this->assertSame('COMPLETED',$request->workflow_stage);
        $this->assertSame('COMPLETED',$request->approval_state);
    }

    public function test_routine_maintenance_runs_from_public_creation_to_customer_satisfaction_and_closure(): void
    {
        $request=$this->submit('SERVICE_REQUEST','ROUTINE_MAINTENANCE',['asset_id'=>$this->asset->id]);
        $this->assertSame('MAINTENANCE',$request->workflow_key);
        $this->completeMaintenance($request);
    }

    public function test_emergency_maintenance_runs_from_public_creation_to_customer_satisfaction_and_closure(): void
    {
        $request=$this->submit('SERVICE_REQUEST','URGENT_MAINTENANCE',['asset_id'=>$this->asset->id,'urgency'=>'URGENT']);
        $this->assertSame('EMERGENCY_MAINTENANCE',$request->workflow_key);
        $this->assertSame('EMERGENCY',$request->priority);
        $this->completeMaintenance($request);
    }

    public function test_spare_parts_quotation_runs_from_creation_through_customer_approval_to_completion(): void
    {
        $request=$this->submit('QUOTATION','SPARE_PARTS_QUOTE',['service_category'=>'Spare Parts']);
        $this->assertSame('QUOTATION',$request->workflow_key);
        $this->assertTrue((bool)$request->workflow_context['procurement_required']);
        $this->completeQuotation($request);
    }

    public function test_maintenance_contract_quotation_runs_from_creation_through_onboarding_to_completion(): void
    {
        $request=$this->submit('QUOTATION','MAINTENANCE_CONTRACT_QUOTE',['service_category'=>'Maintenance Contract']);
        $this->assertSame('MAINTENANCE_CONTRACT_QUOTATION',$request->workflow_key);
        $this->completeQuotation($request);
    }

    public function test_technical_consultation_runs_from_creation_through_customer_delivery_to_closure(): void
    {
        $request=$this->submit('CONSULTATION','TECHNICAL_CONSULTATION',['service_category'=>'Technical Consultation']);
        $this->assertSame('TECHNICAL_CONSULTATION',$request->workflow_key);
        while($request->fresh()->workflow_stage!=='CUSTOMER_DELIVERY') $this->approveCurrent($request);
        $this->actingAs($this->portalUser)->post(route('customer.requests.decision',$request),[
            'decision'=>'ACCEPT','notes'=>'Technical report accepted.',
        ])->assertRedirect();
        $this->assertSame('CLOSURE',$request->fresh()->workflow_stage);
        $this->approveCurrent($request);
        $this->assertSame('COMPLETED',$request->fresh()->workflow_stage);
        $this->assertSame('COMPLETED',$request->fresh()->status);
    }

    public function test_customer_revision_returns_to_the_correct_business_stage_and_rejection_terminates_the_route(): void
    {
        $quoteRequest=$this->submit('QUOTATION','SPARE_PARTS_QUOTE',['service_category'=>'Spare Parts']);
        while($quoteRequest->fresh()->workflow_stage!=='CUSTOMER_DECISION') $this->approveCurrent($quoteRequest);
        $quotation=CrmQuotation::findOrFail($quoteRequest->quotation_id);
        $this->actingAs($this->portalUser)->post(route('customer.quotations.decision',$quotation),[
            'decision'=>'REVISION','notes'=>'Please revise quantities and pricing.',
        ])->assertRedirect();
        $this->assertSame('PRICING_PROCUREMENT',$quoteRequest->fresh()->workflow_stage);

        while($quoteRequest->fresh()->workflow_stage!=='CUSTOMER_DECISION') $this->approveCurrent($quoteRequest);
        $this->actingAs($this->portalUser)->post(route('customer.quotations.decision',$quotation),[
            'decision'=>'REJECT','notes'=>'Commercial offer rejected.',
        ])->assertRedirect();
        $this->assertSame('REJECTED',$quoteRequest->fresh()->status);
        $this->assertSame('REJECTED',$quoteRequest->fresh()->approval_state);
        $this->assertFalse(ApprovalRequest::where('entity_id',$quoteRequest->id)->whereIn('status',['WAITING','PENDING'])->exists());
    }
}
