<?php

namespace App\Console\Commands;

use App\Models\{Asset,Customer,CustomerActivityEvent,ServiceContract,ServiceRequest,User,WorkOrder};
use App\Services\{MaintenanceRequestTransitionService,ServiceRequestWorkflowService};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductionRequestLifecycleSmoke extends Command
{
    protected $signature = 'unifco:production-request-smoke {--customer=100}';
    protected $description = 'Rollback-only smoke test for the core maintenance request lifecycle.';

    public function handle(): int
    {
        DB::beginTransaction();
        try {
            $customerKey=(string)$this->option('customer');
            $customer=Customer::withoutGlobalScopes()
                ->whereKey((int)$customerKey)
                ->orWhere('customer_code',$customerKey)
                ->orWhere('customer_code','UN-'.$customerKey)
                ->first();
            if(!$customer){
                $candidates=Customer::withoutGlobalScopes()
                    ->where('customer_code','like','%'.$customerKey.'%')
                    ->orWhere('name','like','%'.$customerKey.'%')
                    ->limit(10)->get(['id','customer_code','name']);
                if($candidates->count()===1){
                    $customer=$candidates->first();
                    $this->warn("Customer key {$customerKey} resolved to ID {$customer->id} / {$customer->customer_code}.");
                }else{
                    $this->error("Customer key {$customerKey} did not resolve uniquely.");
                    foreach($candidates as $candidate) $this->line("Candidate: ID {$candidate->id} | {$candidate->customer_code} | {$candidate->name}");
                    throw new \RuntimeException('Unable to resolve production customer safely.');
                }
            }
            $asset=Asset::withoutGlobalScopes()->where('customer_id',$customer->id)->orderBy('id')->firstOrFail();
            $contract=ServiceContract::withoutGlobalScopes()->where('customer_id',$customer->id)->where('status','ACTIVE')->orderBy('id')->first();

            $ops=User::where('email','operations.manager@unifco.local')->firstOrFail();
            $pm=User::where('email','projects.manager@unifco.local')->firstOrFail();
            $tech=User::where('email','technician@unifco.local')->firstOrFail();
            $finance=User::where('email','finance@unifco.local')->firstOrFail();

            $request=ServiceRequest::create([
                'tenant_id'=>$customer->tenant_id,
                'organization_id'=>$customer->organization_id,
                'customer_id'=>$customer->id,
                'customer_site_id'=>$asset->customer_site_id,
                'asset_id'=>$asset->id,
                'service_contract_id'=>$contract?->id,
                'request_no'=>'SMOKE-'.now()->format('YmdHis'),
                'request_type'=>'MAINTENANCE',
                'request_subtype'=>'ROUTINE_MAINTENANCE',
                'company_name'=>$customer->name,
                'commercial_registration'=>$customer->commercial_registration,
                'email'=>$customer->email,
                'mobile'=>$customer->phone,
                'service_category'=>'Maintenance',
                'subject'=>'Production rollback smoke test',
                'details'=>'Rollback-only end-to-end validation of the request engine.',
                'priority'=>'NORMAL',
                'status'=>'OPEN',
                'workflow_stage'=>'NEW',
                'eligibility'=>$contract?'IN_CONTRACT':'CHARGEABLE',
                'response_sla_minutes'=>120,
                'resolution_sla_minutes'=>1440,
            ]);

            $workflow=app(ServiceRequestWorkflowService::class);
            $transitions=app(MaintenanceRequestTransitionService::class);
            $workflow->start($request,['procurement_required'=>false,'risk_level'=>'NORMAL','estimated_value'=>0,'payment_terms_days'=>0]);

            $this->expectStage($request,'TRIAGE');
            $transitions->complete($ops,$request->fresh(),['TRIAGE'],'Smoke triage.');
            $this->expectStage($request,'PROJECT_MANAGER_REVIEW');
            $transitions->complete($pm,$request->fresh(),['PROJECT_MANAGER_REVIEW'],'Smoke PM review.');
            $this->expectStage($request,'TECHNICIAN_ASSIGNMENT');
            $transitions->assignTechnician($pm,$request->fresh(),$tech->id,'Smoke technician assignment.');
            $this->expectStage($request,'EXECUTION');

            $request->refresh();
            if(!$request->work_order_id) throw new \RuntimeException('Work order was not created.');
            $transitions->completeExecution($tech,$request->fresh(),'Smoke execution complete.');
            $this->expectStage($request,'CUSTOMER_ACCEPTANCE');

            $workOrder=WorkOrder::withoutGlobalScopes()->findOrFail($request->fresh()->work_order_id);
            $workOrder->update(['customer_accepted_at'=>now(),'customer_acceptance_notes'=>'Smoke acceptance.']);
            $workflow->advance($request->fresh(),'CUSTOMER_ACCEPTANCE',null,'Smoke customer acceptance.');

            $request->refresh();
            if($request->workflow_stage==='FINANCE_REVIEW'){
                $workflow->advance($request,'FINANCE_REVIEW',$finance->id,'Smoke finance review.');
            }

            $this->expectStage($request,'CLOSURE');
            $context=(array)($request->fresh()->workflow_context??[]);
            $context['operationally_closed_at']=now()->toIso8601String();
            $request->update(['workflow_context'=>$context,'status'=>'RESOLVED','resolved_at'=>now()]);
            $transitions->complete($ops,$request->fresh(),['CLOSURE'],'Smoke closure.');
            $this->expectStage($request,'CSAT');

            CustomerActivityEvent::create([
                'tenant_id'=>$request->tenant_id,
                'organization_id'=>$request->organization_id,
                'customer_id'=>$request->customer_id,
                'event_type'=>'CUSTOMER_SATISFACTION',
                'reference_type'=>ServiceRequest::class,
                'reference_id'=>$request->id,
                'title'=>'Production smoke satisfaction',
                'description'=>'Rollback-only smoke test.',
                'visibility'=>'BOTH',
                'metadata'=>['rating'=>5,'nps'=>10],
            ]);
            $workflow->advance($request->fresh(),'CSAT',null,'Smoke CSAT.');
            $request->update(['status'=>'CLOSED']);

            $request->refresh();
            if($request->status!=='CLOSED' || $request->workflow_stage!=='COMPLETED'){
                throw new \RuntimeException("Final state mismatch: {$request->status} / {$request->workflow_stage}");
            }

            $this->info('PASS: Routine Maintenance reached CLOSED through the full production workflow.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('FAIL: '.$e->getMessage());
            return self::FAILURE;
        } finally {
            if(DB::transactionLevel()>0) DB::rollBack();
            $this->line('Rollback complete: production data unchanged.');
        }
    }

    private function expectStage(ServiceRequest $request,string $expected): void
    {
        $request->refresh();
        $this->line("Stage: {$request->workflow_stage}");
        if($request->workflow_stage!==$expected){
            throw new \RuntimeException("Expected {$expected}, got {$request->workflow_stage}.");
        }
    }
}
