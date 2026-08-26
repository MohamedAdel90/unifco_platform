<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','customer_id','customer_site_id','service_contract_id','asset_id','assigned_engineer_id','request_no','request_type','company_name','commercial_registration','email','mobile',
        'service_category','subject','details','site_city','priority','status','workflow_stage','eligibility','procurement_required','work_order_id','quotation_id','workflow_started_at','current_stage_due_at',
        'responded_at','resolved_at','response_sla_minutes','resolution_sla_minutes',
    ];

    protected function casts(): array
    {
        return [
            'responded_at'=>'datetime','resolved_at'=>'datetime','workflow_started_at'=>'datetime','current_stage_due_at'=>'datetime',
            'procurement_required'=>'boolean',
        ];
    }
}
