<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,CustomerSite,Organization,PublicServiceRequest,ServiceContract,ServiceRequest,Tenant,User,WorkOrder};
use App\Services\ServiceRequestWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RequestWorkflowEngineFoundationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Organization $org;
    private Customer $customer;
    private CustomerSite $site;
    private Asset $asset;
    private ServiceContract $contract;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant=Tenant::create(['name'=>'UNIFCO','code'=>'UNIFCO','status'=>'ACTIVE']);
        $this->org=Organization::create(['tenant_id'=>$this->tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $this->customer=Customer::create([
            'tenant_id'=>$this->tenant->id,
            'organization_id'=>$this->org->id,
            'customer_code'=>'WF-FOUNDATION-100',
            'name'=>'Workflow Foundation Customer',
            'commercial_registration'=>'1010888800',
            'email'=>'workflow.foundation@example.test',
            'phone'=>'0500888800',
            'status'=>'ACTIVE',
        ]);
        $this->site=CustomerSite::create([
            'customer_id'=>$this->customer->id,
            'site_code'=>'WF-RUH',
            'name'=>'Workflow Foundation Site',
            'city'=>'Riyadh',
            'status'=>'ACTIVE',
        ]);
        $this->asset=Asset::create([
            'tenant_id'=>$this->tenant->id,
            'organization_id'=>$this->org->id,
            'customer_id'=>$this->customer->id,
            'customer_site_id'=>$this->site->id,
            'asset_code'=>'WF-ASSET-1',
            'name'=>'Workflow Foundation Asset',
            'asset_category'=>'PUMPS',
            'status'=>'REGISTERED',
            'verification_status'=>'VERIFIED',
        ]);
        $this->contract=ServiceContract::create([
            'tenant_id'=>$this->tenant->id,
            'organization_id'=>$this->org->id,
            'customer_id'=>$this->customer->id,
            'contract_no'=>'WF-CNT-1',
            'title'=>'Unrelated until explicitly linked',
            'starts_on'=>today()->subDay(),
            'ends_on'=>today()->addYear(),
            'contract_value'=>50000,
            'currency'=>'SAR',
            'billing_cycle'=>'MONTHLY',
            'status'=>'ACTIVE',
        ]);
        $this->admin=User::create([
            'tenant_id'=>$this->tenant->id,
            'organization_id'=>$this->org->id,
            'name'=>'Workflow Foundation Admin',
            'email'=>'wf-admin@example.test',
            'password'=>bcrypt((string) Str::uuid()),
            'role'=>'ADMIN',
            'user_type'=>'INTERNAL',
            'status'=>'ACTIVE',
        ]);
    }

    private function submitRoutine(): array
    {
        $this->post('/service-requests',[
            'lang'=>'en',
            'request_intent'=>'SERVICE_REQUEST',
            'request_subtype'=>'ROUTINE_MAINTENANCE',
            'company_name'=>$this->customer->name,
            'responsible_person'=>'Workflow Contact',
            'commercial_registration'=>$this->customer->commercial_registration,
            'mobile'=>$this->customer->phone,
            'email'=>$this->customer->email,
            'site_name'=>$this->site->name,
            'site_city'=>$this->site->city,
            'asset_id'=>$this->asset->id,
            'asset_type'=>'Pump',
            'equipment_brand'=>'UNIFCO Test',
            'equipment_model'=>'WF-1',
            'service_category'=>'Maintenance',
            'urgency'=>'NORMAL',
            'requested_date'=>today()->addDay()->toDateString(),
            'requested_time'=>'10:00',
            'details'=>'Request workflow foundation regression test.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $public=PublicServiceRequest::latest('id')->firstOrFail();
        $this->assertNotNull($public->converted_at,(string)($public->conversion_error ?: 'Public request conversion failed.'));
        $request=ServiceRequest::findOrFail($public->service_request_id);

        return [$public,$request];
    }

    public function test_unrelated_customer_contract_does_not_cover_an_unlinked_asset(): void
    {
        [$public,$request]=$this->submitRoutine();

        $this->assertNull($request->service_contract_id);
        $this->assertSame('CHARGEABLE',$request->eligibility);
        $this->assertNull($request->work_order_id,'Work Order must not be created during intake/conversion.');
        $this->assertNull($public->work_order_id,'Public conversion must not own Work Order creation.');
        $this->assertNotNull($request->quotation_id,'Chargeable routine maintenance should retain its commercial artifact.');
    }

    public function test_explicit_asset_contract_reference_proves_coverage_and_work_order_is_created_only_at_execution(): void
    {
        $this->asset->update(['contract_reference'=>$this->contract->contract_no]);
        [$public,$request]=$this->submitRoutine();

        $this->assertSame($this->contract->id,$request->service_contract_id);
        $this->assertSame('IN_CONTRACT',$request->eligibility);
        $this->assertNull($request->work_order_id);
        $this->assertNull($public->work_order_id);

        $workflow=app(ServiceRequestWorkflowService::class);
        while($request->fresh()->workflow_stage!=='EXECUTION'){
            $request->refresh();
            $workflow->advance($request,(string)$request->workflow_stage,$this->admin->id,'Foundation test transition.');
        }

        $request->refresh();
        $this->assertNotNull($request->work_order_id);
        $workOrder=WorkOrder::findOrFail($request->work_order_id);
        $this->assertSame($this->asset->id,$workOrder->asset_id);
        $this->assertSame($this->contract->id,$workOrder->service_contract_id);
        $this->assertSame(1,WorkOrder::where('tenant_id',$this->tenant->id)->where('work_order_no',$workOrder->work_order_no)->count());
    }

    public function test_workflow_transitions_are_written_to_audit_log_with_before_and_after_state(): void
    {
        $this->asset->update(['contract_reference'=>$this->contract->contract_no]);
        [, $request]=$this->submitRoutine();

        $this->actingAs($this->admin);
        $from=(string)$request->workflow_stage;
        app(ServiceRequestWorkflowService::class)->advance($request,$from,$this->admin->id,'Audit foundation test.');
        $to=(string)$request->fresh()->workflow_stage;

        $row=DB::table('audit_logs')
            ->where('entity_type',ServiceRequest::class)
            ->where('entity_id',$request->id)
            ->where('action','SERVICE_REQUEST_WORKFLOW_TRANSITION')
            ->latest('id')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame($this->admin->id,(int)$row->user_id);
        $before=json_decode((string)$row->before_state,true);
        $after=json_decode((string)$row->after_state,true);
        $this->assertSame($from,$before['workflow_stage'] ?? null);
        $this->assertSame($to,$after['workflow_stage'] ?? null);
    }
}
