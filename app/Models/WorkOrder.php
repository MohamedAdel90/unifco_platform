<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','work_order_no','asset_id','maintenance_plan_id','service_contract_id','maintenance_type','priority','status','planned_start','labor_hours','labor_cost','material_cost','external_cost','total_cost','downtime_minutes','started_at','completed_at','failure_code'];
    protected function casts(): array { return ['planned_start'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime','labor_hours'=>'decimal:2','labor_cost'=>'decimal:2','material_cost'=>'decimal:2','external_cost'=>'decimal:2','total_cost'=>'decimal:2']; }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function plan(): BelongsTo { return $this->belongsTo(MaintenancePlan::class,'maintenance_plan_id'); }
    public function contract(): BelongsTo { return $this->belongsTo(ServiceContract::class,'service_contract_id'); }
}
