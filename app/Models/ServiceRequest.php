<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','customer_id','service_contract_id','asset_id','request_no','company_name','commercial_registration','email','mobile',
        'service_category','subject','details','site_city','priority','status','work_order_id','responded_at','resolved_at','response_sla_minutes','resolution_sla_minutes',
    ];

    protected function casts(): array
    {
        return ['responded_at'=>'datetime','resolved_at'=>'datetime'];
    }
}
