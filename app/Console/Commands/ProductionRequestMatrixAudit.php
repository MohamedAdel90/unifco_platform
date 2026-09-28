<?php

namespace App\Console\Commands;

use App\Models\{Asset,Customer,CustomerSite,PublicServiceRequest,ServiceRequest,WorkOrder};
use App\Services\{ServiceContractCoverageResolver,ServiceRequestWorkflowTemplateRegistry};
use Illuminate\Console\Command;

class ProductionRequestMatrixAudit extends Command
{
    protected $signature = 'unifco:production-request-matrix-audit {--targets-only : Audit the nine agreed existing-customer requests only}';
    protected $description = 'Read-only audit of the agreed production request matrix.';

    public function handle(ServiceRequestWorkflowTemplateRegistry $registry, ServiceContractCoverageResolver $coverage): int
    {
        $cases=[
            ['no'=>'UNRM-926000017','group'=>'existing-linked','label'=>'Routine maintenance','expected'=>'MAINTENANCE'],
            ['no'=>'UNUM-926000018','group'=>'existing-linked','label'=>'Emergency maintenance','expected'=>'EMERGENCY_MAINTENANCE'],
            ['no'=>'UNQ-926000019','group'=>'existing-linked','label'=>'Spare parts quotation','expected'=>'SPARE_PARTS_QUOTATION'],
            ['no'=>'UNQ-926000020','group'=>'existing-linked','label'=>'Technical visit quotation','expected'=>'TECHNICAL_VISIT'],
            ['no'=>'UNRM-926000023','group'=>'existing-unlinked','label'=>'Routine maintenance','expected'=>'MAINTENANCE'],
            ['no'=>'UNUM-926000024','group'=>'existing-unlinked','label'=>'Emergency maintenance','expected'=>'EMERGENCY_MAINTENANCE'],
            ['no'=>'UNQ-926000025','group'=>'existing-unlinked','label'=>'Spare parts quotation','expected'=>'SPARE_PARTS_QUOTATION'],
            ['no'=>'UNQ-926000026','group'=>'existing-unlinked','label'=>'Technical visit quotation','expected'=>'TECHNICAL_VISIT'],
            ['no'=>'UNM-926000027','group'=>'existing-unlinked','label'=>'Verify actual request type','expected'=>null],
            ['no'=>'UNM-926000021','group'=>'existing-unlinked','label'=>'Maintenance contract quotation outside contract','expected'=>'MAINTENANCE_CONTRACT_QUOTATION'],
            ['no'=>'UNC-926000022','group'=>'existing-unlinked','label'=>'Technical consultation','expected'=>'TECHNICAL_CONSULTATION'],
            ['no'=>'UNM-926000028','group'=>'new-customer','label'=>'Maintenance contract quotation','expected'=>'MAINTENANCE_CONTRACT_QUOTATION'],
            ['no'=>'UNRM-926000029','group'=>'new-customer','label'=>'Routine maintenance','expected'=>'MAINTENANCE'],
            ['no'=>'UNUM-926000030','group'=>'new-customer','label'=>'Emergency maintenance','expected'=>'EMERGENCY_MAINTENANCE'],
            ['no'=>'UNQ-926000031','group'=>'new-customer','label'=>'Spare parts quotation','expected'=>'SPARE_PARTS_QUOTATION'],
            ['no'=>'UNQ-926000032','group'=>'new-customer','label'=>'Technical visit quotation','expected'=>'TECHNICAL_VISIT'],
            ['no'=>'UNM-926000033','group'=>'new-customer','label'=>'Maintenance contract','expected'=>'MAINTENANCE_CONTRACT_QUOTATION'],
            ['no'=>'UNC-926000034','group'=>'new-customer','label'=>'Technical consultation','expected'=>'TECHNICAL_CONSULTATION'],
        ];

        if ($this->option('targets-only')) $cases=array_slice($cases,0,9);

        $failures=0;
        foreach($cases as $case){
            $public=PublicServiceRequest::query()->where('reference_no',$case['no'])->orWhere('ticket_serial',$case['no'])->first();
            $service=ServiceRequest::withoutGlobalScopes()->where(function($q)use($case){
                $q->where('request_no',$case['no'])->orWhere('request_no','SR-'.$case['no']);
            })->first();
            if(!$service && $public?->service_request_id){
                $service=ServiceRequest::withoutGlobalScopes()->find($public->service_request_id);
            }
            $this->line(str_repeat('-',96));
            $this->info($case['no'].' | '.$case['group'].' | '.$case['label']);
            if(!$public) $this->warn('Public request: not found by reference_no/ticket_serial');
            else $this->line('Public: id='.$public->id.' intent='.($public->request_intent??'—').' subtype='.($public->request_subtype??'—').' status='.($public->status??'—').' service_request_id='.($public->service_request_id??'—'));
            if(!$service){
                $this->error('Service request: NOT FOUND');
                $failures++;
                continue;
            }

            $derived=$registry->keyFor($service);
            $asset=$service->asset_id ? Asset::withoutGlobalScopes()->find($service->asset_id) : null;
            $customer=$service->customer_id ? Customer::withoutGlobalScopes()->find($service->customer_id) : null;
            $site=$service->customer_site_id ? CustomerSite::withoutGlobalScopes()->find($service->customer_site_id) : null;
            $resolvedContract=$customer ? $coverage->resolve($customer,$site,$asset) : null;
            $linked=(bool)$service->service_contract_id;
            $workOrder=$service->work_order_id ? WorkOrder::withoutGlobalScopes()->find($service->work_order_id) : null;

            $this->line('Service: id='.$service->id.' request_no='.$service->request_no);
            $this->line('Customer: '.($service->customer_id??'—').' | Site: '.($service->customer_site_id??'—').' | Asset: '.($service->asset_id??'—').' | Asset code: '.($asset?->asset_code??'—'));
            $this->line('Asset owner: '.($asset?->customer_id??'—').' | Asset site: '.($asset?->customer_site_id??'—').' | Public asset: '.($public?->asset_id??'—'));
            if ($asset && (int)$asset->customer_id !== (int)$service->customer_id) {
                $hasBrand=trim((string)$public?->equipment_brand)!=='';
                $hasModel=trim((string)$public?->equipment_model)!=='';
                $this->line('Public equipment evidence: brand='.($hasBrand?'YES':'NO').' model='.($hasModel?'YES':'NO').' type='.(trim((string)$public?->asset_type)!==''?'YES':'NO').' site='.(trim((string)$public?->site_name)!==''?'YES':'NO'));
                $candidates=Asset::withoutGlobalScopes()
                    ->where('tenant_id',$service->tenant_id)
                    ->where('customer_id',$service->customer_id)
                    ->where(fn($query)=>$query->whereNull('contract_reference')->orWhere('contract_reference',''))
                    ->orderBy('id')->limit(25)->get();
                $this->line('Customer-owned uncovered asset candidates (max 25): '.$candidates->count());
                foreach ($candidates as $candidate) {
                    $brandMatch=$hasBrand && strcasecmp(trim((string)$candidate->manufacturer),trim((string)$public->equipment_brand))===0;
                    $modelMatch=$hasModel && strcasecmp(trim((string)$candidate->model_no),trim((string)$public->equipment_model))===0;
                    $this->line('Candidate: id='.$candidate->id.' code='.$candidate->asset_code.' site='.($candidate->customer_site_id??'—').' brand_match='.($brandMatch?'YES':'NO').' model_match='.($modelMatch?'YES':'NO'));
                }
            }
            $this->line('Contract DB: '.($service->service_contract_id??'—').' | Contract scoped resolver: '.($resolvedContract?->id??'—').' | Asset contract ref: '.($asset?->contract_reference??'—'));
            $this->line('Type: '.($service->request_type??'—').' | Subtype: '.($service->request_subtype??'—').' | Priority: '.($service->priority??'—').' | Eligibility: '.($service->eligibility??'—'));
            $this->line('Workflow DB: '.($service->workflow_key??'—').' | Derived: '.$derived.' | Stage: '.($service->workflow_stage??'—').' | Status: '.($service->status??'—'));
            $this->line('Work Order: '.($workOrder?->work_order_no??'—').' | WO asset: '.($workOrder?->asset_id??'—').' | WO contract: '.($workOrder?->service_contract_id??'—'));

            $issues=[];
            if($case['expected']!==null && $derived!==$case['expected']) $issues[]='derived workflow expected '.$case['expected'].' got '.$derived;
            if($service->workflow_key && $service->workflow_key!==$derived) $issues[]='stored workflow_key differs from derived workflow';
            if($case['group']==='existing-linked' && !$linked) $issues[]='expected contract-linked request but service_contract_id is empty';
            if($case['group']==='existing-unlinked' && $linked) $issues[]='expected unlinked request but service_contract_id='.$service->service_contract_id;
            if($service->service_contract_id && (!$resolvedContract || (int)$resolvedContract->id!==(int)$service->service_contract_id)) $issues[]='service_contract_id is not proven by asset/site scoped coverage';
            if($service->eligibility==='IN_CONTRACT' && !$resolvedContract) $issues[]='eligibility is IN_CONTRACT without scoped contract proof';
            if($service->eligibility==='CHARGEABLE' && $resolvedContract) $issues[]='eligibility is CHARGEABLE although scoped contract coverage exists';
            if(!$service->customer_id) $issues[]='customer_id missing';
            if($service->asset_id && !$asset) $issues[]='referenced asset is missing';
            if($asset && (int)$asset->customer_id!==(int)$service->customer_id) $issues[]='asset owner does not match request customer';
            if($asset && (int)$asset->tenant_id!==(int)$service->tenant_id) $issues[]='asset tenant does not match request tenant';
            if($asset && $site && $asset->customer_site_id && (int)$asset->customer_site_id!==(int)$site->id) $issues[]='asset site does not match request site';
            if(in_array($case['expected'],['MAINTENANCE','EMERGENCY_MAINTENANCE'],true) && !$service->asset_id) $issues[]='maintenance request has no asset_id';
            if($workOrder && (int)$workOrder->asset_id!==(int)$service->asset_id) $issues[]='work order asset_id does not match service request';
            if($workOrder && (int)($workOrder->service_contract_id??0)!==(int)($service->service_contract_id??0)) $issues[]='work order service_contract_id does not match service request';
            if($case['expected']==='SPARE_PARTS_QUOTATION' && !in_array('PRICING_PROCUREMENT',collect($registry->template($derived,(array)$service->workflow_context))->pluck('stage')->all(),true)) $issues[]='spare-parts workflow is missing PRICING_PROCUREMENT';
            if($case['expected']==='TECHNICAL_VISIT' && in_array('PRICING_PROCUREMENT',collect($registry->template($derived,(array)$service->workflow_context))->pluck('stage')->all(),true)) $issues[]='technical-visit workflow incorrectly includes PRICING_PROCUREMENT';

            if($issues){
                foreach($issues as $issue) $this->error('ISSUE: '.$issue);
                $failures+=count($issues);
            } else $this->info('ROUTE AUDIT: PASS');
        }
        $this->line(str_repeat('=',96));
        $failures ? $this->error("Matrix audit completed with {$failures} issue(s).") : $this->info('Matrix audit PASS: all agreed requests map to expected workflow, scoped contract context and core artifacts.');
        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
