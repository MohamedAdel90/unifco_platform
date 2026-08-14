<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class SupplierInvoice extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','supplier_id','purchase_order_id','invoice_no','invoice_date','amount','status','financial_document_id','created_by']; protected function casts(): array { return ['invoice_date'=>'date','amount'=>'decimal:2']; } }
