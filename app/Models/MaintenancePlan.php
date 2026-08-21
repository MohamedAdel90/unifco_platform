<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenancePlan extends Model
{
    use BelongsToTenant;

    protected $fillable=[
        'tenant_id','organization_id','asset_id','service_contract_id','plan_no','name','maintenance_strategy','frequency_type','frequency_value',
        'next_due_date','next_due_meter','priority','estimated_duration_minutes','meter_type','safety_instructions','required_skill',
        'auto_generate_work_orders','lead_days','status'
    ];

    protected function casts(): array
    {
        return ['next_due_date'=>'date','next_due_meter'=>'decimal:4','auto_generate_work_orders'=>'boolean'];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function contract(): BelongsTo { return $this->belongsTo(ServiceContract::class,'service_contract_id'); }
}
