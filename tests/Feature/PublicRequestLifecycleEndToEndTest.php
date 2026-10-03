<?php

namespace Tests\Feature;

use App\Models\{ApprovalRequest,Asset,ChartAccount,CrmQuotation,Customer,CustomerSite,FinancialDocument,FiscalPeriod,Organization,Project,ProjectUserAssignment,PublicServiceRequest,ServiceContract,ServiceRequest,Tenant,User,WorkOrder};
use App\Services\{ApprovalService,MaintenanceRequestTransitionService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicRequestLifecycleEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private array $actors=[];
    private Customer $customer;
    private CustomerSite $site;
    private Asset $asset;
    private User $portalUser;
    private Project $project;

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

        foreach(['ADMIN','OPERATIONS_MANAGER','PROJECT_MANAGER','MAINTENANCE_MANAGER','TECHNICAL_SUPERVISOR','TECHNICIAN','SALES','MAINTENANCE_ENGINEER','QUALITY','HSE','PROCUREMENT','TENDERS_CONTRACTS','FINANCE_MANAGER','CEO','CUSTOMER_SERVICE'] as $role){
            $this->actors[$role]=User::create([
                'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>$role,'email'=>strtolower($role).'@lifecycle.test',
                'password'=>'StrongPassword123','role'=>$role,'user_type'=>'INTERNAL','status'=>'ACTIVE',
            ]);
        }
        $this->portalUser=User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$this->customer->id,'name'=>'Lifecycle Customer',
            'email'=>'portal@lifecycle.test','password'=>'StrongPassword123','role'=>'CUSTOMER','user_type'=>'EXTERNAL','status'=>'ACTIVE',
        ]);
        $this->project=Project::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$this->customer->id,
            'project_no'=>'E2E-PRJ-100','name'=>'Lifecycle customer project','status'=>'ACTIVE',
        ]);
        foreach(['OPERATIONS_MANAGER','PROJECT_MANAGER','MAINTENANCE_MANAGER','MAINTENANCE_ENGINEER','TECHNICAL_SUPERVISOR','TECHNICIAN','QUALITY','HSE'] as $projectRole){
            ProjectUserAssignment::create([
                'tenant_id'=>$tenant->id,'project_id'=>$this->project->id,
                'user_id'=>$this->actors[$projectRole]->id,'project_role'=>$projectRole,
                'access_level'=>'PROJECT','status'=>'ACTIVE',
            ]);
        }
        DB::table('role_permissions')->insert([
            'tenant_id'=>$tenant->id,'role_code'=>'OPERATIONS_MANAGER',
            'permission_code'=>'service_requests.assign','effect'=>'ALLOW',
            'created_at'=>now(),'updated_at'=>now(),
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
        $before=PublicServiceRequest::count();
        $this->post('/service-requests',$this->payload($intent,$subtype,$overrides))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame($before+1,PublicServiceRequest::count(),'Public request was not persisted.');
        $public=PublicServiceRequest::latest('id')->firstOrFail();
        $this->assertSame($subtype,$public->request_subtype,'Public request subtype changed during intake.');
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

        if(!$request->project_id
            && in_array((string)$request->workflow_key,['QUOTATION','SPARE_PARTS_QUOTATION','TECHNICAL_VISIT'],true)
            && in_array((string)$request->workflow_stage,['PROJECT_MANAGER_REVIEW','TECHNICAL_REVIEW','TECHNICIAN_ASSIGNMENT'],true)){
            $this->actingAs($this->actors['OPERATIONS_MANAGER'])
                ->post(route('service-requests.workflow.assign-project',$request),[
                    'project_id'=>$this->project->id,
                ])->assertRedirect()->assertSessionHasNoErrors();
            $request->refresh();
            $this->assertSame($this->project->id,$request->project_id);
        }

        if($request->workflow_stage==='TECHNICIAN_ASSIGNMENT'){
            $this->actingAs($this->actors['TECHNICAL_SUPERVISOR']);
            app(MaintenanceRequestTransitionService::class)->assignTechnician(
                $this->actors['TECHNICAL_SUPERVISOR'],
                $request,
                $this->actors['TECHNICIAN']->id,
                'E2E technician assignment completed.'
            );
            return;
        }

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

        while($request->fresh()->workflow_stage!=='EXECUTION'){
            $request->refresh();
            match($request->workflow_stage){
                'PROJECT_MANAGER_REVIEW' => $transitions->complete($this->actors['PROJECT_MANAGER'],$request,['PROJECT_MANAGER_REVIEW'],'Project review completed.'),
                'MAINTENANCE_MANAGER_REVIEW' => $transitions->complete($this->actors['MAINTENANCE_MANAGER'],$request,['MAINTENANCE_MANAGER_REVIEW'],'Maintenance manager review completed.'),
                'TECHNICAL_ASSESSMENT' => $transitions->complete($this->actors['MAINTENANCE_ENGINEER'],$request,['TECHNICAL_ASSESSMENT'],'Technical assessment completed.'),
                'HSE_CLEARANCE' => $transitions->complete($this->actors['HSE'],$request,['HSE_CLEARANCE'],'HSE clearance completed.'),
                'TECHNICIAN_ASSIGNMENT' => $transitions->assignTechnician($this->actors['TECHNICAL_SUPERVISOR'],$request,$this->actors['TECHNICIAN']->id,'Technician assigned.'),
                default => $this->fail('Unexpected maintenance stage before execution: '.$request->workflow_stage),
            };
        }

        $workOrder=WorkOrder::findOrFail($request->fresh()->work_order_id);
        DB::table('role_permissions')->updateOrInsert(
            ['tenant_id'=>$request->tenant_id,'role_code'=>'TECHNICIAN','permission_code'=>'maintenance.work_order.manage'],
            ['effect'=>'ALLOW','created_at'=>now(),'updated_at'=>now()]
        );
        $this->actingAs($this->actors['TECHNICIAN'])->post(route('maintenance.work-orders.complete',$workOrder),[
            'completion_notes'=>'Repair completed and tested.','labor_hours'=>1,
            'labor_cost'=>100,'external_cost'=>0,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $request->refresh();
        while(in_array($request->workflow_stage,['TECHNICAL_REVIEW','QUALITY_VERIFICATION'],true)){
            if($request->workflow_stage==='TECHNICAL_REVIEW'){
                $transitions->complete($this->actors['MAINTENANCE_ENGINEER'],$request,['TECHNICAL_REVIEW'],'Technical review completed.');
            } else {
                $transitions->complete($this->actors['QUALITY'],$request,['QUALITY_VERIFICATION'],'Quality verification completed.');
            }
            $request->refresh();
        }
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
        if ($request->eligibility === 'CHARGEABLE') {
            $invoice=FinancialDocument::query()->where('service_request_id',$request->id)->where('document_type','AR_INVOICE')->firstOrFail();
            $this->assertSame('DRAFT',$invoice->status);
            $this->assertSame(100.0,(float)$invoice->amount);
            FiscalPeriod::create([
                'tenant_id'=>$request->tenant_id,'organization_id'=>$request->organization_id,
                'code'=>'E2E-'.today()->format('Y-m'),'starts_on'=>today()->startOfMonth(),
                'ends_on'=>today()->endOfMonth(),'status'=>'OPEN',
            ]);
            foreach ([['AR','Accounts Receivable','ASSET','DEBIT'],['REV','Service Revenue','REVENUE','CREDIT'],['CASH','Cash','ASSET','DEBIT']] as [$code,$name,$type,$normal]) {
                ChartAccount::create([
                    'tenant_id'=>$request->tenant_id,'organization_id'=>$request->organization_id,
                    'code'=>$code,'name'=>$name,'type'=>$type,'normal_balance'=>$normal,
                    'posting_allowed'=>true,'status'=>'ACTIVE',
                ]);
            }
            DB::table('role_permissions')->updateOrInsert(
                ['tenant_id'=>$request->tenant_id,'role_code'=>'FINANCE_MANAGER','permission_code'=>'finance.journal.post'],
                ['effect'=>'ALLOW','created_at'=>now(),'updated_at'=>now()]
            );
            $this->actingAs($this->actors['FINANCE_MANAGER'])->post(route('finance.core.documents.post',$invoice))
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame('POSTED',$invoice->fresh()->status);
            $this->actingAs($this->actors['FINANCE_MANAGER'])->post(route('finance.core.documents.pay',$invoice),[
                'payment_no'=>'RCPT-E2E-'.$request->id,'payment_date'=>today()->toDateString(),
                'amount'=>(float)$invoice->amount,'cash_account_code'=>'CASH',
            ])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame('SETTLED',$invoice->fresh()->status);
            $this->assertSame(0.0,(float)$invoice->fresh()->open_amount);
        }
        $this->actingAs($this->actors['OPERATIONS_MANAGER'])->post(route('service-requests.workflow.close',$request),[
            'notes'=>'Operational closure verified.',
        ])->assertRedirect()->assertSessionHasNoErrors();
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
        $this->assertSame('SPARE_PARTS_QUOTATION',$request->workflow_key);
        $this->assertTrue((bool)$request->workflow_context['procurement_required']);
        $this->completeQuotation($request);
    }


    public function test_technical_visit_quotation_runs_through_site_visit_report_and_customer_decision(): void
    {
        $request=$this->submit('QUOTATION','TECHNICAL_VISIT',['service_category'=>'Technical Visit']);
        $this->assertSame('TECHNICAL_VISIT',$request->workflow_key);
        $this->completeQuotation($request);
    }

    public function test_technical_visit_subtype_is_preserved_when_the_category_is_generic(): void
    {
        $request=$this->submit('QUOTATION','TECHNICAL_VISIT',['service_category'=>'Quotation']);
        $this->assertSame('TECHNICAL_VISIT',$request->request_subtype);
        $this->assertSame('TECHNICAL_VISIT',$request->workflow_key);
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
