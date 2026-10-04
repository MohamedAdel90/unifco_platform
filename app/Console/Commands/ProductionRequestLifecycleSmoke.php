<?php

namespace App\Console\Commands;

use App\Models\{ApprovalRequest,Asset,Customer,CustomerActivityEvent,ServiceContract,ServiceRequest,User,WorkOrder};
use App\Services\{AuthorizationService,MaintenanceRequestTransitionService,RequestStageOwnerService,ScopeService,ServiceRequestWorkflowService};
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
                'resolution_sla_minutes'=>480,
                'submitted_at'=>now(),
            ]);

            app(ServiceRequestWorkflowService::class)->routeNewRequest($request);
            $request->refresh();
            $this->line("Initial stage: {$request->workflow_stage}");

            $guard=0;
            while($request->workflow_stage!=='CLOSED' && $guard++<20){
                $stage=$request->workflow_stage;
                $owner=app(RequestStageOwnerService::class)->resolve($request);
                if(($owner['status']??null)==='NEEDS_ASSIGNMENT') throw new \RuntimeException("NEEDS_ASSIGNMENT at {$stage}");
                $actor=$this->resolveActor($request,$owner);
                if(!$actor) throw new \RuntimeException("No eligible actor for {$stage}");
                Auth::login($actor);

                if($stage==='TECHNICIAN_ASSIGNMENT'){
                    $request->assigned_technician_id=$tech->id;
                    $request->save();
                }

                app(MaintenanceRequestTransitionService::class)->advance($request,$actor);
                $request->refresh();
                $this->line("{$stage} -> {$request->workflow_stage} by {$actor->email}");
            }

            if($request->workflow_stage!=='CLOSED') throw new \RuntimeException('Lifecycle did not reach CLOSED.');
            $this->info('PASS: Routine Maintenance reached CLOSED through the full production workflow.');
            DB::rollBack();
            $this->info('Rollback complete: production data unchanged.');
            return self::SUCCESS;
        } catch(Throwable $e){
            DB::rollBack();
            $this->error('FAIL: '.get_class($e).' status='.(method_exists($e,'getStatusCode')?$e->getStatusCode():'n/a').' message='.($e->getMessage()?:'[empty]').' at '.$e->getFile().':'.$e->getLine());
            $this->warn('Rollback complete: production data unchanged.');
            return self::FAILURE;
        } finally {
            Auth::logout();
        }
    }

    private function resolveActor(ServiceRequest $request,array $owner): ?User
    {
        if(($owner['status']??null)==='ASSIGNED' && !empty($owner['user_id'])) return User::find($owner['user_id']);
        if(($owner['status']??null)!=='ROLE_QUEUE' || empty($owner['role'])) return null;
        return User::query()->where('is_active',true)->whereHas('roles',fn($q)=>$q->where('code',$owner['role']))
            ->get()->first(fn(User $user)=>app(ScopeService::class)->canAccess($user,$request));
    }

    private function verifyInternalRequestInbox(): void
    {
        $user=User::where('email','operations.manager@unifco.local')->firstOrFail();
        Auth::login($user);
        $request=Request::create('/workflow/requests','GET');
        $request->setUserResolver(fn()=>$user);
        $response=app(HttpKernel::class)->handle($request);
        if($response->getStatusCode()!==200) throw new \RuntimeException('Internal request inbox returned HTTP '.$response->getStatusCode());
        $this->line('Internal Request Inbox: HTTP 200');
        app(HttpKernel::class)->terminate($request,$response);
        Auth::logout();
    }
}
