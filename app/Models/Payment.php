<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','financial_document_id','payment_no','payment_date','amount','cash_account_code','created_by','journal_id'];
    protected function casts(): array { return ['payment_date'=>'date','amount'=>'decimal:2']; }
    public function document(): BelongsTo { return $this->belongsTo(FinancialDocument::class,'financial_document_id'); }
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
}
