<?php

namespace App\Console\Commands;

use App\Models\{ApprovalRequest,Asset,Customer,CustomerSite,PublicServiceRequest,ServiceRequest,WorkOrder};
use App\Services\{ServiceContractCoverageResolver,ServiceRequestWorkflowService,ServiceRequestWorkflowTemplateRegistry};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RepairProductionRequestMatrix extends Command
{
    protected $signature = 'unifco:repair-production-request-matrix {--apply : Apply the controlled backfill}';
    protected $description = 'Normalize the agreed legacy production request matrix onto the current workflow engine.';

    private const CASES = [
        'UNRM-926000017' => ['group'=>'existing-linked'],
        'UNUM-926000018' => ['group'=>'existing-linked'],
        'UNQ-926000019'  => ['group'=>'existing-linked','subtype'=>'SPARE_PARTS_QUOTE'],
        'UNQ-926000020'  => ['group'=>'existing-linked','subtype'=>'TECHNICAL_VISIT'],
        'UNRM-926000023' => ['group'=>'existing-unlinked'],
        'UNUM-926000024' => ['group'=>'existing-unlinked'],
        'UNQ-926000025'  => ['group'=>'existing-unlinked','subtype'=>'SPARE_PARTS_QUOTE'],
        'UNQ-926000026'  => ['group'=>'existing-unlinked','subtype'=>'TECHNICAL_VISIT'],
        'UNM-926000027'  => ['group'=>'existing-unlinked','subtype'=>'MAINTENANCE_CONTRACT_QUOTE'],
        'UNM-926000021'  => ['group'=>'existing-unlinked','subtype'=>'MAINTENANCE_CONTRACT_QUOTE'],
        'UNC-926000022'  => ['group'=>'existing-unlinked','subtype'=>'TECHNICAL_CONSULTATION'],
        'UNM-926000028'  => ['group'=>'new-customer','subtype'=>'MAINTENANCE_CONTRACT_QUOTE'],
        'UNRM-926000029' => ['group'=>'new-customer'],
        'UNUM-926000030' => ['group'=>'new-customer'],
        'UNQ-926000031'  => ['group'=>'new-customer','subtype'=>'SPARE_PARTS_QUOTE'],
        'UNQ-926000032'  => ['group'=>'new-customer','subtype'=>'TECHNICAL_VISIT'],
        'UNM-926000033'  => ['group'=>'new-customer','subtype'=>'MAINTENANCE_CONTRACT_QUOTE'],
        'UNC-926000034'  => ['group'=>'new-customer','subtype'=>'TECHNICAL_CONSULTATION'],
    ];

    public function handle(
        ServiceRequestWorkflowTemplateRegistry $registry,
        ServiceContractCoverageResolver $coverage,
        ServiceRequestWorkflowService $workflow,
    ): int {
        if (! $this->option('apply')) {
            $this->warn('Dry run only. Re-run with --apply to mutate the agreed matrix records.');
        }

        $failures = 0;
        foreach (self::CASES as $reference => $case) {
            try {
                $service = ServiceRequest::withoutGlobalScopes()
                    ->whereIn('request_no', [$reference, 'SR-'.$reference])
                    ->first();
                $public = PublicServiceRequest::query()->where('reference_no', $reference)->first();

                if (! $service) {
                    $this->error($reference.': service request not found');
                    $failures++;
                    continue;
                }

                $expectedInitial = match ($registry->keyFor($service)) {
                    ServiceRequestWorkflowTemplateRegistry::MAINTENANCE => 'TRIAGE',
                    ServiceRequestWorkflowTemplateRegistry::EMERGENCY_MAINTENANCE => 'EMERGENCY_DISPATCH',
                    ServiceRequestWorkflowTemplateRegistry::TECHNICAL_CONSULTATION => 'OPERATIONS_REVIEW',
                    default => 'SALES_REVIEW',
                };
                if (! in_array((string) $service->workflow_stage, [$expectedInitial, ''], true)) {
                    $this->error($reference.': refused to rebaseline progressed request at stage '.$service->workflow_stage);
                    $failures++;
                    continue;
                }

                $this->line($reference.' => '.$case['group'].'; current='.$service->workflow_key.'/'.$service->request_subtype.'/'.$service->eligibility);
                if (! $this->option('apply')) continue;

                DB::transaction(function () use ($reference, $case, $service, $public, $registry, $coverage, $workflow): void {
                    if (! empty($case['subtype'])) {
                        $service->request_subtype = $case['subtype'];
                        if ($public) {
                            $public->request_subtype = $case['subtype'];
                            $public->save();
                        }
                    }

                    $asset = $service->asset_id ? Asset::withoutGlobalScopes()->find($service->asset_id) : null;
                    $customer = $service->customer_id ? Customer::withoutGlobalScopes()->find($service->customer_id) : null;
                    $site = $service->customer_site_id ? CustomerSite::withoutGlobalScopes()->find($service->customer_site_id) : null;
                    $resolved = $customer ? $coverage->resolve($customer, $site, $asset) : null;

                    if ($case['group'] === 'existing-linked') {
                        if (! $resolved) throw new \RuntimeException($reference.': linked case has no scoped contract proof');
                        $service->service_contract_id = $resolved->id;
                        $service->eligibility = 'IN_CONTRACT';
                    } else {
                        $service->service_contract_id = null;
                        $service->eligibility = 'CHARGEABLE';
                    }

                    $context = (array) ($service->workflow_context ?? []);
                    $context['chargeable'] = $service->eligibility === 'CHARGEABLE';
                    $context['procurement_required'] = in_array(strtoupper((string) $service->request_subtype), [
                        'SPARE_PARTS_QUOTE','SPARE_PARTS_QUOTATION','PARTS_QUOTE','SPARE_PARTS'
                    ], true);
                    $service->workflow_context = $context;
                    $service->save();

                    if ($service->work_order_id) {
                        WorkOrder::withoutGlobalScopes()->whereKey($service->work_order_id)->update([
                            'service_contract_id' => $service->service_contract_id,
                        ]);
                    }

                    $derived = $registry->keyFor($service->fresh());
                    $template = $registry->template($derived, $context);
                    $initial = $template[0]['stage'] ?? null;
                    if (! $initial) throw new \RuntimeException($reference.': workflow template has no initial stage');

                    ApprovalRequest::withoutGlobalScopes()
                        ->where('entity_type', ServiceRequest::class)
                        ->where('entity_id', $service->id)
                        ->delete();

                    $service->forceFill([
                        'workflow_key' => $derived,
                        'workflow_stage' => $initial,
                        'approval_state' => 'PENDING',
                        'next_action' => $initial,
                        'current_stage_due_at' => null,
                    ])->save();

                    $workflow->start($service->fresh(), $context);
                });

                $service->refresh();
                $this->info($reference.': repaired => '.$service->workflow_key.'/'.$service->request_subtype.'/'.$service->eligibility);
            } catch (Throwable $e) {
                $this->error($reference.': '.$e->getMessage());
                $failures++;
            }
        }

        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
