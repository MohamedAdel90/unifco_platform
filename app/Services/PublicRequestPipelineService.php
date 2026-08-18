<?php

namespace App\Services;

use App\Models\{Asset,CrmLead,CrmOpportunity,CrmQuotation,Organization,PublicServiceRequest,ServiceRequest,Tenant,WorkOrder};
use Illuminate\Support\Facades\DB;

class PublicRequestPipelineService
{
    public function convert(PublicServiceRequest $public): PublicServiceRequest
    {
        return DB::transaction(function () use ($public) {
            $public = PublicServiceRequest::query()->lockForUpdate()->findOrFail($public->id);
            if ($public->converted_at) {
                return $public;
            }

            $tenant = Tenant::firstOrCreate(['code' => 'UNIFCO'], ['name' => 'UNIFCO', 'status' => 'ACTIVE']);
            $org = Organization::firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'HQ'],
                ['name' => 'UNIFCO HQ', 'status' => 'ACTIVE']
            );

            $links = ['tenant_id' => $tenant->id, 'organization_id' => $org->id];

            if ($public->request_type === 'QUOTATION') {
                $lead = CrmLead::create([
                    'tenant_id' => $tenant->id,
                    'organization_id' => $org->id,
                    'lead_no' => 'LEAD-'.$public->reference_no,
                    'name' => $public->company_name,
                    'company' => $public->company_name,
                    'email' => $public->email,
                    'mobile' => $public->mobile,
                    'commercial_registration' => $public->commercial_registration,
                    'source' => 'PUBLIC_WEBSITE',
                    'status' => 'NEW',
                ]);

                $opportunity = CrmOpportunity::create([
                    'tenant_id' => $tenant->id,
                    'organization_id' => $org->id,
                    'lead_id' => $lead->id,
                    'opportunity_no' => 'OPP-'.$public->reference_no,
                    'name' => $public->subject,
                    'stage' => 'QUALIFICATION',
                    'expected_value' => 0,
                    'probability' => 10,
                    'status' => 'OPEN',
                ]);

                $quotation = CrmQuotation::create([
                    'tenant_id' => $tenant->id,
                    'organization_id' => $org->id,
                    'opportunity_id' => $opportunity->id,
                    'quotation_no' => 'QT-'.$public->reference_no,
                    'quotation_date' => now()->toDateString(),
                    'currency' => 'SAR',
                    'amount' => 0,
                    'status' => 'DRAFT',
                ]);

                $links += [
                    'crm_lead_id' => $lead->id,
                    'crm_opportunity_id' => $opportunity->id,
                    'crm_quotation_id' => $quotation->id,
                    'status' => 'CONVERTED_TO_QUOTATION',
                ];
            } else {
                $asset = Asset::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'asset_code' => 'PUBLIC-SERVICE-INBOX'],
                    ['organization_id' => $org->id, 'name' => 'Public Emergency Service Intake', 'status' => 'REGISTERED']
                );

                $workOrder = WorkOrder::create([
                    'tenant_id' => $tenant->id,
                    'organization_id' => $org->id,
                    'work_order_no' => 'WO-'.$public->reference_no,
                    'asset_id' => $asset->id,
                    'maintenance_type' => 'CORRECTIVE',
                    'priority' => 'EMERGENCY',
                    'status' => 'OPEN',
                    'planned_start' => now(),
                ]);

                $serviceRequest = ServiceRequest::create([
                    'tenant_id' => $tenant->id,
                    'organization_id' => $org->id,
                    'request_no' => 'SR-'.$public->reference_no,
                    'company_name' => $public->company_name,
                    'commercial_registration' => $public->commercial_registration,
                    'email' => $public->email,
                    'mobile' => $public->mobile,
                    'service_category' => $public->service_category,
                    'subject' => $public->subject,
                    'details' => $public->details,
                    'site_city' => $public->site_city,
                    'priority' => 'EMERGENCY',
                    'status' => 'OPEN',
                    'work_order_id' => $workOrder->id,
                ]);

                $links += [
                    'service_request_id' => $serviceRequest->id,
                    'work_order_id' => $workOrder->id,
                    'status' => 'CONVERTED_TO_WORK_ORDER',
                ];
            }

            $public->update($links + ['converted_at' => now()]);
            return $public->fresh();
        });
    }
}
