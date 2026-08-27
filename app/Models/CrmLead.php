<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class CrmLead extends Model {
    use BelongsToTenant;
    protected $fillable=[
        'tenant_id','organization_id','lead_no','name','company','email','mobile','commercial_registration','source','source_channel','source_detail',
        'status','lifecycle_stage','service_interest','city','inquiry_notes','first_touch_at','first_touch_user_id','qualified_at','qualified_by',
        'converted_customer_id','converted_at','converted_by','created_by'
    ];
    protected function casts(): array { return ['first_touch_at'=>'datetime','qualified_at'=>'datetime','converted_at'=>'datetime']; }
}
