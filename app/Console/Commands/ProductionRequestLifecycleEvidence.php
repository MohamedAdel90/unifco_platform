<?php

namespace App\Console\Commands;

use App\Models\CrmQuotation;
use App\Models\FinancialDocument;
use App\Models\Payment;
use App\Models\PublicServiceRequest;
use App\Models\ServiceRequest;
use App\Services\CustomerRequestStatusPresenter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProductionRequestLifecycleEvidence extends Command
{
    protected $signature = 'unifco:production-request-lifecycle-evidence';
    protected $description = 'Emit read-only lifecycle evidence for the agreed production request matrix';

    private const REFERENCES = ['UNRM-926000017','UNUM-926000018','UNQ-926000019','UNQ-926000020','UNRM-926000023','UNUM-926000024','UNQ-926000025','UNQ-926000026','UNM-926000027'];

    public function handle(CustomerRequestStatusPresenter $presenter): int
    {
        $this->info('PRODUCTION REQUEST LIFECYCLE EVIDENCE — READ ONLY');
        $this->line('generated_at='.now()->toIso8601String());
        $failures = 0;

        foreach (self::REFERENCES as $reference) {
            $public = PublicServiceRequest::query()
                ->where('reference_no', $reference)
                ->orWhere('ticket_serial', $reference)
                ->first();

            $request = ServiceRequest::withoutGlobalScopes()
                ->where(function ($query) use ($reference) {
                    $query->where('request_no', $reference)
                        ->orWhere('request_no', 'SR-'.$reference);
                })
                ->first();

            if (! $request && $public?->service_request_id) {
                $request = ServiceRequest::withoutGlobalScopes()->find($public->service_request_id);
            }

            if (! $request) {
                $failures++;
                $this->line(json_encode([
                    'reference'=>$reference,
                    'record'=>$public ? 'unresolved_public_intake' : 'missing',
                    'public_request_id'=>$public?->id,
                    'public_service_request_id'=>$public?->service_request_id,
                    'raw_status'=>$public?->status,
                    'current_stage'=>null,
                    'responsible'=>null,
                    'work_order'=>null,
                    'quotation'=>null,
                    'invoice'=>null,
                    'payments'=>[],
                    'outstanding'=>null,
                    'closure'=>null,
                    'customer_facing'=>null,
                ], JSON_UNESCAPED_SLASHES));
                continue;
            }

            $actorIds = array_values(array_unique(array_filter([
                $request->operations_manager_id,
                $request->project_manager_id,
                $request->assigned_engineer_id,
            ])));
            $actors = empty($actorIds)
                ? collect()
                : DB::table('users')->whereIn('id', $actorIds)->get()->keyBy('id');
            $label = static function ($id) use ($actors) {
                if (! $id) return null;
                $user = $actors->get($id);
                return $user ? ($user->email ?? $user->name ?? ('user#'.$id)) : 'user#'.$id;
            };
            $responsible = [
                'operations_manager'=>$label($request->operations_manager_id),
                'project_manager'=>$label($request->project_manager_id),
                'assigned_engineer'=>$label($request->assigned_engineer_id),
                'department'=>$request->assigned_department,
            ];

            $quotation = $request->quotation_id ? CrmQuotation::withoutGlobalScopes()->find($request->quotation_id) : null;
            $documents = FinancialDocument::withoutGlobalScopes()->where('service_request_id', $request->id)->orderBy('id')->get();
            $invoice = $documents->firstWhere('document_type', 'AR_INVOICE');
            $payments = $documents->isEmpty() ? collect() : Payment::withoutGlobalScopes()->whereIn('financial_document_id', $documents->pluck('id'))->orderBy('id')->get();
            $customer = $presenter->present($request);

            $this->line(json_encode([
                'reference'=>$reference,
                'record'=>'service_request',
                'public_request_id'=>$public?->id,
                'public_status'=>$public?->status,
                'request_id'=>$request->id,
                'request_no'=>$request->request_no,
                'status'=>$request->status,
                'current_stage'=>$request->workflow_stage,
                'responsible'=>$responsible,
                'customer_id'=>$request->customer_id,
                'site_id'=>$request->customer_site_id,
                'contract_id'=>$request->service_contract_id,
                'asset_id'=>$request->asset_id,
                'eligibility'=>$request->eligibility,
                'work_order'=>$request->work_order_id,
                'quotation'=>$quotation?['id'=>$quotation->id,'no'=>$quotation->quotation_no,'status'=>$quotation->status,'amount'=>$quotation->amount,'customer_approved_at'=>optional($quotation->customer_approved_at)->toIso8601String(),'customer_rejected_at'=>optional($quotation->customer_rejected_at)->toIso8601String()]:null,
                'invoice'=>$invoice?['id'=>$invoice->id,'no'=>$invoice->document_no,'status'=>$invoice->status,'amount'=>$invoice->amount,'open_amount'=>$invoice->open_amount]:null,
                'payments'=>$payments->map(fn($payment)=>['id'=>$payment->id,'no'=>$payment->payment_no,'amount'=>$payment->amount,'date'=>optional($payment->payment_date)->toDateString()])->values()->all(),
                'outstanding'=>$invoice?->open_amount,
                'closure'=>in_array(strtoupper((string)$request->status),['CLOSED','COMPLETED'],true)?$request->status:null,
                'customer_facing'=>$customer,
            ], JSON_UNESCAPED_SLASHES));
        }

        if ($failures > 0) {
            $this->error("READ-ONLY EVIDENCE INCOMPLETE: {$failures} agreed request(s) could not be resolved to service_requests.");
            return self::FAILURE;
        }

        $this->info('READ-ONLY EVIDENCE COMPLETE');
        return self::SUCCESS;
    }
}
