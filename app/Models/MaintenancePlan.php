<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenancePlan extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','asset_id','service_contract_id','plan_no','name','frequency_type','frequency_value','next_due_date','next_due_meter','priority','status'];
    protected function casts(): array { return ['next_due_date'=>'date','next_due_meter'=>'decimal:4']; }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function contract(): BelongsTo { return $this->belongsTo(ServiceContract::class,'service_contract_id'); }
}
