<?php

namespace Tests\Feature;

use App\Models\{Customer,FinancialDocument,Organization,ServiceRequest,Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialRequestLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Organization $organization;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant=Tenant::create(['name'=>'UNIFCO','code'=>'UNIFCO-FIN-WF','status'=>'ACTIVE']);
        $this->organization=Organization::create([
            'tenant_id'=>$this->tenant->id,
            'name'=>'Finance Workflow HQ',
            'code'=>'FIN-WF-HQ',
            'status'=>'ACTIVE',
        ]);
        $this->customer=Customer::create([
            'tenant_id'=>$this->tenant->id,
            'organization_id'=>$this->organization->id,
            'customer_code'=>'FIN-WF-100',
            'name'=>'Finance Workflow Customer',
            'email'=>'finance-workflow@example.test',
            'phone'=>'0500000100',
            'status'=>'ACTIVE',
        ]);
    }

    private function request(string $eligibility='CHARGEABLE'): ServiceRequest
    {
        return ServiceRequest::create([
            'tenant_id'=>$this->tenant->id,
            'organization_id'=>$this->organization->id,
            'customer_id'=>$this->customer->id,
            'request_no'=>'SR-FIN-'.uniqid(),
            'request_type'=>'MAINTENANCE',
            'request_subtype'=>'ROUTINE_MAINTENANCE',
            'company_name'=>$this->customer->name,
            'email'=>$this->customer->email,
            'mobile'=>$this->customer->phone,
            'service_category'=>'Maintenance',
            'subject'=>'Financial lifecycle test request',
            'details'=>'Financial lifecycle integration test fixture.',
            'priority'=>'NORMAL',
            'status'=>'OPEN',
            'workflow_stage'=>'CLOSURE',
            'workflow_key'=>'MAINTENANCE',
            'eligibility'=>$eligibility,
            'workflow_context'=>['chargeable'=>$eligibility==='CHARGEABLE'],
        ]);
    }

    private function invoice(ServiceRequest $request, string $status='POSTED', float $openAmount=100): FinancialDocument
    {
        return FinancialDocument::create([
            'tenant_id'=>$this->tenant->id,
            'organization_id'=>$this->organization->id,
            'customer_id'=>$this->customer->id,
            'service_request_id'=>$request->id,
            'document_no'=>'INV-SR-'.$request->id,
            'document_type'=>'AR_INVOICE',
            'counterparty_name'=>$this->customer->name,
            'document_date'=>today(),
            'due_date'=>today()->addDays(30),
            'currency'=>'SAR',
            'amount'=>100,
            'open_amount'=>$openAmount,
            'control_account_code'=>'AR',
            'offset_account_code'=>'REV',
            'status'=>$status,
        ]);
    }

    public function test_financial_document_has_a_direct_service_request_link(): void
    {
        $request=$this->request();
        $invoice=$this->invoice($request);

        $this->assertSame($request->id,$invoice->serviceRequest->id);
        $this->assertSame($invoice->id,$request->financialDocuments()->firstOrFail()->id);
    }

    public function test_request_invoice_number_backfills_direct_lifecycle_link_when_creator_omits_it(): void
    {
        $request=$this->request();
        $invoice=FinancialDocument::create([
            'tenant_id'=>$this->tenant->id,
            'organization_id'=>$this->organization->id,
            'customer_id'=>$this->customer->id,
            'document_no'=>'INV-SR-'.$request->id,
            'document_type'=>'AR_INVOICE',
            'counterparty_name'=>$this->customer->name,
            'document_date'=>today(),
            'due_date'=>today()->addDays(30),
            'currency'=>'SAR',
            'amount'=>100,
            'open_amount'=>100,
            'control_account_code'=>'AR',
            'offset_account_code'=>'REV',
            'status'=>'DRAFT',
        ]);

        $this->assertSame($request->id,$invoice->service_request_id);
        $this->assertSame($request->id,$invoice->serviceRequest->id);
    }

    public function test_chargeable_request_cannot_leave_closure_with_an_open_invoice(): void
    {
        $request=$this->request();
        $this->invoice($request,'POSTED',100);

        try {
            $request->update(['workflow_stage'=>'CSAT']);
            $this->fail('An unpaid chargeable request must not leave CLOSURE.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('workflow_stage',$exception->errors());
        }

        $this->assertSame('CLOSURE',$request->fresh()->workflow_stage);
    }

    public function test_partial_payment_still_blocks_closure_but_full_settlement_allows_it(): void
    {
        $request=$this->request();
        $invoice=$this->invoice($request,'POSTED',25);

        try {
            $request->update(['workflow_stage'=>'CSAT']);
            $this->fail('A partially paid invoice must still block closure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('workflow_stage',$exception->errors());
        }

        $invoice->update(['status'=>'SETTLED','open_amount'=>0]);
        $request->refresh()->update(['workflow_stage'=>'CSAT']);

        $this->assertSame('CSAT',$request->fresh()->workflow_stage);
    }

    public function test_zero_value_chargeable_request_can_close_when_no_invoice_artifact_exists(): void
    {
        $request=$this->request('CHARGEABLE');
        $request->update(['workflow_stage'=>'CSAT']);

        $this->assertSame('CSAT',$request->fresh()->workflow_stage);
    }

    public function test_non_chargeable_request_can_close_without_an_invoice(): void
    {
        $request=$this->request('IN_CONTRACT');
        $request->update(['workflow_stage'=>'CSAT']);

        $this->assertSame('CSAT',$request->fresh()->workflow_stage);
    }
}
