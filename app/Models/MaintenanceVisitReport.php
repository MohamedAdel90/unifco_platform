<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MaintenanceVisitReport extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','customer_id','service_contract_id','asset_id','work_order_id','report_no','visit_date','visit_type','findings','work_performed','recommendations','technician_name','customer_acknowledgement'];
    protected function casts(): array { return ['visit_date'=>'date']; }
}
