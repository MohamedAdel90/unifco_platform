<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialDocument extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','customer_id','document_no','document_type','counterparty_name','document_date','due_date','currency','amount','control_account_code','offset_account_code','status','created_by','posted_by','journal_id','posted_at','open_amount'];
    protected function casts(): array { return ['document_date'=>'date','due_date'=>'date','posted_at'=>'datetime','amount'=>'decimal:2','open_amount'=>'decimal:2']; }
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}
