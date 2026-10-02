<?php

namespace App\Console\Commands;

use App\Models\{Asset,PublicServiceRequest,ServiceRequest,WorkOrder};
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ReconcileHistoricalIntakeAssets extends Command
{
    protected $signature = 'unifco:reconcile-historical-intake-assets {--apply : Commit the guarded reconciliation}';
    protected $description = 'Reconcile the two legacy maintenance requests linked to the shared intake placeholder.';

    private const REFERENCES = ['UNRM-926000023', 'UNUM-926000024'];

    public function handle(AuditService $audit): int
    {
        $failures = 0;
        foreach (self::REFERENCES as $reference) {
            try {
                $result = DB::transaction(function () use ($reference, $audit): string {
                    $public = PublicServiceRequest::where('reference_no', $reference)->lockForUpdate()->firstOrFail();
                    $service = ServiceRequest::withoutGlobalScopes()->where('request_no', 'SR-'.$reference)->lockForUpdate()->firstOrFail();
                    $dedicated = Asset::withoutGlobalScopes()->where('tenant_id', $service->tenant_id)
                        ->where('asset_code', 'INTAKE-'.$reference)->first();
                    if ($dedicated && (int) $dedicated->customer_id === (int) $service->customer_id
                        && (int) $public->asset_id === (int) $dedicated->id
                        && (int) $service->asset_id === (int) $dedicated->id
                        && (int) WorkOrder::withoutGlobalScopes()->whereKey($service->work_order_id)->value('asset_id') === (int) $dedicated->id) {
                        return 'ALREADY RECONCILED';
                    }
                    if ((int) $public->service_request_id !== (int) $service->id
                        || (int) $public->tenant_id !== (int) $service->tenant_id
                        || (int) $public->organization_id !== (int) $service->organization_id
                        || $public->status !== 'CONVERTED_TO_WORK_ORDER'
                        || ! in_array($service->workflow_stage, ['TRIAGE', 'EMERGENCY_DISPATCH'], true)
                        || $service->status !== 'OPEN' || $service->eligibility !== 'CHARGEABLE'
                        || $service->service_contract_id !== null || $public->asset_id !== null
                        || trim((string) $public->asset_type) === ''
                        || trim((string) $public->equipment_brand) === '') {
                        throw new RuntimeException('Request state or original equipment evidence differs from the audited baseline.');
                    }

                    $placeholder = Asset::withoutGlobalScopes()->whereKey($service->asset_id)->lockForUpdate()->firstOrFail();
                    $workOrder = WorkOrder::withoutGlobalScopes()->whereKey($service->work_order_id)->lockForUpdate()->firstOrFail();
                    if ($placeholder->asset_code !== 'PUBLIC-SERVICE-INBOX'
                        || $placeholder->customer_id !== null
                        || (int) $placeholder->tenant_id !== (int) $service->tenant_id
                        || (int) $workOrder->tenant_id !== (int) $service->tenant_id
                        || $workOrder->work_order_no !== 'WO-'.$reference
                        || (int) $workOrder->asset_id !== (int) $placeholder->id
                        || $workOrder->status !== 'OPEN' || $workOrder->started_at !== null
                        || $workOrder->completed_at !== null
                        || ServiceRequest::withoutGlobalScopes()->where('work_order_id', $workOrder->id)->count() !== 1) {
                        throw new RuntimeException('Placeholder or work order is no longer safe to reconcile.');
                    }

                    $code = 'INTAKE-'.$reference;
                    if (Asset::withoutGlobalScopes()->where('tenant_id', $service->tenant_id)->where('asset_code', $code)->exists()) {
                        throw new RuntimeException('A dedicated intake asset already exists; manual reconciliation is required.');
                    }
                    if (! $this->option('apply')) return 'READY: request and work order can be linked to a dedicated draft intake asset';

                    // The submitted equipment description is evidence of an intake record,
                    // not verification of the physical asset or its contract coverage.
                    $asset = Asset::create([
                        'tenant_id' => $service->tenant_id,
                        'organization_id' => $service->organization_id,
                        'customer_id' => $service->customer_id,
                        'asset_code' => $code,
                        'name' => $public->asset_type,
                        'asset_category' => $public->asset_type,
                        'manufacturer' => $public->equipment_brand,
                        'model_no' => $public->equipment_model,
                        'physical_location' => $public->site_address ?: $public->site_name,
                        'status' => 'REGISTERED',
                        'verification_status' => 'DRAFT',
                    ]);
                    $service->update(['asset_id' => $asset->id]);
                    $workOrder->update(['asset_id' => $asset->id]);
                    $public->update(['asset_id' => $asset->id]);
                    $audit->record('HISTORICAL_INTAKE_ASSET_RECONCILED', $service,
                        ['asset_id' => $placeholder->id, 'work_order_asset_id' => $placeholder->id, 'public_asset_id' => null],
                        ['asset_id' => $asset->id, 'work_order_asset_id' => $asset->id, 'public_asset_id' => $asset->id],
                        reason: 'Replace shared unowned intake placeholder with draft customer intake asset from original request',
                        metadata: ['reference' => $reference, 'work_order_id' => $workOrder->id]);

                    return 'RECONCILED: dedicated draft intake asset '.$asset->asset_code;
                });
                $this->info($reference.' '.$result);
            } catch (Throwable $exception) {
                $this->error($reference.' BLOCKED: '.$exception->getMessage());
                $failures++;
            }
        }

        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
