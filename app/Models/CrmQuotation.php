<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class CrmQuotation extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','opportunity_id','quotation_no','quotation_date','currency','amount','status','created_by']; protected function casts(): array { return ['quotation_date'=>'date','amount'=>'decimal:2']; } }
