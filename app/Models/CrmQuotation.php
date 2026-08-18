<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class CrmQuotation extends Model {
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','opportunity_id','customer_id','quotation_no','quotation_date','currency','amount','status','created_by','customer_approved_at','customer_rejected_at','customer_decision_notes'];
    protected function casts(): array { return ['quotation_date'=>'date','amount'=>'decimal:2','customer_approved_at'=>'datetime','customer_rejected_at'=>'datetime']; }
}
