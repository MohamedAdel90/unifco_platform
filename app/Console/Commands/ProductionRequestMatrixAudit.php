<?php

namespace App\Console\Commands;

use App\Models\{Asset,PublicServiceRequest,ServiceRequest};
use App\Services\ServiceRequestWorkflowTemplateRegistry;
use Illuminate\Console\Command;

class ProductionRequestMatrixAudit extends Command
{
    protected $signature = 'unifco:production-request-matrix-audit';
    protected $description = 'Read-only audit of the agreed production request matrix.';

    public function handle(ServiceRequestWorkflowTemplateRegistry $registry): int
    {
        $cases=[
            ['no'=>'UNRM-926000017','group'=>'existing-linked','label'=>'Routine maintenance','expected'=>'MAINTENANCE'],
            ['no'=>'UNUM-926000018','group'=>'existing-linked','label'=>'Emergency maintenance','expected'=>'EMERGENCY_MAINTENANCE'],
            ['no'=>'UNQ-926000019','group'=>'existing-linked','label'=>'Spare parts quotation','expected'=>'QUOTATION'],
            ['no'=>'UNQ-926000020','group'=>'existing-linked','label'=>'Technical visit quotation','expected'=>'QUOTATION'],
            ['no'=>'UNRM-926000023','group'=>'existing-unlinked','label'=>'Routine maintenance','expected'=>'MAINTENANCE'],
            ['no'=>'UNUM-926000024','group'=>'existing-unlinked','label'=>'Emergency maintenance','expected'=>'EMERGENCY_MAINTENANCE'],
            ['no'=>'UNQ-926000025','group'=>'existing-unlinked','label'=>'Spare parts quotation','expected'=>'QUOTATION'],
            ['no'=>'UNQ-926000026','group'=>'existing-unlinked','label'=>'Technical visit quotation','expected'=>'QUOTATION'],
            ['no'=>'UNM-926000027','group'=>'existing-unlinked','label'=>'Maintenance contract','expected'=>'MAINTENANCE_CONTRACT_QUOTATION'],
            ['no'=>'UNM-926000021','group'=>'existing-unlinked','label'=>'Maintenance contract quotation outside contract','expected'=>'MAINTENANCE_CONTRACT_QUOTATION'],
            ['no'=>'UNC-926000022','group'=>'existing-unlinked','label'=>'Technical consultation','expected'=>'TECHNICAL_CONSULTATION'],
            ['no'=>'UNM-926000028','group'=>'new-customer','label'=>'Routine maintenance','expected'=>'MAINTENANCE'],
            ['no'=>'UNRM-926000029','group'=>'new-customer','label'=>'Routine maintenance','expected'=>'MAINTENANCE'],
            ['no'=>'UNUM-926000030','group'=>'new-customer','label'=>'Emergency maintenance','expected'=>'EMERGENCY_MAINTENANCE'],
            ['no'=>'UNQ-926000031','group'=>'new-customer','label'=>'Spare parts quotation','expected'=>'QUOTATION'],
            ['no'=>'UNQ-926000032','group'=>'new-customer','label'=>'Technical visit quotation','expected'=>'QUOTATION'],
            ['no'=>'UNM-926000033','group'=>'new-customer','label'=>'Maintenance contract','expected'=>'MAINTENANCE_CONTRACT_QUOTATION'],
            ['no'=>'UNC-926000034','group'=>'new-customer','label'=>'Technical consultation','expected'=>'TECHNICAL_CONSULTATION'],
        ];

        $failures=0;
        foreach($cases as $case){
            $public=PublicServiceRequest::query()->where('reference_no',$case['no'])->orWhere('ticket_serial',$case['no'])->first();
            $service=ServiceRequest::withoutGlobalScopes()->where('request_no',$case['no'])->first();
            if(!$service && $public?->service_request_id){
                $service=ServiceRequest::withoutGlobalScopes()->find($public->service_request_id);
            }
            $this->line(str_repeat('-',88));
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
            $linked=(bool)$service->service_contract_id;
            $this->line('Service: id='.$service->id.' request_no='.$service->request_no);
            $this->line('Customer: '.($service->customer_id??'—').' | Asset: '.($service->asset_id??'—').' | Asset code: '.($asset?->asset_code??'—').' | Contract: '.($service->service_contract_id??'—'));
            $this->line('Type: '.($service->request_type??'—').' | Subtype: '.($service->request_subtype??'—').' | Priority: '.($service->priority??'—').' | Eligibility: '.($service->eligibility??'—'));
            $this->line('Workflow DB: '.($service->workflow_key??'—').' | Derived: '.$derived.' | Stage: '.($service->workflow_stage??'—').' | Status: '.($service->status??'—'));
            $issues=[];
            if($derived!==$case['expected']) $issues[]='derived workflow expected '.$case['expected'].' got '.$derived;
            if($service->workflow_key && $service->workflow_key!==$derived) $issues[]='stored workflow_key differs from derived workflow';
            if($case['group']==='existing-linked' && !$linked) $issues[]='expected contract-linked request but service_contract_id is empty';
            if($case['group']==='existing-unlinked' && $linked) $issues[]='expected unlinked request but service_contract_id='.$service->service_contract_id;
            if(!$service->customer_id) $issues[]='customer_id missing';
            if(in_array($case['expected'],['MAINTENANCE','EMERGENCY_MAINTENANCE'],true) && !$service->asset_id) $issues[]='maintenance request has no asset_id';
            if($issues){
                foreach($issues as $issue) $this->error('ISSUE: '.$issue);
                $failures+=count($issues);
            } else $this->info('ROUTE AUDIT: PASS');
        }
        $this->line(str_repeat('=',88));
        $failures ? $this->error("Matrix audit completed with {$failures} issue(s).") : $this->info('Matrix audit PASS: all agreed requests map to their expected workflow and contract context.');
        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
