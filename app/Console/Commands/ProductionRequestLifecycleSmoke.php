<?php

namespace App\Console\Commands;

use App\Models\{ApprovalRequest,Asset,Customer,CustomerActivityEvent,ServiceContract,ServiceRequest,User,WorkOrder};
use App\Services\{AuthorizationService,MaintenanceRequestTransitionService,ServiceRequestWorkflowService};
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB};
use Throwable;

class ProductionRequestLifecycleSmoke extends Command
{
    protected $signature = 'unifco:production-request-smoke {--customer=100}';
    protected $description = 'Rollback-only smoke test for request lifecycle and authenticated internal request inbox visibility.';

    public function handle(): int
    {
        DB::beginTransaction();
        try {
            $this->verifyInternalRequestInbox();

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
            $this->info("Customer ID: {$customer->id}");
            $this->line("Customer Code: ".($customer->customer_code ?? '—'));
            $this->line("Name: ".($customer->name ?? '—'));
            $this->line("Email: ".($customer->email ?? '—'));
            $this->line("Phone: ".($customer->phone ?? '—'));
            $this->line("City: ".($customer->city ?? '—'));
            $this->line("Status: ".($customer->status ?? '—'));
            $this->line("Contact: ".($customer->contact_name ?? '—'));
            $this->line("Commercial Registration: ".($customer->commercial_registration ?? '—'));

            $asset=Asset::withoutGlobalScopes()->where('customer_id',$customer->id)->orderBy('id')->firstOrFail();
            $contract=ServiceContract::withoutGlobalScopes()->where('customer_id',$customer->id)->where('status','ACTIVE')->orderBy('id')->first();

            $tech=User::where('email','technician@unifco.local')->firstOrFail();

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
            $transitions->complete($this->stageActor($request),$request->fresh(),['TRIAGE'],'Smoke triage.');
            $this->expectStage($request,'PROJECT_MANAGER_REVIEW');
            $transitions->complete($this->stageActor($request),$request->fresh(),['PROJECT_MANAGER_REVIEW'],'Smoke PM review.');
            $this->expectStage($request,'MAINTENANCE_MANAGER_REVIEW');
            $transitions->complete($this->stageActor($request),$request->fresh(),['MAINTENANCE_MANAGER_REVIEW'],'Smoke maintenance manager review.');
            $this->expectStage($request,'TECHNICAL_ASSESSMENT');
            $transitions->complete($this->stageActor($request),$request->fresh(),['TECHNICAL_ASSESSMENT'],'Smoke technical assessment.');
            $this->expectStage($request,'TECHNICIAN_ASSIGNMENT');
            $transitions->assignTechnician($this->stageActor($request),$request->fresh(),$tech->id,'Smoke technician assignment.');
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
                $workflow->advance($request,'FINANCE_REVIEW',$this->stageActor($request)->id,'Smoke finance review.');
            }

            $this->expectStage($request,'CLOSURE');
            $context=(array)($request->fresh()->workflow_context??[]);
            $context['operationally_closed_at']=now()->toIso8601String();
            $request->update(['workflow_context'=>$context,'status'=>'RESOLVED','resolved_at'=>now()]);
            $transitions->complete($this->stageActor($request),$request->fresh(),['CLOSURE'],'Smoke closure.');
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
            Auth::logout();
            if(DB::transactionLevel()>0) DB::rollBack();
            $this->line('Rollback complete: production data unchanged.');
        }
    }

    private function verifyInternalRequestInbox(): void
    {
        $authorization=app(AuthorizationService::class);
        $sales=User::query()->get()->first(function(User $user) use($authorization): bool {
            return strtoupper((string)$user->role)==='SALES' || $authorization->roleCodes($user)->contains('SALES');
        });
        if(!$sales) throw new \RuntimeException('No SALES user is available for the authenticated inbox smoke.');

        Auth::login($sales);
        $kernel=app(HttpKernel::class);
        $request=Request::create('/admin/public-requests','GET');
        $response=$kernel->handle($request);
        $kernel->terminate($request,$response);
        Auth::logout();

        if($response->getStatusCode()!==200){
            throw new \RuntimeException('Authenticated /admin/public-requests returned HTTP '.$response->getStatusCode().'.');
        }
        $this->info('PASS: Authenticated internal request inbox returned HTTP 200.');
    }

    private function stageActor(ServiceRequest $request): User
    {
        $request->refresh();
        $step=ApprovalRequest::query()
            ->where('tenant_id',$request->tenant_id)
            ->where('entity_type',ServiceRequest::class)
            ->where('entity_id',$request->id)
            ->where('action',$request->workflow_stage)
            ->where('status','PENDING')
            ->firstOrFail();
        if(!$step->assigned_user_id){
            throw new \RuntimeException("No resolved owner for {$request->workflow_stage}.");
        }
        return User::query()
            ->where('tenant_id',$request->tenant_id)
            ->findOrFail($step->assigned_user_id);
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
