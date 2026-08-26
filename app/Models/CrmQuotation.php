<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CrmQuotation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','opportunity_id','customer_id','quotation_no','revision_no','parent_quotation_id','quotation_date','currency','amount','cost_amount','margin_pct','payment_terms_days','risk_level','status','created_by',
        'customer_approved_at','customer_rejected_at','customer_decision_notes',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date'=>'date','amount'=>'decimal:2','cost_amount'=>'decimal:2','margin_pct'=>'decimal:2',
            'customer_approved_at'=>'datetime','customer_rejected_at'=>'datetime',
        ];
    }
}
