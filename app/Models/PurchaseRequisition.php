<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PurchaseRequisition extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','requisition_no','requested_date','purpose','status','created_by','approved_by','approved_at']; protected function casts(): array { return ['requested_date'=>'date','approved_at'=>'datetime']; } public function lines(): HasMany { return $this->hasMany(PurchaseRequisitionLine::class); } }
