<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BusinessTripExpense extends Model { use BelongsToTenant; protected $fillable=['tenant_id','business_trip_id','expense_date','category','description','amount','currency','receipt_ref','status','submitted_by','decided_by','decided_at']; protected function casts(): array { return ['expense_date'=>'date','amount'=>'decimal:2','decided_at'=>'datetime']; } public function trip(): BelongsTo { return $this->belongsTo(BusinessTrip::class,'business_trip_id'); } }
