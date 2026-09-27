<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialDocument extends Model
{
    use BelongsToTenant;

    protected $fillable=[
        'tenant_id','organization_id','customer_id','project_id','service_request_id','work_order_id','crm_quotation_id',
        'document_no','document_type','counterparty_name','document_date','due_date','currency','amount',
        'control_account_code','offset_account_code','status','created_by','posted_by','journal_id','posted_at','open_amount'
    ];

    protected function casts(): array
    {
        return ['document_date'=>'date','due_date'=>'date','posted_at'=>'datetime','amount'=>'decimal:2','open_amount'=>'decimal:2'];
    }

    protected static function booted(): void
    {
        static::creating(function (FinancialDocument $document): void {
            if ($document->document_type !== 'AR_INVOICE' || $document->service_request_id) return;
            if (! preg_match('/^INV-SR-(\d+)$/', (string) $document->document_no, $matches)) return;

            $request = ServiceRequest::query()
                ->where('tenant_id', $document->tenant_id)
                ->whereKey((int) $matches[1])
                ->when($document->customer_id, fn ($query) => $query->where('customer_id', $document->customer_id))
                ->first();

            if (! $request) return;

            $document->service_request_id = $request->id;
            $document->work_order_id ??= $request->work_order_id;
            $document->crm_quotation_id ??= $request->quotation_id;
        });
    }

    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function serviceRequest(): BelongsTo { return $this->belongsTo(ServiceRequest::class); }
    public function workOrder(): BelongsTo { return $this->belongsTo(WorkOrder::class); }
    public function quotation(): BelongsTo { return $this->belongsTo(CrmQuotation::class,'crm_quotation_id'); }
}
