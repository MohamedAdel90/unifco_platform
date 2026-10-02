<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\CrmQuotation;
use App\Models\FinancialDocument;
use App\Models\Payment;
use App\Models\PublicServiceRequest;
use App\Models\ServiceRequest;
use App\Models\WorkOrderPartRequest;
use App\Services\CustomerRequestStatusPresenter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
                    'approvals'=>[],
                    'parts'=>[],
                    'attachments'=>null,
                    'audit_events'=>null,
                    'scope_consistency'=>null,
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

            $approvals = ApprovalRequest::withoutGlobalScopes()
                ->where('entity_type', 'service_request')
                ->where('entity_id', $request->id)
                ->orderBy('id')
                ->get(['id','action','workflow_key','step_order','status','requested_by','assigned_user_id','decided_by','decided_at']);

            $parts = $request->work_order_id
                ? WorkOrderPartRequest::withoutGlobalScopes()->where('work_order_id', $request->work_order_id)->orderBy('id')->get()
                : collect();

            $attachments = $public ? [
                'problem_photos'=>count((array) ($public->problem_photo_paths ?? [])),
                'equipment_photos'=>count((array) ($public->equipment_photo_paths ?? [])),
                'previous_reports'=>count((array) ($public->previous_report_paths ?? [])),
                'legacy_attachments'=>count((array) ($public->attachment_paths ?? [])),
            ] : null;

            $auditEvents = DB::table('audit_logs')
                ->where(function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('entity_type', 'service_request')->where('entity_id', $request->id);
                    })->orWhere(function ($q) use ($request) {
                        $q->where('entity_type', ServiceRequest::class)->where('entity_id', $request->id);
                    });
                })
                ->count();

            $scopeConsistency = [
                'public_customer_matches'=>!$public || !$public->customer_id || (int) $public->customer_id === (int) $request->customer_id,
                'asset_customer_matches'=>!$request->asset_id || (int) DB::table('assets')->where('id', $request->asset_id)->value('customer_id') === (int) $request->customer_id,
                'work_order_customer_matches'=>!$request->work_order_id || (int) DB::table('work_orders')->where('id', $request->work_order_id)->value('customer_id') === (int) $request->customer_id,
                'quotation_customer_matches'=>!$quotation || (int) $quotation->customer_id === (int) $request->customer_id,
                'invoice_customer_matches'=>!$invoice || (int) $invoice->customer_id === (int) $request->customer_id,
            ];

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
                'approvals'=>$approvals->map(fn($approval)=>['id'=>$approval->id,'code'=>$approval->workflow_key ?: $approval->action,'sequence'=>$approval->step_order,'status'=>$approval->status,'requested_by'=>$approval->requested_by,'assigned_to'=>$approval->assigned_user_id,'decided_by'=>$approval->decided_by,'decided_at'=>optional($approval->decided_at)->toIso8601String()])->values()->all(),
                'parts'=>$parts->map(fn($part)=>['id'=>$part->id,'request_no'=>$part->request_no,'status'=>$part->status,'asset_id'=>$part->asset_id,'approved_at'=>optional($part->approved_at)->toIso8601String(),'issued_at'=>optional($part->issued_at)->toIso8601String(),'received_at'=>optional($part->received_at)->toIso8601String()])->values()->all(),
                'attachments'=>$attachments,
                'audit_events'=>$auditEvents,
                'scope_consistency'=>$scopeConsistency,
                'closure'=>in_array(strtoupper((string)$request->status),['CLOSED','COMPLETED'],true)?$request->status:null,
                'customer_facing'=>$customer,
            ], JSON_UNESCAPED_SLASHES));
        }

        $auditIntegrity = $this->verifyAuditLinks();
        $this->line(json_encode(['audit_integrity'=>$auditIntegrity], JSON_UNESCAPED_SLASHES));
        if (! ($auditIntegrity['valid'] ?? false)) {
            $failures++;
            $this->error('Audit chain linkage verification failed or unavailable.');
        }

        if ($failures > 0) {
            $this->error("READ-ONLY EVIDENCE INCOMPLETE: {$failures} evidence check(s) failed.");
            return self::FAILURE;
        }

        $this->info('READ-ONLY EVIDENCE COMPLETE');
        return self::SUCCESS;
    }
    private function verifyAuditLinks(): array
    {
        if (! Schema::hasColumn('audit_logs', 'previous_hash') || ! Schema::hasColumn('audit_logs', 'entry_hash')) {
            return ['valid'=>false, 'method'=>'linkage_only', 'reason'=>'hash_columns_unavailable'];
        }

        $checked = 0;
        $broken = [];
        $previous = [];
        foreach (DB::table('audit_logs')->orderBy('id')->cursor() as $row) {
            $tenant = $row->tenant_id ?? 'global';
            $expected = $previous[$tenant] ?? null;
            if (($row->previous_hash ?: null) !== $expected ||
                ! preg_match('/^[a-f0-9]{64}$/', (string) $row->entry_hash)) {
                $broken[] = $row->id;
            }
            $previous[$tenant] = $row->entry_hash;
            $checked++;
        }

        // Stored timestamps do not retain the precision used when entry_hash was
        // generated. This verifies linkage and hash presence, not content hashes.
        return ['valid'=>empty($broken), 'method'=>'linkage_only',
            'content_verified'=>false, 'checked'=>$checked, 'broken_ids'=>$broken];
    }

}
