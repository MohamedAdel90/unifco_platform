<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','work_order_no','asset_id','maintenance_plan_id','service_contract_id','maintenance_type','priority','status','planned_start',
        'labor_hours','labor_cost','material_cost','external_cost','total_cost','downtime_minutes','started_at','completed_at','failure_code','execution_notes','completion_notes',
        'started_by','completed_by','customer_accepted_at','customer_rejected_at','customer_acceptance_notes'
    ];

    protected static function booted(): void
    {
        static::updating(function (WorkOrder $workOrder): void {
            if($workOrder->isDirty('status') && $workOrder->status==='COMPLETED'){
                $unresolved=DB::table('work_order_part_requests')
                    ->where('work_order_id',$workOrder->id)
                    ->whereNotIn('status',['REJECTED','CLOSED'])
                    ->exists();
                if($unresolved) throw ValidationException::withMessages(['parts'=>'Resolve all requested/issued parts by consuming them on the asset or returning them before closing the work order.']);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'planned_start'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime','customer_accepted_at'=>'datetime','customer_rejected_at'=>'datetime',
            'labor_hours'=>'decimal:2','labor_cost'=>'decimal:2','material_cost'=>'decimal:2','external_cost'=>'decimal:2','total_cost'=>'decimal:2'
        ];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function plan(): BelongsTo { return $this->belongsTo(MaintenancePlan::class,'maintenance_plan_id'); }
    public function contract(): BelongsTo { return $this->belongsTo(ServiceContract::class,'service_contract_id'); }
}
