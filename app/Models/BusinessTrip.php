<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};

class BusinessTrip extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','trip_no','employee_id','project_id','customer_id','trip_type','purpose','destination_city','destination_country','starts_on','ends_on','per_diem_rate','per_diem_days','per_diem_total','requested_advance','approved_advance','advance_status','travel_method','hotel_required','transport_required','status','requested_by','approved_by','approved_at','completion_notes','settlement_total','company_payable','employee_refund_due','settled_at','settled_by'];
    protected function casts(): array { return ['starts_on'=>'date','ends_on'=>'date','per_diem_rate'=>'decimal:2','per_diem_days'=>'decimal:2','per_diem_total'=>'decimal:2','requested_advance'=>'decimal:2','approved_advance'=>'decimal:2','hotel_required'=>'boolean','transport_required'=>'boolean','approved_at'=>'datetime','settlement_total'=>'decimal:2','company_payable'=>'decimal:2','employee_refund_due'=>'decimal:2','settled_at'=>'datetime']; }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function expenses(): HasMany { return $this->hasMany(BusinessTripExpense::class); }
}
