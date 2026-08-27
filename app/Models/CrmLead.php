<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class CrmLead extends Model {
    use BelongsToTenant;
    protected $fillable=[
        'tenant_id','organization_id','lead_no','name','company','email','mobile','commercial_registration','source','source_channel','source_detail',
        'status','lifecycle_stage','service_interest','city','inquiry_notes','first_touch_at','first_touch_user_id','qualified_at','qualified_by',
        'converted_customer_id','converted_at','converted_by','created_by','assigned_to','next_follow_up_at','duplicate_review_status','duplicate_customer_id',
        'duplicate_lead_id','conversion_approval_status','conversion_requested_by','conversion_requested_at','conversion_approved_by','conversion_approved_at','conversion_review_notes'
    ];
    protected function casts(): array { return [
        'first_touch_at'=>'datetime','qualified_at'=>'datetime','converted_at'=>'datetime','next_follow_up_at'=>'datetime',
        'conversion_requested_at'=>'datetime','conversion_approved_at'=>'datetime'
    ]; }
}
