<?php

namespace Tests\Feature;

use App\Models\{ApprovalRequest,Asset,ChartAccount,Customer,FinancialDocument,FiscalPeriod,Organization,ServiceRequest,Tenant,User,WorkOrder};
use App\Services\{ApprovalService,ServiceRequestWorkflowService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServiceRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'UNIFCO','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'C-WF','name'=>'Workflow Customer','email'=>'wf@example.test','status'=>'ACTIVE']);
        $requester=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'System Admin','email'=>'wf-admin@example.test','password'=>'StrongPassword123','role'=>'ADMIN','status'=>'ACTIVE']);
        $approver=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Sales Reviewer','email'=>'wf-sales@example.test','password'=>'StrongPassword123','role'=>'SALES','status'=>'ACTIVE']);
        return compact('tenant','org','customer','requester','approver');
    }

    public function test_large_quotation_builds_sequential_approval_matrix_with_sla(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-001','request_type'=>'QUOTATION','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Project quotation','subject'=>'Major maintenance project','details'=>'Scope','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'CHARGEABLE','procurement_required'=>true,
        ]);

        app(ServiceRequestWorkflowService::class)->start($request,[
            'estimated_value'=>320000,'margin_pct'=>8,'payment_terms_days'=>120,'risk_level'=>'HIGH','procurement_required'=>true,
        ]);

        $steps=ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$request->id)->orderBy('step_order')->get();
        $this->assertSame([
            'SALES','MAINTENANCE_ENGINEER','PROCUREMENT','TENDERS_CONTRACTS','OPERATIONS_MANAGER','CUSTOMER','TENDERS_CONTRACTS','SALES',
        ],$steps->pluck('approval_role')->all());
        $this->assertSame([
            'SALES_REVIEW','TECHNICAL_REVIEW','PRICING_PROCUREMENT','CONTRACT_REVIEW','INTERNAL_APPROVAL','CUSTOMER_DECISION','PO_OR_CONTRACT','COMPLETED',
        ],$steps->pluck('action')->all());
        $this->assertSame('PENDING',$steps->first()->status);
        $this->assertTrue($steps->skip(1)->every(fn($step)=>$step->status==='WAITING'));
        $this->assertSame(120,(int)$steps->first()->sla_minutes);
        $this->assertNotNull($steps->first()->due_at);
        $this->assertNull($steps->get(1)->due_at);
        $this->assertSame('SALES_REVIEW',$request->fresh()->workflow_stage);
    }

    public function test_approval_moves_only_next_waiting_step_to_pending(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-002','request_type'=>'QUOTATION','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Quotation','subject'=>'Standard quote','details'=>'Scope','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'CHARGEABLE',
        ]);
        app(ServiceRequestWorkflowService::class)->start($request,['estimated_value'=>50000,'margin_pct'=>20,'payment_terms_days'=>30,'risk_level'=>'NORMAL']);
        $first=ApprovalRequest::where('entity_id',$request->id)->where('status','PENDING')->firstOrFail();

        $this->assertSame('SALES_REVIEW',$first->action);
        $this->assertSame('SALES',$first->approval_role);
        $this->actingAs($c['approver']);
        app(ApprovalService::class)->decide($first,'APPROVED','Sales review complete');

        $steps=ApprovalRequest::where('entity_id',$request->id)->orderBy('step_order')->get();
        $this->assertSame('APPROVED',$steps->get(0)->status);
        $this->assertSame('PENDING',$steps->get(1)->status);
        $this->assertSame('TECHNICAL_REVIEW',$steps->get(1)->action);
        $this->assertSame('MAINTENANCE_ENGINEER',$steps->get(1)->approval_role);
        $this->assertTrue($steps->skip(2)->every(fn($step)=>$step->status==='WAITING'));
        $this->assertSame($steps->get(1)->action,$request->fresh()->workflow_stage);
    }

    public function test_wrong_role_cannot_decide_another_roles_approval(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-ROLE','request_type'=>'QUOTATION','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Quotation','subject'=>'Role ownership','details'=>'Scope','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'CHARGEABLE',
        ]);
        app(ServiceRequestWorkflowService::class)->start($request,['estimated_value'=>50000,'margin_pct'=>20,'payment_terms_days'=>30,'risk_level'=>'NORMAL']);
        $first=ApprovalRequest::where('entity_id',$request->id)->where('status','PENDING')->firstOrFail();
        $wrong=User::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'name'=>'Procurement','email'=>'wf-procurement@example.test','password'=>'StrongPassword123','role'=>'PROCUREMENT','status'=>'ACTIVE']);
        $this->actingAs($wrong);

        $this->expectException(ValidationException::class);
        app(ApprovalService::class)->decide($first,'APPROVED','Should not be allowed');
    }

    public function test_in_contract_routine_maintenance_builds_full_operational_route(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-003','request_type'=>'MAINTENANCE','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Corrective','subject'=>'Routine issue','details'=>'Scope','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'IN_CONTRACT','procurement_required'=>false,
        ]);
        app(ServiceRequestWorkflowService::class)->start($request);

        $steps=ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$request->id)->orderBy('step_order')->get();
        $this->assertSame([
            'TRIAGE','PROJECT_MANAGER_REVIEW','TECHNICIAN_ASSIGNMENT','EXECUTION','CUSTOMER_ACCEPTANCE','CLOSURE','CSAT',
        ],$steps->pluck('action')->all());
        $this->assertSame([
            'OPERATIONS_MANAGER','PROJECT_MANAGER','TECHNICAL_SUPERVISOR','TECHNICIAN','CUSTOMER','OPERATIONS_MANAGER','CUSTOMER',
        ],$steps->pluck('approval_role')->all());
        $this->assertSame('PENDING',$steps->first()->status);
        $this->assertSame(60,(int)$steps->first()->sla_minutes);
        $this->assertTrue($steps->skip(1)->every(fn($step)=>$step->status==='WAITING'));
        $this->assertSame('TRIAGE',$request->fresh()->workflow_stage);
    }

    public function test_emergency_maintenance_has_ten_minute_response_sla_and_full_route(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-004','request_type'=>'MAINTENANCE','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Emergency','subject'=>'Critical outage','details'=>'Scope','priority'=>'EMERGENCY','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'CHARGEABLE','response_sla_minutes'=>10,
        ]);
        app(ServiceRequestWorkflowService::class)->start($request);

        $steps=ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$request->id)->orderBy('step_order')->get();
        $this->assertSame(10,(int)$request->response_sla_minutes);
        $this->assertSame([
            'EMERGENCY_DISPATCH','PROJECT_MANAGER_REVIEW','TECHNICIAN_ASSIGNMENT','EXECUTION','CUSTOMER_ACCEPTANCE','FINANCE_REVIEW','CLOSURE','CSAT',
        ],$steps->pluck('action')->all());
        $this->assertSame([
            'OPERATIONS_MANAGER','PROJECT_MANAGER','TECHNICAL_SUPERVISOR','TECHNICIAN','CUSTOMER','FINANCE_MANAGER','OPERATIONS_MANAGER','CUSTOMER',
        ],$steps->pluck('approval_role')->all());
        $this->assertSame('PENDING',$steps->first()->status);
        $this->assertSame(10,(int)$steps->first()->sla_minutes);
        $this->assertNotNull($steps->first()->due_at);
        $this->assertTrue($steps->skip(1)->every(fn($step)=>$step->status==='WAITING'));
        $this->assertSame('EMERGENCY_DISPATCH',$request->fresh()->workflow_stage);
    }
    public function test_all_four_core_request_types_reach_their_expected_customer_and_closure_stages(): void
    {
        $c=$this->context();
        $cases=[
            ['no'=>'SR-E2E-ROUTINE','type'=>'MAINTENANCE','subtype'=>null,'priority'=>'NORMAL','eligibility'=>'IN_CONTRACT','expected'=>'MAINTENANCE','customer'=>'CUSTOMER_ACCEPTANCE','final'=>'CSAT'],
            ['no'=>'SR-E2E-EMERGENCY','type'=>'MAINTENANCE','subtype'=>'EMERGENCY_MAINTENANCE','priority'=>'EMERGENCY','eligibility'=>'CHARGEABLE','expected'=>'EMERGENCY_MAINTENANCE','customer'=>'CUSTOMER_ACCEPTANCE','final'=>'CSAT'],
            ['no'=>'SR-E2E-QUOTE','type'=>'QUOTATION','subtype'=>null,'priority'=>'NORMAL','eligibility'=>'CHARGEABLE','expected'=>'QUOTATION','customer'=>'CUSTOMER_DECISION','final'=>'COMPLETED'],
            ['no'=>'SR-E2E-CONSULT','type'=>'CONSULTATION','subtype'=>'TECHNICAL_CONSULTATION','priority'=>'NORMAL','eligibility'=>'CHARGEABLE','expected'=>'TECHNICAL_CONSULTATION','customer'=>'CUSTOMER_DELIVERY','final'=>'CLOSURE'],
        ];

        foreach($cases as $case){
            $request=ServiceRequest::create([
                'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
                'request_no'=>$case['no'],'request_type'=>$case['type'],'request_subtype'=>$case['subtype'],
                'company_name'=>$c['customer']->name,'email'=>$c['customer']->email,'service_category'=>$case['type'],
                'subject'=>$case['no'],'details'=>'Core business closure scenario','priority'=>$case['priority'],
                'status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>$case['eligibility'],
            ]);
            app(ServiceRequestWorkflowService::class)->start($request,[
                'estimated_value'=>$case['type']==='QUOTATION'?50000:0,
                'procurement_required'=>false,'risk_level'=>'NORMAL',
            ]);
            $request->refresh();
            $this->assertSame($case['expected'],$request->workflow_key);
            $actions=ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$request->id)->orderBy('step_order')->pluck('action');
            $this->assertTrue($actions->contains($case['customer']),$case['no'].' must include its customer decision/acceptance stage.');
            $this->assertSame($case['final'],$actions->last());
        }
    }

    public function test_workflow_advance_does_not_skip_waiting_stages(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-E2E-SEQUENCE','request_type'=>'MAINTENANCE','company_name'=>$c['customer']->name,
            'email'=>$c['customer']->email,'service_category'=>'Maintenance','subject'=>'Sequence guard','details'=>'Scope',
            'priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'IN_CONTRACT',
        ]);
        $workflow=app(ServiceRequestWorkflowService::class);
        $workflow->start($request);
        $this->assertSame('TRIAGE',$request->fresh()->workflow_stage);
        $workflow->advance($request,'TRIAGE',$c['requester']->id,'triaged');
        $this->assertSame('PROJECT_MANAGER_REVIEW',$request->fresh()->workflow_stage);
        $pending=ApprovalRequest::where('entity_id',$request->id)->where('status','PENDING')->pluck('action')->all();
        $this->assertSame(['PROJECT_MANAGER_REVIEW'],$pending);
    }

    public function test_maintenance_execution_creates_linked_work_order_and_finance_stage_creates_invoice(): void
    {
        $c=$this->context();
        $asset=Asset::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'asset_code'=>'E2E-GEN-100','name'=>'Customer 100 Generator','status'=>'REGISTERED',
        ]);
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,'asset_id'=>$asset->id,
            'request_no'=>'SR-E2E-INTEGRATED','request_type'=>'MAINTENANCE','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Corrective','subject'=>'Integrated maintenance','details'=>'End to end','priority'=>'HIGH',
            'status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'CHARGEABLE',
        ]);
        $workflow=app(ServiceRequestWorkflowService::class);
        $workflow->start($request,['estimated_value'=>0,'payment_terms_days'=>30]);

        foreach(['TRIAGE','PROJECT_MANAGER_REVIEW','TECHNICIAN_ASSIGNMENT'] as $stage){
            $workflow->advance($request->fresh(),$stage,$c['requester']->id,'E2E');
        }

        $request->refresh();
        $this->assertSame('EXECUTION',$request->workflow_stage);
        $this->assertNotNull($request->work_order_id);
        $workOrder=WorkOrder::findOrFail($request->work_order_id);
        $this->assertSame($asset->id,$workOrder->asset_id);

        $workOrder->update(['labor_cost'=>100,'material_cost'=>50,'external_cost'=>25,'total_cost'=>175,'status'=>'COMPLETED','completed_at'=>now()]);
        $workflow->advance($request->fresh(),'EXECUTION',$c['requester']->id,'Work completed');
        $this->assertSame('CUSTOMER_ACCEPTANCE',$request->fresh()->workflow_stage);

        $workflow->advance($request->fresh(),'CUSTOMER_ACCEPTANCE',$c['requester']->id,'Customer accepted');
        $request->refresh();
        $this->assertSame('FINANCE_REVIEW',$request->workflow_stage);
        $invoiceId=(int) data_get($request->workflow_context,'invoice_id');
        $this->assertGreaterThan(0,$invoiceId);
        $invoice=FinancialDocument::findOrFail($invoiceId);
        $this->assertSame($c['customer']->id,$invoice->customer_id);
        $this->assertSame('AR_INVOICE',$invoice->document_type);
        $this->assertSame('175.00',(string)$invoice->amount);
        $this->assertSame('DRAFT',$invoice->status);
    }

    public function test_posting_generated_invoice_advances_chargeable_request_to_closure(): void
    {
        $c=$this->context();
        $poster=User::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'name'=>'Finance Poster','email'=>'finance.poster@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-E2E-FINANCE','request_type'=>'MAINTENANCE','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Corrective','subject'=>'Finance closure','details'=>'Invoice closure','priority'=>'NORMAL','status'=>'OPEN',
            'workflow_stage'=>'FINANCE_REVIEW','workflow_key'=>'MAINTENANCE','eligibility'=>'CHARGEABLE',
        ]);
        $invoice=FinancialDocument::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,'document_no'=>'INV-SR-FIN',
            'document_type'=>'AR_INVOICE','counterparty_name'=>$c['customer']->name,'document_date'=>today(),'due_date'=>today()->addDays(30),
            'currency'=>'SAR','amount'=>100,'open_amount'=>100,'control_account_code'=>'AR','offset_account_code'=>'REV','status'=>'DRAFT','created_by'=>$c['requester']->id,
        ]);
        $request->update(['workflow_context'=>['invoice_id'=>$invoice->id]]);
        ApprovalRequest::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'entity_type'=>ServiceRequest::class,'entity_id'=>$request->id,'action'=>'FINANCE_REVIEW','approval_role'=>'FINANCE_MANAGER','step_order'=>1,'status'=>'PENDING','requested_by'=>$c['requester']->id]);
        ApprovalRequest::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'entity_type'=>ServiceRequest::class,'entity_id'=>$request->id,'action'=>'CLOSURE','approval_role'=>'OPERATIONS_MANAGER','step_order'=>2,'status'=>'WAITING','requested_by'=>$c['requester']->id]);
        ApprovalRequest::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'entity_type'=>ServiceRequest::class,'entity_id'=>$request->id,'action'=>'CSAT','approval_role'=>'CUSTOMER','step_order'=>3,'status'=>'WAITING','requested_by'=>$c['requester']->id]);
        foreach([['AR','Accounts Receivable','ASSET','DEBIT'],['REV','Service Revenue','REVENUE','CREDIT']] as $a) ChartAccount::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'code'=>$a[0],'name'=>$a[1],'type'=>$a[2],'normal_balance'=>$a[3],'posting_allowed'=>true,'status'=>'ACTIVE']);
        FiscalPeriod::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'code'=>'E2E','starts_on'=>today()->startOfMonth(),'ends_on'=>today()->endOfMonth(),'status'=>'OPEN']);
        $this->actingAs($poster);
        app(\App\Services\Finance\FinancialPostingService::class)->postDocument($invoice);
        $this->assertSame('CLOSURE',$request->fresh()->workflow_stage);
        $this->assertSame('POSTED',$invoice->fresh()->status);
    }

    public function test_closure_advances_to_csat_and_csat_fully_completes_workflow(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-E2E-CSAT','request_type'=>'MAINTENANCE','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Corrective','subject'=>'Closure and CSAT','details'=>'Final closure','priority'=>'NORMAL',
            'status'=>'OPEN','workflow_stage'=>'CLOSURE','workflow_key'=>'MAINTENANCE','eligibility'=>'IN_CONTRACT',
        ]);
        ApprovalRequest::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'entity_type'=>ServiceRequest::class,'entity_id'=>$request->id,'action'=>'CLOSURE','approval_role'=>'OPERATIONS_MANAGER','step_order'=>1,'status'=>'PENDING','requested_by'=>$c['requester']->id]);
        ApprovalRequest::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'entity_type'=>ServiceRequest::class,'entity_id'=>$request->id,'action'=>'CSAT','approval_role'=>'CUSTOMER','step_order'=>2,'status'=>'WAITING','requested_by'=>$c['requester']->id]);

        $workflow=app(ServiceRequestWorkflowService::class);
        $workflow->advance($request,'CLOSURE',$c['requester']->id,'Operational closure complete');
        $this->assertSame('CSAT',$request->fresh()->workflow_stage);
        $this->assertSame('PENDING',ApprovalRequest::where('entity_id',$request->id)->where('action','CSAT')->value('status'));

        $workflow->advance($request->fresh(),'CSAT',$c['requester']->id,'Customer satisfaction submitted');
        $request->refresh();
        $this->assertSame('COMPLETED',$request->workflow_stage);
        $this->assertSame('COMPLETED',$request->approval_state);
        $this->assertSame('COMPLETED',$request->status);
        $this->assertNotNull($request->resolved_at);
    }

}
